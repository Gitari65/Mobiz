<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaxConfiguration;
use Illuminate\Support\Facades\DB;

class CleanupTaxConfigurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tax:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up company-specific duplicate tax configs (Standard VAT, Zero-Rated, Exempt)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting tax configurations cleanup...');

        $defaultNames = ['Standard VAT', 'Zero-Rated', 'Exempt'];

        $companies = DB::table('companies')->select('id')->get();
        $globalDefaults = TaxConfiguration::whereNull('company_id')
            ->whereIn('name', $defaultNames)
            ->get();

        if ($globalDefaults->count() !== 3) {
            $this->warn('Global default set incomplete. Aborting cleanup.');
            return Command::FAILURE;
        }

        $deleted = 0;
        $archived = 0;

        foreach ($companies as $company) {
            $companyId = $company->id;
            $dupes = TaxConfiguration::where('company_id', $companyId)
                ->whereIn('name', $defaultNames)
                ->get();

            foreach ($dupes as $tax) {
                if ($tax->products()->exists()) {
                    $tax->update([
                        'is_active' => false,
                        'is_default' => false,
                        'description' => trim(($tax->description ?? '') . "\n[Archived] Replaced by global defaults"),
                    ]);
                    $archived++;
                    $this->line("Archived in-use config: #{$tax->id} ({$tax->name}) for company {$companyId}");
                } else {
                    $tax->delete();
                    $deleted++;
                    $this->line("Deleted unused duplicate: #{$tax->id} ({$tax->name}) for company {$companyId}");
                }
            }
        }

        $this->info("Cleanup complete. Deleted {$deleted}, archived {$archived}.");
        return Command::SUCCESS;
    }
}
