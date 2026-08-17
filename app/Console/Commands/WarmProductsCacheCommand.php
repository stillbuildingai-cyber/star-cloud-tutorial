<?php

namespace App\Console\Commands;

use App\Models\System\Company;
use App\Services\Product\ProductCatalogService;
use Illuminate\Console\Command;

class WarmProductsCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:warm-cache {--company= : ID of the specific company to warm cache for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-warm the Redis cache for product catalogs of all or a specific company.';

    /**
     * Execute the console command.
     *
     * @param ProductCatalogService $catalogService
     * @return int
     */
    public function handle(ProductCatalogService $catalogService): int
    {
        $companyId = $this->option('company');

        if ($companyId) {
            $companies = Company::where('id', $companyId)->pluck('id');
        } else {
            // Only warm companies that actually have products to avoid empty cache entries
            $companies = Company::whereHas('products')->pluck('id');
        }

        if ($companies->isEmpty()) {
            $this->warn('No companies found with products to warm cache for.');
            return self::SUCCESS;
        }

        $this->info("Starting to warm product catalog cache for {$companies->count()} companies...");
        $bar = $this->output->createProgressBar($companies->count());
        $bar->start();

        foreach ($companies as $id) {
            $catalogService->rebuildCache($id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Product catalog cache warming completed!');

        return self::SUCCESS;
    }
}
