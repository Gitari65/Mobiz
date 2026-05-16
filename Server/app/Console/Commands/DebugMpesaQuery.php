<?php

namespace App\Console\Commands;

use App\Services\MpesaService;
use App\Models\MpesaTransaction;
use Illuminate\Console\Command;

class DebugMpesaQuery extends Command
{
    protected $signature = 'mpesa:debug-query {--checkout_id=}';
    protected $description = 'Debug M-Pesa status query issues';

    public function handle()
    {
        $this->line('');
        $this->info('M-Pesa Status Query Debugging');
        $this->line('─' . str_repeat('─', 60));
        $this->line('');

        // Check if we have a transaction to query
        $checkoutId = $this->option('checkout_id');
        
        if (!$checkoutId) {
            $this->info('Available M-Pesa Transactions:');
            $this->line('');
            
            $transactions = MpesaTransaction::latest()->limit(5)->get();
            
            if ($transactions->isEmpty()) {
                $this->warn('No M-Pesa transactions found in database.');
                $this->line('');
                $this->line('Run: php artisan mpesa:test-flow');
                $this->line('Then use: php artisan mpesa:debug-query --checkout_id=<ID>');
                return;
            }

            foreach ($transactions as $txn) {
                $this->line("Checkout ID: {$txn->checkout_request_id}");
                $this->line("  - Amount: {$txn->amount}");
                $this->line("  - Status: {$txn->status}");
                $this->line("  - Company: {$txn->company_id}");
                $this->line("  - User: {$txn->user_id}");
                $this->line('');
            }

            $this->line("To debug a transaction, run:");
            $this->line("  php artisan mpesa:debug-query --checkout_id=" . $transactions->first()->checkout_request_id);
            return;
        }

        // Debug the specific query
        $this->line("Debugging Query for Checkout ID: $checkoutId");
        $this->line('');

        // Find transaction
        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutId)->first();
        
        if (!$transaction) {
            $this->error("✗ Transaction NOT found");
            $this->line('');
            $this->line('Checking similar transactions:');
            $similar = MpesaTransaction::where('checkout_request_id', 'like', '%' . substr($checkoutId, 0, 10) . '%')->limit(3)->get();
            
            if ($similar->isNotEmpty()) {
                foreach ($similar as $txn) {
                    $this->line("  - {$txn->checkout_request_id}");
                }
            } else {
                $this->line('  (none found)');
            }
            return 1;
        }

        $this->info('✓ Transaction found in database');
        $this->line('');
        $this->line('Transaction Details:');
        $this->line('  ID: ' . $transaction->id);
        $this->line('  Phone: ' . $transaction->phone_number);
        $this->line('  Amount: ' . $transaction->amount);
        $this->line('  Status: ' . $transaction->status);
        $this->line('  Company ID: ' . $transaction->company_id);
        $this->line('  User ID: ' . $transaction->user_id);
        $this->line('  Merchant Request ID: ' . ($transaction->merchant_request_id ?: 'NULL'));
        $this->line('');

        // Now try the query
        $this->line('Attempting M-Pesa Status Query...');
        $this->line('');

        $mpesa = app(MpesaService::class);

        try {
            $response = $mpesa->queryStkStatus($checkoutId);

            $this->info('✓ Query successful');
            $this->line('');
            $this->line('M-Pesa Response:');
            $this->line('  ResponseCode: ' . ($response['ResponseCode'] ?? 'N/A'));
            $this->line('  ResponseDescription: ' . ($response['ResponseDescription'] ?? 'N/A'));
            $this->line('  ResultCode: ' . ($response['ResultCode'] ?? 'N/A'));
            $this->line('  ResultDesc: ' . ($response['ResultDesc'] ?? 'N/A'));
            $this->line('');

            // Interpret the result
            $resultCode = $response['ResultCode'] ?? null;
            if ($resultCode === '0') {
                $this->info('→ Payment SUCCESSFUL');
            } elseif ($resultCode === '1032') {
                $this->warn('→ Payment CANCELLED by user');
            } elseif ($resultCode === '1037') {
                $this->line('→ Payment TIMEOUT (no user response)');
            } else {
                $this->warn("→ Payment status: $resultCode");
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error('✗ Query failed');
            $this->line('');
            $this->line('Error: ' . $e->getMessage());
            $this->line('');
            $this->line('Possible causes:');
            $this->line('  1. Invalid CheckoutRequestID format');
            $this->line('  2. M-Pesa API connection issue');
            $this->line('  3. Invalid credentials in config');
            $this->line('  4. Timeout from M-Pesa');

            return 1;
        }
    }
}
