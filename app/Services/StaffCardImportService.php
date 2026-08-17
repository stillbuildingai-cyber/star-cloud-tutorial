<?php

namespace App\Services;

use App\Models\StaffCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

class StaffCardImportService
{
    /**
     * Import staff cards from Excel file
     *
     * @param string $filePath
     * @param int|null $companyId
     * @return array
     */
    public function import(string $filePath, ?int $companyId = null): array
    {
        $reader = new Reader();
        $reader->open($filePath);

        $results = [
            'success_count' => 0,
            'error_count' => 0,
            'errors' => [],
        ];

        $rowCount = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowCount++;
                
                // Skip heading row (Row 1)
                if ($rowCount === 1) {
                    continue;
                }

                $cells = $row->toArray();
                
                // Expecting: 0:employee_id, 1:name, 2:card_uid, 3:notes
                $data = [
                    'employee_id' => isset($cells[0]) ? trim((string)$cells[0]) : null,
                    'name' => isset($cells[1]) ? trim((string)$cells[1]) : null,
                    'card_uid' => isset($cells[2]) ? trim((string)$cells[2]) : null,
                    'notes' => isset($cells[3]) ? trim((string)$cells[3]) : null,
                    'company_id' => $companyId,
                ];

                // Basic validation
                if (empty($data['employee_id']) && empty($data['name']) && empty($data['card_uid'])) {
                    continue; // Skip empty rows
                }

                $validator = Validator::make($data, [
                    'employee_id' => 'required|string|max:50',
                    'name' => 'required|string|max:100',
                    'card_uid' => 'required|string|max:100',
                ]);

                if ($validator->fails()) {
                    $results['error_count']++;
                    $results['errors'][] = [
                        'row' => $rowCount,
                        'messages' => $validator->errors()->all(),
                    ];
                    continue;
                }

                try {
                    // Update or create based on employee_id and company_id
                    StaffCard::updateOrCreate(
                        [
                            'company_id' => $data['company_id'],
                            'employee_id' => $data['employee_id'],
                        ],
                        [
                            'card_uid' => $data['card_uid'],
                            'name' => $data['name'],
                            'notes' => $data['notes'],
                            'status' => 'active',
                        ]
                    );
                    $results['success_count']++;
                } catch (\Exception $e) {
                    $results['error_count']++;
                    $results['errors'][] = [
                        'row' => $rowCount,
                        'messages' => [$e->getMessage()],
                    ];
                }
            }
        }

        $reader->close();

        return $results;
    }

    /**
     * Download Excel template for staff cards
     *
     * @return void
     */
    public function downloadTemplate()
    {
        $writer = new Writer();
        $fileName = 'staff_cards_template.xlsx';
        
        $writer->openToBrowser($fileName);

        // Header Row
        $headerRow = Row::fromValues([
            'Employee ID (必填)',
            'Staff Name (必填)',
            'IC Card UID (必填)',
            'Notes (選填)'
        ]);
        $writer->addRow($headerRow);

        // Example Row
        $exampleRow = Row::fromValues([
            'S001',
            '王小明',
            '12345678',
            '這是備註範例'
        ]);
        $writer->addRow($exampleRow);

        $writer->close();
    }
}
