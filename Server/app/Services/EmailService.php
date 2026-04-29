<?php

namespace App\Services;

use App\Models\MessageLog;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Email Service using Laravel Mail with Mailtrap
 * 
 * Configuration:
 * MAIL_MAILER=smtp
 * MAIL_HOST=sandbox.smtp.mailtrap.io
 * MAIL_PORT=2525
 * MAIL_USERNAME=your_mailtrap_username
 * MAIL_PASSWORD=your_mailtrap_password
 * MAIL_ENCRYPTION=tls
 * MAIL_FROM_ADDRESS=no-reply@mobiz.local
 * MAIL_FROM_NAME=MOBIz POS
 */

class EmailService
{
    /**
     * Send email with template
     */
    public function sendFromTemplate(
        string $recipientEmail,
        MessageTemplate $template,
        array $variables = [],
        ?string $recipientName = null,
        array $metadata = []
    ): MessageLog
    {
        try {
            // Render template
            $subject = $template->renderSubject($variables);
            $body = $template->renderBody($variables, 'email');

            return $this->send(
                $recipientEmail,
                $subject,
                $body,
                $recipientName,
                $template->id,
                $metadata
            );
        } catch (\Exception $e) {
            Log::error("Email from template failed: " . $e->getMessage());
            return $this->createFailedLog(
                $recipientEmail,
                'Unknown Subject',
                $e->getMessage(),
                $recipientName,
                null,
                $metadata
            );
        }
    }

    /**
     * Send raw email
     */
    public function send(
        string $recipientEmail,
        string $subject,
        string $body,
        ?string $recipientName = null,
        ?int $templateId = null,
        array $metadata = []
    ): MessageLog
    {
        try {
            // Create pending message log
            $messageLog = MessageLog::create([
                'company_id' => auth()->user()?->company_id,
                'sent_by_user_id' => auth()->id(),
                'message_template_id' => $templateId,
                'type' => 'email',
                'recipient_name' => $recipientName,
                'recipient_contact' => $recipientEmail,
                'subject' => $subject,
                'body' => $body,
                'status' => 'pending',
                'metadata' => $metadata,
            ]);

            // Send email using Laravel Mail
            Mail::raw($body, function ($message) use ($recipientEmail, $recipientName, $subject) {
                $message->to($recipientEmail, $recipientName)
                    ->subject($subject);
            });

            // Mark as sent
            $messageLog->markAsSent();
            
            Log::info("Email sent successfully", [
                'message_log_id' => $messageLog->id,
                'recipient' => $recipientEmail,
                'subject' => $subject,
            ]);

        } catch (\Exception $e) {
            Log::error("Email sending failed: " . $e->getMessage());
            if (isset($messageLog)) {
                $messageLog->markAsFailed($e->getMessage());
            } else {
                $messageLog = $this->createFailedLog(
                    $recipientEmail,
                    $subject,
                    $e->getMessage(),
                    $recipientName,
                    $templateId,
                    $metadata
                );
            }
        }

        return $messageLog;
    }

    /**
     * Send HTML email
     */
    public function sendHtml(
        string $recipientEmail,
        string $subject,
        string $htmlBody,
        ?string $recipientName = null,
        array $metadata = []
    ): MessageLog
    {
        try {
            $messageLog = MessageLog::create([
                'company_id' => auth()->user()?->company_id,
                'sent_by_user_id' => auth()->id(),
                'type' => 'email',
                'recipient_name' => $recipientName,
                'recipient_contact' => $recipientEmail,
                'subject' => $subject,
                'body' => $htmlBody,
                'status' => 'pending',
                'metadata' => $metadata,
            ]);

            Mail::html($htmlBody, function ($message) use ($recipientEmail, $recipientName, $subject) {
                $message->to($recipientEmail, $recipientName)
                    ->subject($subject);
            });

            $messageLog->markAsSent();

        } catch (\Exception $e) {
            Log::error("HTML email sending failed: " . $e->getMessage());
            if (isset($messageLog)) {
                $messageLog->markAsFailed($e->getMessage());
            }
        }

        return $messageLog;
    }

    /**
     * Send bulk emails
     */
    public function sendBulk(array $recipients, string $subject, string $body, array $metadata = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $recipientEmail = is_array($recipient) ? $recipient['email'] : $recipient;
            $recipientName = is_array($recipient) ? ($recipient['name'] ?? null) : null;

            $results[] = $this->send($recipientEmail, $subject, $body, $recipientName, null, $metadata);
        }

        return $results;
    }

    /**
     * Send email from Mailable class
     */
    public function sendMailable($mailable): MessageLog
    {
        try {
            Mail::send($mailable);

            $messageLog = MessageLog::create([
                'company_id' => auth()->user()?->company_id,
                'sent_by_user_id' => auth()->id(),
                'type' => 'email',
                'recipient_contact' => $mailable->getTo() ? current($mailable->getTo()) : null,
                'subject' => $mailable->getSubject(),
                'body' => 'Mailable class',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info("Mailable email sent");

            return $messageLog;

        } catch (\Exception $e) {
            Log::error("Mailable email failed: " . $e->getMessage());
            return $this->createFailedLog(
                $mailable->getTo() ? current($mailable->getTo()) : 'unknown',
                $mailable->getSubject(),
                $e->getMessage()
            );
        }
    }

    /**
     * Create a failed message log record
     */
    private function createFailedLog(
        string $recipientContact,
        string $subject,
        string $error,
        ?string $recipientName = null,
        ?int $templateId = null,
        array $metadata = []
    ): MessageLog
    {
        return MessageLog::create([
            'company_id' => auth()->user()?->company_id,
            'sent_by_user_id' => auth()->id(),
            'message_template_id' => $templateId,
            'type' => 'email',
            'recipient_name' => $recipientName,
            'recipient_contact' => $recipientContact,
            'subject' => $subject,
            'body' => 'Failed to send',
            'status' => 'failed',
            'error_message' => $error,
            'metadata' => $metadata,
        ]);
    }
}
