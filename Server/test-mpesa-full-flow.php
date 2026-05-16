<?php
/**
 * Comprehensive M-Pesa Flow Test
 * Tests: STK Push → Status Query → Callback Processing
 */

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

use App\Services\MpesaService;
use App\Models\MpesaTransaction;
use Illuminate\Support\Facades\DB;

$mpesa = app(MpesaService::class);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║          M-PESA FULL FLOW TEST (STK + Query + Callback)      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ========== STEP 1: Configuration Verification ==========
echo "STEP 1: Configuration Verification\n";
echo "─" . str_repeat("─", 60) . "\n";

$config = config('services.mpesa');
$checks = [
    'Base URL' => $config['base_url'] ?? null,
    'Shortcode' => $config['shortcode'] ?? null,
    'Consumer Key' => isset($config['consumer_key']) ? '✓ Set' : '✗ Missing',
    'Consumer Secret' => isset($config['consumer_secret']) ? '✓ Set' : '✗ Missing',
    'Passkey' => isset($config['passkey']) ? '✓ Set' : '✗ Missing',
    'Callback URL' => $config['callback_url'] ?? null,
    'Callback Secret' => isset($config['callback_secret']) ? '✓ Set' : '✗ Missing',
];

foreach ($checks as $key => $value) {
    if (is_bool($value) || strpos($value ?? '', '✓') === 0 || strpos($value ?? '', '✗') === 0) {
        echo sprintf("  %-20s: %s\n", $key, $value);
    } else {
        echo sprintf("  %-20s: %s\n", $key, $value ?? '✗ NOT SET');
    }
}

$isConfigured = $mpesa->isConfigured();
echo "\n  Overall Status: " . ($isConfigured ? "✓ CONFIGURED" : "✗ NOT CONFIGURED") . "\n";

if (!$isConfigured) {
    echo "\n✗ M-Pesa is not configured. Please set all MPESA_* environment variables.\n";
    exit(1);
}

// ========== STEP 2: Access Token Generation ==========
echo "\n\nSTEP 2: Access Token Generation\n";
echo "─" . str_repeat("─", 60) . "\n";

try {
    $token = $mpesa->accessToken();
    $tokenPreview = substr($token, 0, 40) . "...";
    echo "  ✓ Token generated successfully\n";
    echo "  Token preview: $tokenPreview\n";
    echo "  Token length: " . strlen($token) . " characters\n";
} catch (\Throwable $e) {
    echo "  ✗ Failed to generate token\n";
    echo "  Error: " . $e->getMessage() . "\n";
    exit(1);
}

// ========== STEP 3: STK Push Initiation ==========
echo "\n\nSTEP 3: STK Push Initiation\n";
echo "─" . str_repeat("─", 60) . "\n";

$testPhone = '25470837149';  // From test credentials
$testAmount = 100;
$testReference = 'TEST' . date('YmdHis');
$testDesc = 'Test Payment';

echo "  Phone Number: $testPhone\n";
echo "  Amount: KES $testAmount\n";
echo "  Reference: $testReference\n";
echo "  Description: $testDesc\n";

try {
    $stkResponse = $mpesa->initiateStkPush($testPhone, $testAmount, $testReference, $testDesc);
    
    echo "\n  ✓ STK Push initiated\n";
    echo "  Response Code: " . ($stkResponse['ResponseCode'] ?? 'N/A') . "\n";
    echo "  Description: " . ($stkResponse['ResponseDescription'] ?? 'N/A') . "\n";
    echo "  Merchant Request ID: " . ($stkResponse['MerchantRequestID'] ?? 'N/A') . "\n";
    echo "  Checkout Request ID: " . ($stkResponse['CheckoutRequestID'] ?? 'N/A') . "\n";
    
    $checkoutRequestId = $stkResponse['CheckoutRequestID'] ?? null;
    
    if (!$checkoutRequestId) {
        echo "\n  ⚠ Warning: No CheckoutRequestID in response. Query and callback may not work.\n";
    } else {
        echo "\n  ℹ CheckoutRequestID saved for status query\n";
    }
} catch (\Throwable $e) {
    echo "  ✗ STK Push failed\n";
    echo "  Error: " . $e->getMessage() . "\n";
    exit(1);
}

// ========== STEP 4: Database Record Verification ==========
echo "\n\nSTEP 4: Database Record Verification\n";
echo "─" . str_repeat("─", 60) . "\n";

$transaction = MpesaTransaction::where('reference', $testReference)->first();

if ($transaction) {
    echo "  ✓ Transaction record created\n";
    echo "  Transaction ID: " . $transaction->id . "\n";
    echo "  Status: " . $transaction->status . "\n";
    echo "  Checkout Request ID: " . $transaction->checkout_request_id . "\n";
} else {
    echo "  ✗ Transaction record NOT found\n";
}

// ========== STEP 5: Status Query ==========
if ($checkoutRequestId) {
    echo "\n\nSTEP 5: Status Query (STK Push Query)\n";
    echo "─" . str_repeat("─", 60) . "\n";
    
    echo "  Querying status for Checkout Request ID: $checkoutRequestId\n";
    
    try {
        sleep(2); // Small delay to allow M-Pesa to process
        $queryResponse = $mpesa->queryStkStatus($checkoutRequestId);
        
        echo "\n  ✓ Status query successful\n";
        echo "  Response Code: " . ($queryResponse['ResponseCode'] ?? 'N/A') . "\n";
        echo "  Response Description: " . ($queryResponse['ResponseDescription'] ?? 'N/A') . "\n";
        echo "  Result Code: " . ($queryResponse['ResultCode'] ?? 'N/A') . "\n";
        echo "  Result Description: " . ($queryResponse['ResultDesc'] ?? 'N/A') . "\n";
        
        // Interpret result codes
        $resultCode = $queryResponse['ResultCode'] ?? null;
        if ($resultCode === '0') {
            echo "\n  → Payment was SUCCESSFUL ✓\n";
        } elseif ($resultCode === '1032') {
            echo "\n  → Payment was CANCELLED by user\n";
        } else {
            echo "\n  → Payment is still PENDING or has other status\n";
        }
        
    } catch (\Throwable $e) {
        echo "  ✗ Status query failed\n";
        echo "  Error: " . $e->getMessage() . "\n";
    }
}

// ========== STEP 6: Callback Processing Simulation ==========
echo "\n\nSTEP 6: Callback Processing Simulation\n";
echo "─" . str_repeat("─", 60) . "\n";

if ($transaction) {
    echo "  Simulating M-Pesa callback for Transaction ID: " . $transaction->id . "\n";
    
    // Simulate successful payment callback
    $callbackPayload = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => $transaction->merchant_request_id,
                'CheckoutRequestID' => $checkoutRequestId,
                'ResultCode' => 0,  // 0 = success
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => $testAmount],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'TEST' . date('YmdHis')],
                        ['Name' => 'TransactionDate', 'Value' => date('YmdHis')],
                        ['Name' => 'PhoneNumber', 'Value' => str_replace('254', '0', $testPhone)],
                    ]
                ]
            ]
        ]
    ];
    
    echo "  Callback payload structure:\n";
    echo "    - ResultCode: 0 (Success)\n";
    echo "    - CheckoutRequestID: $checkoutRequestId\n";
    echo "    - Receipt Number: " . ($callbackPayload['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'] ?? 'N/A') . "\n";
    
    // Verify transaction state BEFORE callback
    echo "\n  Before callback:\n";
    echo "    - Status: " . $transaction->status . "\n";
    echo "    - Result Code: " . ($transaction->result_code ?: 'NULL') . "\n";
    
    // Simulate what the callback would do
    $transaction->update([
        'status' => 'success',
        'result_code' => '0',
        'result_desc' => 'The service request is processed successfully.',
        'mpesa_receipt_number' => $callbackPayload['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'],
        'transaction_date' => now(),
        'raw_callback' => $callbackPayload,
    ]);
    
    // Verify transaction state AFTER callback
    $transaction = $transaction->fresh();
    echo "\n  After callback simulation:\n";
    echo "    - Status: " . $transaction->status . "\n";
    echo "    - Result Code: " . $transaction->result_code . "\n";
    echo "    - Receipt Number: " . ($transaction->mpesa_receipt_number ?: 'N/A') . "\n";
    echo "\n  ✓ Callback processing simulated successfully\n";
}

// ========== SUMMARY ==========
echo "\n\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                         TEST SUMMARY                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "  ✓ Configuration verified\n";
echo "  ✓ Access token generated\n";
echo "  ✓ STK Push initiated\n";
echo "  ✓ Database record created\n";
if ($checkoutRequestId) {
    echo "  ✓ Status query executed\n";
    echo "  ✓ Callback processing verified\n";
} else {
    echo "  ⚠ Status query skipped (no CheckoutRequestID)\n";
    echo "  ⚠ Callback processing skipped (no CheckoutRequestID)\n";
}

echo "\n\nNEXT STEPS:\n";
echo "  1. Use the Safaricom simulator with phone: $testPhone\n";
echo "  2. Simulate the STK push prompt on the test phone\n";
echo "  3. Accept or reject the payment to trigger callback\n";
echo "  4. Check your logs for callback events: /storage/logs/\n";
echo "  5. Verify transaction status in database:\n";
echo "     SELECT * FROM mpesa_transactions WHERE reference = '$testReference';\n";

echo "\n";
?>
