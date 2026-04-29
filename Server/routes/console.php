<?php

use App\Models\Company;
use App\Services\PriceGroupService;
use App\Services\SubscriptionPlanService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('price-groups:backfill-defaults', function () {
    $processed = 0;

    $this->info('Backfilling default price groups for existing companies...');

    Company::query()
        ->select(['id'])
        ->orderBy('id')
        ->chunkById(100, function ($companies) use (&$processed) {
            foreach ($companies as $company) {
                PriceGroupService::ensureDefaultsForCompany((int) $company->id);
                $processed++;
            }
        });

    $this->info("Completed. Backfilled defaults for {$processed} compan" . ($processed === 1 ? 'y' : 'ies') . '.');
})->purpose('Create missing default price groups for all existing companies');

Artisan::command('subscriptions:backfill-starter', function () {
    $processed = 0;
    $assigned = 0;

    $this->info('Backfilling Starter Essentials subscriptions for companies without subscriptions...');

    Company::query()
        ->select(['id'])
        ->orderBy('id')
        ->chunkById(100, function ($companies) use (&$processed, &$assigned) {
            foreach ($companies as $company) {
                $processed++;
                $before = \App\Models\Subscription::where('company_id', $company->id)->count();

                SubscriptionPlanService::ensureCompanyDefaultSubscription((int) $company->id);

                $after = \App\Models\Subscription::where('company_id', $company->id)->count();
                if ($after > $before) {
                    $assigned++;
                }
            }
        });

    $this->info("Completed. Processed {$processed} companies; assigned Starter Essentials to {$assigned} compan" . ($assigned === 1 ? 'y' : 'ies') . '.');
})->purpose('Assign Starter Essentials subscription to existing companies with no subscription');
