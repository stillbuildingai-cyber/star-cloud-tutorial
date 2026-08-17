<?php

namespace App\Jobs\Product;

use App\Models\System\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProductsToMachinesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $companyId
     * @param string|null $remark
     * @param int|null $userId
     */
    public function __construct(
        protected int $companyId,
        protected ?string $remark = null,
        protected ?int $userId = null
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $company = Company::with('machines')->find($this->companyId);

        if (!$company || $company->machines->isEmpty()) {
            return;
        }

        $delayMs = 100; // 100ms per machine

        foreach ($company->machines as $index => $machine) {
            // Calculate delay to avoid thundering herd (100ms, 200ms, 300ms...)
            $currentDelay = ($index * $delayMs) / 1000;

            SendProductSyncCommandJob::dispatch($machine->id, $this->remark, $this->userId)
                ->delay(now()->addSeconds($currentDelay));
        }
    }
}
