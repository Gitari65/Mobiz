<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\User;

class ExpenseSeeder extends Seeder
{
    public function run()
    {
        // Create a default user if none exists
        $user = User::first() ?? User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Sample agrovet expense data
        $expenses = [
            [
                'category' => 'animal_feed',
                'subcategory' => 'cattle_feed',
                'description' => 'Dairy meal and concentrate for 50 dairy cows',
                'amount' => 125000.00,
                'payment_method' => 'bank_transfer',
                'vendor_name' => 'Kenya Animal Feeds Ltd',
                'receipt_number' => 'KAF-2025-0089',
                'expense_date' => '2025-08-01',
                'status' => 'approved',
                'user_id' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'tax_amount' => 20000.00,
                'tax_rate' => 16.00,
            ],
            [
                'category' => 'veterinary_supplies',
                'subcategory' => 'vaccines',
                'description' => 'FMD and Newcastle disease vaccines',
                'amount' => 45000.00,
                'payment_method' => 'mobile_money',
                'vendor_name' => 'Veterinary Pharmaceuticals Ltd',
                'receipt_number' => 'VET-2025-0156',
                'expense_date' => '2025-08-05',
                'status' => 'approved',
                'user_id' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'tax_amount' => 7200.00,
                'tax_rate' => 16.00,
            ],
            [
                'category' => 'seeds_fertilizers',
                'subcategory' => 'crop_seeds',
                'description' => 'Hybrid maize seeds and certified beans',
                'amount' => 85000.00,
                'payment_method' => 'credit_card',
                'vendor_name' => 'Kenya Seed Company',
                'receipt_number' => 'KSC-2025-0234',
                'expense_date' => '2025-08-10',
                'status' => 'pending',
                'user_id' => $user->id,
                'tax_amount' => 13600.00,
                'tax_rate' => 16.00,
            ],
            [
                'category' => 'operational',
                'subcategory' => 'rent',
                'description' => 'Agrovet shop rent for August 2025',
                'amount' => 65000.00,
                'payment_method' => 'bank_transfer',
                'vendor_name' => 'Kiambu Properties Ltd',
                'receipt_number' => 'RENT-2025-08',
                'expense_date' => '2025-08-01',
                'status' => 'approved',
                'user_id' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'tax_amount' => 0.00,
                'tax_rate' => 0.00,
                'is_recurring' => true,
                'recurring_frequency' => 'monthly',
                'next_due_date' => '2025-09-01',
            ],
            [
                'category' => 'farm_equipment',
                'subcategory' => 'hand_tools',
                'description' => 'Farm tools: hoes, pangas, watering cans',
                'amount' => 28000.00,
                'payment_method' => 'cash',
                'vendor_name' => 'Farmers Choice Equipment',
                'receipt_number' => 'FCE-2025-0078',
                'expense_date' => '2025-08-12',
                'status' => 'approved',
                'user_id' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'tax_amount' => 4480.00,
                'tax_rate' => 16.00,
            ],
            [
                'category' => 'marketing',
                'subcategory' => 'farmer_education',
                'description' => 'Farmer training workshop on modern farming',
                'amount' => 35000.00,
                'payment_method' => 'mobile_money',
                'vendor_name' => 'Agricultural Extension Services',
                'receipt_number' => 'AES-2025-0012',
                'expense_date' => '2025-08-15',
                'status' => 'approved',
                'user_id' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'tax_amount' => 5600.00,
                'tax_rate' => 16.00,
            ],
            [
                'category' => 'staff',
                'subcategory' => 'salaries',
                'description' => 'Staff salaries for August 2025',
                'amount' => 180000.00,
                'payment_method' => 'bank_transfer',
                'vendor_name' => 'Staff Payroll',
                'receipt_number' => 'SAL-2025-08',
                'expense_date' => '2025-08-15',
                'status' => 'approved',
                'user_id' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'tax_amount' => 0.00,
                'tax_rate' => 0.00,
                'is_recurring' => true,
                'recurring_frequency' => 'monthly',
                'next_due_date' => '2025-09-15',
            ],
            [
                'category' => 'veterinary_supplies',
                'subcategory' => 'dewormers',
                'description' => 'Dewormers for cattle and goats',
                'amount' => 22000.00,
                'payment_method' => 'debit_card',
                'vendor_name' => 'Livestock Health Products',
                'receipt_number' => 'LHP-2025-0198',
                'expense_date' => '2025-08-14',
                'status' => 'pending',
                'user_id' => $user->id,
                'tax_amount' => 3520.00,
                'tax_rate' => 16.00,
            ],
            [
                'category' => 'others',
                'subcategory' => 'farmer_support',
                'description' => 'Support for local farmers cooperative',
                'amount' => 15000.00,
                'payment_method' => 'cash',
                'vendor_name' => 'Kiambu Farmers Cooperative',
                'receipt_number' => 'COOP-2025-0045',
                'expense_date' => '2025-08-16',
                'status' => 'approved',
                'user_id' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'tax_amount' => 0.00,
                'tax_rate' => 0.00,
            ]
        ];

        foreach ($expenses as $expense) {
            Expense::create($expense);
        }

        $this->command->info('Sample expense data created successfully!');
    }
}
