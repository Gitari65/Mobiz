<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\CompanySetting;

class MpesaService
{
    private ?int $companyId = null;

    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId;
    }

    private function getCompanyMpesaConfig(): ?array
    {
        if (!$this->companyId) {
            return null;
        }

        $setting = CompanySetting::where('company_id', $this->companyId)->first();
        
        if (!$setting || !$setting->mpesa_enabled || !$setting->mpesa_number) {
            return null;
        }

        return [
            'mpesa_number' => $setting->mpesa_number,
            'mpesa_type' => $setting->mpesa_type,
            'mpesa_business_name' => $setting->mpesa_business_name,
        ];
    }

    public function isConfigured(): bool
    {
        // Check company-specific config first
        $companyConfig = $this->getCompanyMpesaConfig();
        if ($companyConfig) {
            return true;
        }

        // Fall back to .env config for backward compatibility
        return (bool) config('services.mpesa.consumer_key')
            && (bool) config('services.mpesa.consumer_secret')
            && (bool) config('services.mpesa.shortcode')
            && (bool) config('services.mpesa.passkey');
    }

    public function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0')) {
            return '254' . substr($digits, 1);
        }

        if (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            return '254' . $digits;
        }

        if (str_starts_with($digits, '254')) {
            return $digits;
        }

        return $digits;
    }

    public function initiateStkPush(string $phoneNumber, float $amount, string $reference, string $description): array
    {
        $this->assertProductionSafeCallbackUrl();
        $token = $this->accessToken();
        $timestamp = now()->format('YmdHis');
        $shortcode = (string) config('services.mpesa.shortcode');
        $callbackUrl = $this->resolvedCallbackUrl();
        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => base64_encode($shortcode . config('services.mpesa.passkey') . $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => config('services.mpesa.transaction_type', 'CustomerPayBillOnline'),
            'Amount' => max(1, (int) round($amount)),
            'PartyA' => $this->normalizePhoneNumber($phoneNumber),
            'PartyB' => $shortcode,
            'PhoneNumber' => $this->normalizePhoneNumber($phoneNumber),
            'CallBackURL' => $callbackUrl,
            'AccountReference' => substr($reference, 0, 12),
            'TransactionDesc' => substr($description, 0, 13),
        ];

        try {
            $response = $this->makeHttpRequest(
                'post',
                rtrim(config('services.mpesa.base_url'), '/') . '/mpesa/stkpush/v1/processrequest',
                $payload,
                ['Authorization' => "Bearer $token"]
            );
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK push API call failed', [
                'message' => $e->getMessage(),
                'payload' => $payload,
                'normalized_phone' => $this->normalizePhoneNumber($phoneNumber),
                'amount' => max(1, (int) round($amount)),
                'reference' => substr($reference, 0, 12),
                'callback_url' => $callbackUrl,
                'base_url' => config('services.mpesa.base_url'),
            ]);
            throw $e;
        }

        Log::info('M-Pesa STK push initiated', ['response' => $response, 'payload' => $payload]);

        return $response;
    }

    public function queryStkStatus(string $checkoutRequestId): array
    {
        $token = $this->accessToken();
        $timestamp = now()->format('YmdHis');
        $shortcode = (string) config('services.mpesa.shortcode');

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => base64_encode($shortcode . config('services.mpesa.passkey') . $timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        try {
            $response = $this->makeHttpRequest(
                'post',
                rtrim(config('services.mpesa.base_url'), '/') . '/mpesa/stkpushquery/v1/query',
                $payload,
                ['Authorization' => "Bearer $token"]
            );
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK query API call failed', [
                'message' => $e->getMessage(),
                'checkout_request_id' => $checkoutRequestId,
            ]);
            throw $e;
        }

        Log::info('M-Pesa STK query response', ['response' => $response, 'checkout_request_id' => $checkoutRequestId]);

        return $response;
    }

    private function accessToken(): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('M-Pesa env constants are missing. Set the required MPESA_* values first.');
        }

        $credentials = base64_encode(config('services.mpesa.consumer_key') . ':' . config('services.mpesa.consumer_secret'));

        try {
            $response = $this->makeHttpRequest(
                'get',
                rtrim(config('services.mpesa.base_url'), '/') . '/oauth/v1/generate?grant_type=client_credentials',
                null,
                ['Authorization' => 'Basic ' . $credentials]
            );
        } catch (\Throwable $e) {
            Log::error('M-Pesa access token request failed', ['message' => $e->getMessage()]);
            throw $e;
        }

        if (empty($response['access_token'])) {
            throw new \RuntimeException('Unable to fetch M-Pesa access token');
        }

        return $response['access_token'];
    }

    private function resolvedCallbackUrl(): string
    {
        $callbackUrl = (string) config('services.mpesa.callback_url');
        $secret = (string) config('services.mpesa.callback_secret');

        if ($secret === '') {
            return $callbackUrl;
        }

        $separator = str_contains($callbackUrl, '?') ? '&' : '?';
        return $callbackUrl . $separator . 'token=' . urlencode($secret);
    }

    private function assertProductionSafeCallbackUrl(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $callbackUrl = (string) config('services.mpesa.callback_url');
        if ($callbackUrl === '') {
            throw new \RuntimeException('MPESA_CALLBACK_URL is required in production.');
        }

        $parts = parse_url($callbackUrl);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $disallowedHosts = ['localhost', '127.0.0.1', '0.0.0.0'];

        if ($scheme !== 'https' || in_array($host, $disallowedHosts, true)) {
            throw new \RuntimeException('MPESA_CALLBACK_URL must be a public HTTPS URL in production.');
        }
    }

    private function makeHttpRequest(string $method, string $url, ?array $data = null, array $headers = []): array
    {
        $client = Http::withHeaders(array_merge($headers, ['Content-Type' => 'application/json', 'Accept' => 'application/json']));

        // For sandbox/development, disable SSL verification to handle self-signed certificates
        if (!app()->environment('production')) {
            $client = $client->withoutVerifying();
        }

        try {
            $response = match ($method) {
                'post' => $client->post($url, $data ?? []),
                'get' => $client->get($url),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: $method"),
            };

            if ($response->failed()) {
                $errorBody = $response->body();
                Log::error('M-Pesa HTTP request failed', [
                    'url' => $url,
                    'method' => $method,
                    'status_code' => $response->status(),
                    'response_body' => $errorBody,
                    'headers' => $response->headers(),
                ]);
                throw new \RuntimeException("M-Pesa API returned status {$response->status()}: {$errorBody}");
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('M-Pesa HTTP request error', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}