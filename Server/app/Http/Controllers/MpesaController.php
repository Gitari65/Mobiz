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
            $response = $this->mpesaService->initiateStkPush(
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
            $response = $this->mpesaService->queryStkStatus($validated['checkout_request_id']);

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

            return response()->json([
                'error' => 'Failed to query M-Pesa payment status',
                'message' => $e->getMessage(),
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

    private function logFailure(string $message, array $context = []): void
    {
        Log::warning('M-Pesa failure: ' . $message, $context);
    }
}