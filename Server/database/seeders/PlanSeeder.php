<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // All available features in the system
        $allFeatures = [
            'sales',
            'products',
            'mpesa',
            'credit',
            'promotions',
            'inventory',
            'purchases',
            'warehouse',
            'stock_transfers',
            'customer_management',
            'suppliers',
            'sms',
            'tax_configuration',
            'expenses',
            'invoicing',
            'price_groups',
            'accounts',
            'reports',
            'data_export',
            'audit_logs',
            'user_management',
            'printer_config',
            'returns',
            'advanced_settings',
            'admin_customization',
        ];

        // Essential Plan - Everything except mpesa, sms, and promotions
        $essentialFeatures = array_diff($allFeatures, ['mpesa', 'sms', 'promotions']);

        Plan::firstOrCreate(
            ['slug' => 'essential'],
            [
                'name' => 'Essential',
                'description' => 'Core features for small businesses',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'features' => array_values($essentialFeatures),
                'is_active' => true,
            ]
        );

        // Professional Plan - Essential + mpesa, sms, promotions
        $professionalFeatures = array_merge($essentialFeatures, ['mpesa', 'sms', 'promotions']);

        Plan::firstOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'description' => 'Enhanced features with mobile payments and promotions',
                'price' => 5000,
                'billing_cycle' => 'monthly',
                'features' => array_values($professionalFeatures),
                'is_active' => true,
            ]
        );

        // Enterprise Plan - All features
        Plan::firstOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'Full feature set with all system capabilities',
                'price' => 15000,
                'billing_cycle' => 'monthly',
                'features' => $allFeatures,
                'is_active' => true,
            ]
        );
    }
}
