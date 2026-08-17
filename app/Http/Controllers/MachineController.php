<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MachineController extends Controller
{
    /**
     * Display a listing of machines with smart socket live telemetry data.
     */
    public function index()
    {
        $machines = DB::table('machines')->get();
        return view('machines.index', compact('machines'));
    }

    /**
     * Receive telemetry data from ESP32 Smart Socket.
     * POST /api/v1/telemetry
     */
    public function receiveTelemetry(Request $request)
    {
        try {
            $machineId = $request->input('machineId') ?? $request->input('machineID');

            if (!$machineId) {
                return response()->json(['error' => 'Missing machineId'], 400);
            }

            $now = now();
            $data = [
                'socket_ip'         => $request->input('ip'),
                'relay_state'       => $request->input('relay', false) ? 1 : 0,
                'current_power'     => (float) $request->input('power', 0.0),
                'current_amp'       => (float) $request->input('current', 0.0),
                'voltage'           => (float) $request->input('voltage', 110.0),
                'total_energy'      => (float) $request->input('energy', 0.0),
                'last_telemetry_at' => $now,
            ];

            // 嘗試更新已存在之機台資料
            $affected = DB::table('machines')->where('machine_id', $machineId)->update($data);

            // 若機台尚未建立，自動新增資料庫紀錄
            if ($affected === 0) {
                $exists = DB::table('machines')->where('machine_id', $machineId)->exists();
                if (!$exists) {
                    $insertData = array_merge([
                        'name'       => '智慧插座 (' . substr($machineId, -6) . ')',
                        'machine_id' => $machineId,
                        'location'   => 'A區生產線',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $data);
                    
                    try {
                        DB::table('machines')->insert($insertData);
                    } catch (\Exception $e) {
                        // 防範併發寫入例外
                        DB::table('machines')->where('machine_id', $machineId)->update($data);
                    }
                }
            }

            return response()->json([
                'status'     => 'success',
                'machineId'  => $machineId,
                'updated_at' => $now->toDateTimeString()
            ]);
        } catch (\Throwable $ex) {
            return response()->json([
                'status' => 'error',
                'message' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Provision/Register new Machine ID.
     * POST /api/v1/machines/register
     */
    public function store(Request $request)
    {
        $mId = $request->input('machineId') ?? $request->input('machine_id');
        $name = $request->input('name');
        $loc = $request->input('location', 'A區生產線');

        if (!$mId || !$name) {
            return response()->json(['error' => 'Missing machineId or name'], 400);
        }

        DB::table('machines')->updateOrInsert(
            ['machine_id' => $mId],
            [
                'name'       => $name,
                'location'   => $loc,
                'updated_at' => now(),
            ]
        );

        return response()->json(['status' => 'success', 'machineId' => $mId]);
    }
}
