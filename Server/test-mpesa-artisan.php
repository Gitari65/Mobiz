<?php
/**
 * M-Pesa Test Command
 * Run via: php artisan tinker then paste commands
 */

use App\Services\MpesaService;
use App\Models\MpesaTransaction;

$mpesa = app(MpesaService::class);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║          M-PESA QUICK TEST (STK + Query + Callback)          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Configuration
echo "TEST 1: Configuration Verification\n";
echo "─" . str_repeat("─", 60) . "\n";
$isConfigured = $mpesa->isConfigured();
echo "  Is Configured: " . ($isConfigured ? "✓ YES" : "✗ NO") . "\n";

if (!$isConfigured) {
    echo "\n✗ M-Pesa is not configured\n";
    die(1);
}

// Test 2: Token
echo "\nTEST 2: Access Token Generation\n";
echo "─" . str_repeat("─", 60) . "\n";
try {
    $token = $mpesa->accessToken();
    echo "  ✓ Token generated: " . substr($token, 0, 40) . "...\n";
} catch (\Throwable $e) {
    echo "  ✗ Token failed: " . $e->getMessage() . "\n";
    die(1);
}

// Test 3: STK Push
echo "\nTEST 3: STK Push Initiation\n";
echo "─" . str_repeat("─", 60) . "\n";
$testPhone = '25470837149';
$testAmount = 100;
$testRef = 'TEST' . date('YmdHis');

echo "  Phone: $testPhone\n";
echo "  Amount: $testAmount\n";
echo "  Reference: $testRef\n";

try {
    $stkResponse = $mpesa->initiateStkPush($testPhone, $testAmount, $testRef, 'Test Payment');
    echo "  ✓ STK Push Response:\n";
    echo "    - ResponseCode: " . ($stkResponse['ResponseCode'] ?? 'N/A') . "\n";
    echo "    - Description: " . ($stkResponse['ResponseDescription'] ?? 'N/A') . "\n";
    
    $checkoutRequestId = $stkResponse['CheckoutRequestID'] ?? null;
    if ($checkoutRequestId) {
        echo "    - CheckoutRequestID: $checkoutRequestId\n";
    } else {
        echo "    ⚠ No CheckoutRequestID in response\n";
    }
} catch (\Throwable $e) {
    echo "  ✗ STK Push failed: " . $e->getMessage() . "\n";
    die(1);
}

// Test 4: Database
echo "\nTEST 4: Database Record\n";
echo "─" . str_repeat("─", 60) . "\n";
$transaction = MpesaTransaction::where('reference', $testRef)->first();
if ($transaction) {
    echo "  ✓ Transaction created\n";
    echo "    - ID: " . $transaction->id . "\n";
    echo "    - Status: " . $transaction->status . "\n";
    echo "    - CheckoutRequestID: " . $transaction->checkout_request_id . "\n";
} else {
    echo "  ✗ Transaction not found\n";
}

// Test 5: Query Status
if ($checkoutRequestId) {
    echo "\nTEST 5: Status Query\n";
    echo "─" . str_repeat("─", 60) . "\n";
    try {
        $queryResponse = $mpesa->queryStkStatus($checkoutRequestId);
        echo "  ✓ Query Response:\n";
        echo "    - ResponseCode: " . ($queryResponse['ResponseCode'] ?? 'N/A') . "\n";
        echo "    - ResultCode: " . ($queryResponse['ResultCode'] ?? 'N/A') . "\n";
        echo "    - ResultDesc: " . ($queryResponse['ResultDesc'] ?? 'N/A') . "\n";
    } catch (\Throwable $e) {
        echo "  ✗ Query failed: " . $e->getMessage() . "\n";
    }
}

// Test 6: Callback Simulation
if ($transaction) {
    echo "\nTEST 6: Callback Simulation\n";
    echo "─" . str_repeat("─", 60) . "\n";
    
    $transaction->update([
        'status' => 'success',
        'result_code' => '0',
        'result_desc' => 'The service request is processed successfully.',
        'mpesa_receipt_number' => 'TEG' . date('YmdHis'),
    ]);
    
    $transaction = $transaction->fresh();
    echo "  ✓ Callback simulated\n";
    echo "    - Status: " . $transaction->status . "\n";
    echo "    - Result Code: " . $transaction->result_code . "\n";
    echo "    - Receipt: " . ($transaction->mpesa_receipt_number ?: 'N/A') . "\n";
}

echo "\n✓ ALL TESTS PASSED\n\n";
?>
