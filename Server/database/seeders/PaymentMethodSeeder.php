<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Cash',
                'description' => 'Cash payment',
                'is_active' => true,
                'mpesa_type' => null,
                'mpesa_number' => null,
            ],
            [
                'name' => 'M-Pesa',
                'description' => 'Mobile money payment via M-Pesa',
                'is_active' => true,
                'mpesa_type' => 'till',
                'mpesa_number' => null,
            ],
            [
                'name' => 'Bank Transfer',
                'description' => 'Direct bank transfer',
                'is_active' => true,
                'mpesa_type' => null,
                'mpesa_number' => null,
            ],
            [
                'name' => 'Credit Card',
                'description' => 'Credit or Debit card payment',
                'is_active' => true,
                'mpesa_type' => null,
                'mpesa_number' => null,
            ],
            [
                'name' => 'Debit Card',
                'description' => 'Debit card payment',
                'is_active' => true,
                'mpesa_type' => null,
                'mpesa_number' => null,
            ],
            [
                'name' => 'Cheque',
                'description' => 'Cheque payment',
                'is_active' => true,
                'mpesa_type' => null,
                'mpesa_number' => null,
            ],
            [
                'name' => 'Credit/Invoice',
                'description' => 'Payment on credit or invoice',
                'is_active' => true,
                'mpesa_type' => null,
                'mpesa_number' => null,
            ],
            [
                'name' => 'PayPal',
                'description' => 'PayPal online payment',
                'is_active' => true,
                'mpesa_type' => null,
                'mpesa_number' => null,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['name' => $method['name']],
                $method
            );
        }

        $this->command->info('Payment methods seeded successfully!');
    }
}
