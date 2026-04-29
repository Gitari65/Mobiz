<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionPlanService
{
    public const STARTER_PLAN_SLUG = 'starter-essentials';
    public const GROWTH_PLAN_SLUG = 'growth-plus';

    /**
     * Ensure system plans exist with clear naming.
     */
    public static function ensureDefaultPlans(): void
    {
        Plan::updateOrCreate(
            ['slug' => self::STARTER_PLAN_SLUG],
            [
                'name' => 'Starter Essentials',
                'description' => 'Core POS setup for daily operations: sales, inventory, customers, and reporting.',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'features' => [
                    'sales',
                    'inventory',
                    'customer_management',
                    'reports',
                ],
                'is_active' => true,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => self::GROWTH_PLAN_SLUG],
            [
                'name' => 'Growth Plus',
                'description' => 'Includes Starter Essentials plus M-Pesa, promotions, and SMS capabilities.',
                'price' => 3500,
                'billing_cycle' => 'monthly',
                'features' => [
                    'sales',
                    'inventory',
                    'customer_management',
                    'reports',
                    'mpesa',
                    'promotions',
                    'sms',
                ],
                'is_active' => true,
            ]
        );
    }

    /**
     * Ensure a company has an initial active subscription on the starter plan.
     */
    public static function ensureCompanyDefaultSubscription(?int $companyId): void
    {
        if (!$companyId) {
            return;
        }

        self::ensureDefaultPlans();

        $starterPlan = Plan::where('slug', self::STARTER_PLAN_SLUG)->first();
        if (!$starterPlan) {
            return;
        }

        $existing = Subscription::where('company_id', $companyId)->latest('id')->first();
        if ($existing) {
            return;
        }

        Subscription::create([
            'company_id' => $companyId,
            'plan_id' => $starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => null,
            'trial_ends_at' => null,
            'on_trial' => false,
            'monthly_fee' => $starterPlan->price,
        ]);
    }
}
