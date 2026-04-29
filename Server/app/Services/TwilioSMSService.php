<?php

namespace App\Services;

use App\Models\MessageLog;
use Illuminate\Support\Facades\Log;

/**
 * SMS Service using Twilio
 * 
 * Configuration:
 * TWILIO_ACCOUNT_SID=your_account_sid
 * TWILIO_AUTH_TOKEN=your_auth_token
 * TWILIO_PHONE_NUMBER=+1234567890 (your Twilio number)
 * TWILIO_SANDBOX_MODE=true (set to false for production)
 * 
 * Sandbox Mode:
 * - Free SMS to verified phone numbers only
 * - Limited testing capability
 * - Perfect for development and testing
 * 
 * Get Twilio Sandbox:
 * 1. Sign up at https://www.twilio.com/
 * 2. Verify your phone number in console
 * 3. Use your Twilio trial number
 */

class TwilioSMSService
{
    private $accountSid;
    private $authToken;
    private $phoneNumber;
    private $sandboxMode;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->phoneNumber = config('services.twilio.phone_number');
        $this->sandboxMode = config('services.twilio.sandbox_mode', true);

        if (!$this->accountSid || !$this->authToken || !$this->phoneNumber) {
            Log::warning('Twilio SMS Service: Missing configuration. Check services.twilio config.');
        }
    }

    /**
     * Send SMS via Twilio
     */
    public function send(string $recipientPhone, string $message, array $metadata = []): MessageLog
    {
        try {
            // Normalize phone number
            $recipientPhone = $this->normalizePhoneNumber($recipientPhone);

            // Create pending message log
            $messageLog = MessageLog::create([
                'company_id' => auth()->user()?->company_id,
                'sent_by_user_id' => auth()->id(),
                'type' => 'sms',
                'recipient_contact' => $recipientPhone,
                'body' => $message,
                'status' => 'pending',
                'metadata' => $metadata,
            ]);

            if (!$this->isConfigured()) {
                throw new \Exception('Twilio SMS Service is not configured. Please add TWILIO credentials to .env');
            }

            // Build message with sandbox prefix if in sandbox mode
            $finalMessage = $this->sandboxMode 
                ? "[SANDBOX] " . substr($message, 0, 100)
                : $message;

            // Call Twilio API
            $response = $this->makeRequest(
                "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json",
                [
                    'From' => $this->phoneNumber,
                    'To' => $recipientPhone,
                    'Body' => $finalMessage,
                ]
            );

            if ($response && isset($response['sid'])) {
                $messageLog->markAsSent($response['sid']);
                Log::info("SMS sent via Twilio", [
                    'message_log_id' => $messageLog->id,
                    'twilio_sid' => $response['sid'],
                    'recipient' => $recipientPhone,
                ]);
            } else {
                throw new \Exception('Twilio API returned invalid response');
            }

        } catch (\Exception $e) {
            Log::error("SMS sending failed: " . $e->getMessage());
            if (isset($messageLog)) {
                $messageLog->markAsFailed($e->getMessage());
            }
        }

        return $messageLog;
    }

    /**
     * Send bulk SMS
     */
    public function sendBulk(array $recipients, string $message, array $metadata = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[] = $this->send($recipient, $message, $metadata);
        }

        return $results;
    }

    /**
     * Make HTTP request to Twilio API
     */
    private function makeRequest(string $url, array $data): ?array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPAUTH => CURL_HTTPAUTH_BASIC,
            CURLOPT_USERPWD => "{$this->accountSid}:{$this->authToken}",
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            $error = json_decode($response, true);
            throw new \Exception(
                "Twilio API Error ({$httpCode}): " . 
                ($error['message'] ?? 'Unknown error')
            );
        }

        return json_decode($response, true);
    }

    /**
     * Normalize phone number to E.164 format
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phone);

        // Add country code if missing (default +1 for US)
        if (strlen($cleaned) === 10) {
            $cleaned = '1' . $cleaned;
        } elseif (strlen($cleaned) === 9) {
            // For 9-digit numbers, add Kenya country code +254
            $cleaned = '254' . $cleaned;
        }

        return '+' . $cleaned;
    }

    /**
     * Check if service is configured
     */
    private function isConfigured(): bool
    {
        return !empty($this->accountSid) && 
               !empty($this->authToken) && 
               !empty($this->phoneNumber);
    }

    /**
     * Get SMS delivery status from Twilio
     */
    public function checkDeliveryStatus(string $twilioSid): string
    {
        try {
            $response = json_decode(
                file_get_contents(
                    "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages/{$twilioSid}.json",
                    false,
                    stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => 'Authorization: Basic ' . 
                                base64_encode("{$this->accountSid}:{$this->authToken}"),
                        ]
                    ])
                ),
                true
            );

            return $response['status'] ?? 'unknown';
        } catch (\Exception $e) {
            Log::error("Failed to check SMS status: " . $e->getMessage());
            return 'error';
        }
    }
}
