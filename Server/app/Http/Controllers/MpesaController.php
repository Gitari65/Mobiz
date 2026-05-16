<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(private MpesaService $mpesaService)
    {
    }

    public function stkPush(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1',
            'account_reference' => 'required|string|max:50',
            'transaction_desc' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        if (! $user || ! $user->company_id) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            // Create service with company_id for company-specific config
            $mpesaService = new MpesaService($user->company_id);
            $response = $mpesaService->initiateStkPush(
                $validated['phone_number'],
                (float) $validated['amount'],
                $validated['account_reference'],
                $validated['transaction_desc'] ?? 'POS Payment'
            );

            $isAccepted = ((string) ($response['ResponseCode'] ?? '')) === '0'
                && !empty($response['CheckoutRequestID']);

            $transaction = MpesaTransaction::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'phone_number' => $this->mpesaService->normalizePhoneNumber($validated['phone_number']),
                'amount' => (float) $validated['amount'],
                'reference' => $validated['account_reference'],
                'description' => $validated['transaction_desc'] ?? 'POS Payment',
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                'status' => $isAccepted ? 'pending' : 'failed',
                'result_code' => (string) ($response['ResponseCode'] ?? ''),
                'result_desc' => $response['CustomerMessage'] ?? ($response['ResponseDescription'] ?? null),
            ]);

            if (!$isAccepted) {
                $this->logFailure('STK push rejected by provider', [
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'response' => $response,
                ]);

                return response()->json([
                    'error' => 'M-Pesa STK request was not accepted by provider',
                    'transaction' => $transaction,
                    'provider_response' => $response,
                ], 400);
            }

            return response()->json([
                'message' => $response['CustomerMessage'] ?? 'M-Pesa request sent successfully',
                'transaction' => $transaction,
                'provider_response' => $response,
            ]);
        } catch (\Throwable $e) {
            $this->logFailure('STK push failed', [
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'phone' => $validated['phone_number'],
                'amount' => (float) $validated['amount'],
                'reference' => $validated['account_reference'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to initiate M-Pesa STK push',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function stkQuery(Request $request)
    {
        $validated = $request->validate([
            'checkout_request_id' => 'required|string',
        ]);

        $user = $request->user();
        $transaction = MpesaTransaction::where('company_id', $user?->company_id)
            ->where('checkout_request_id', $validated['checkout_request_id'])
            ->firstOrFail();

        try {
            // Create service with company_id for company-specific config
            $mpesaService = new MpesaService($user->company_id);
            $response = $mpesaService->queryStkStatus($validated['checkout_request_id']);

            if (array_key_exists('ResultCode', $response)) {
                $transaction->result_code = (string) $response['ResultCode'];
                $transaction->result_desc = $response['ResultDesc'] ?? $transaction->result_desc;
                $transaction->status = ((string) $response['ResultCode'] === '0') ? 'success' : 'failed';
                $transaction->save();

                if ((string) $response['ResultCode'] !== '0') {
                    $this->logFailure('STK query returned non-success result', [
                        'transaction_id' => $transaction->id,
                        'checkout_request_id' => $validated['checkout_request_id'],
                        'provider_response' => $response,
                    ]);
                }
            }

            return response()->json([
                'transaction' => $transaction->fresh(),
                'provider_response' => $response,
            ]);
        } catch (\Throwable $e) {
            $this->logFailure('STK query failed', [
                'transaction_id' => $transaction->id,
                'checkout_request_id' => $validated['checkout_request_id'],
                'error' => $e->getMessage(),
            ]);

            // Check if it's a rate limit error
            if (strpos($e->getMessage(), '429') !== false || strpos($e->getMessage(), 'Spike arrest') !== false) {
                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'message' => 'Too many requests. Please wait a moment before trying again.',
                    'retry_after' => 60,
                    'transaction' => $transaction,
                ], 429);
            }

            return response()->json([
                'error' => 'Failed to query M-Pesa payment status',
                'message' => $e->getMessage(),
                'transaction' => $transaction,
            ], 500);
        }
    }

    public function show(Request $request, string $checkoutRequestId)
    {
        $user = $request->user();
        $transaction = MpesaTransaction::where('company_id', $user?->company_id)
            ->where('checkout_request_id', $checkoutRequestId)
            ->firstOrFail();

        return response()->json(['transaction' => $transaction]);
    }

    public function callback(Request $request)
    {
        $configuredSecret = (string) config('services.mpesa.callback_secret');
        if ($configuredSecret !== '') {
            $providedToken = (string) ($request->query('token') ?? $request->header('X-Mpesa-Callback-Token') ?? '');
            if (!hash_equals($configuredSecret, $providedToken)) {
                $this->logFailure('Rejected callback with invalid callback secret', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Unauthorized callback'], 403);
            }
        }

        $payload = $request->all();
        $stkCallback = data_get($payload, 'Body.stkCallback', []);
        $checkoutRequestId = data_get($stkCallback, 'CheckoutRequestID');

        if (! $checkoutRequestId) {
            Log::warning('Invalid M-Pesa callback payload', ['payload' => $payload]);
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback payload']);
        }

        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();
        if (! $transaction) {
            Log::warning('M-Pesa callback for unknown checkout request', ['checkout_request_id' => $checkoutRequestId]);
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $items = collect(data_get($stkCallback, 'CallbackMetadata.Item', []));
        $receiptNumber = optional($items->firstWhere('Name', 'MpesaReceiptNumber'))['Value'] ?? null;
        $transactionDate = optional($items->firstWhere('Name', 'TransactionDate'))['Value'] ?? null;

        $transaction->update([
            'status' => ((string) data_get($stkCallback, 'ResultCode') === '0') ? 'success' : 'failed',
            'result_code' => (string) data_get($stkCallback, 'ResultCode'),
            'result_desc' => data_get($stkCallback, 'ResultDesc'),
            'mpesa_receipt_number' => $receiptNumber,
            'transaction_date' => $transactionDate ? now()->createFromFormat('YmdHis', (string) $transactionDate) : null,
            'raw_callback' => $payload,
        ]);

        if ($transaction->sale_id) {
            $transaction->sale()->update([
                'mpesa_receipt_number' => $transaction->mpesa_receipt_number,
            ]);
        }

        if ($transaction->status !== 'success') {
            $this->logFailure('M-Pesa callback indicates failed payment', [
                'transaction_id' => $transaction->id,
                'checkout_request_id' => $checkoutRequestId,
                'result_code' => $transaction->result_code,
                'result_desc' => $transaction->result_desc,
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function testCallback(Request $request)
    {
        // This endpoint is only available in non-production environments for testing
        if (app()->environment('production')) {
            return response()->json(['error' => 'Test callbacks are not available in production'], 403);
        }

        $validated = $request->validate([
            'checkout_request_id' => 'required|string',
            'result_code' => 'required|in:0,1032,1,2',  // 0=success, 1032=user cancelled, other=failed
            'result_desc' => 'nullable|string',
            'receipt_number' => 'nullable|string',
        ]);

        $transaction = MpesaTransaction::where('checkout_request_id', $validated['checkout_request_id'])->first();
        
        if (!$transaction) {
            return response()->json([
                'error' => 'Transaction not found for checkout request ID',
                'checkout_request_id' => $validated['checkout_request_id']
            ], 404);
        }

        // Simulate M-Pesa callback payload
        $testPayload = [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => $transaction->merchant_request_id,
                    'CheckoutRequestID' => $validated['checkout_request_id'],
                    'ResultCode' => $validated['result_code'],
                    'ResultDesc' => $validated['result_desc'] ?? $this->getResultDesc($validated['result_code']),
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => $transaction->amount],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => $validated['receipt_number'] ?? 'TEST' . date('YmdHis')],
                            ['Name' => 'TransactionDate', 'Value' => date('YmdHis')],
                            ['Name' => 'PhoneNumber', 'Value' => str_replace('254', '0', $transaction->phone_number)],
                        ]
                    ]
                ]
            ]
        ];

        // Update transaction as if callback was received
        $transaction->update([
            'status' => ((string) $validated['result_code'] === '0') ? 'success' : 'failed',
            'result_code' => (string) $validated['result_code'],
            'result_desc' => $validated['result_desc'] ?? $this->getResultDesc($validated['result_code']),
            'mpesa_receipt_number' => $validated['receipt_number'] ?? 'TEST' . date('YmdHis'),
            'transaction_date' => now(),
            'raw_callback' => $testPayload,
        ]);

        if ($transaction->sale_id) {
            $transaction->sale()->update([
                'mpesa_receipt_number' => $transaction->mpesa_receipt_number,
            ]);
        }

        Log::info('M-Pesa test callback processed', [
            'transaction_id' => $transaction->id,
            'checkout_request_id' => $validated['checkout_request_id'],
            'result_code' => $validated['result_code'],
            'is_test' => true,
        ]);

        return response()->json([
            'message' => 'Test callback processed successfully',
            'transaction' => $transaction->fresh(),
            'simulated_payload' => $testPayload,
        ]);
    }

    private function getResultDesc(string $resultCode): string
    {
        return match ($resultCode) {
            '0' => 'The service request is processed successfully.',
            '1032' => 'Request canceled by the user',
            '1' => 'An error occurred during processing',
            '2' => 'The initiator information is invalid',
            default => 'Unknown result code',
        };
    }

    private function logFailure(string $message, array $context = []): void
    {
        Log::warning('M-Pesa failure: ' . $message, $context);
    }
}