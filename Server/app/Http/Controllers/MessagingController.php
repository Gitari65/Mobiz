<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\ScheduledMessage;
use App\Services\EmailService;
use App\Services\TwilioSMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MessagingController extends Controller
{
    protected $emailService;
    protected $smsService;

    public function __construct(EmailService $emailService, TwilioSMSService $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all message templates for company
     */
    public function getTemplates()
    {
        $company = Auth::user()->company;
        
        $templates = MessageTemplate::where('company_id', $company->id)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json([
            'templates' => $templates,
            'categories' => ['promotional', 'transactional', 'notification', 'reminder'],
            'types' => ['sms', 'email', 'both'],
        ]);
    }

    /**
     * Get single template
     */
    public function getTemplate(MessageTemplate $template)
    {
        if ($template->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($template);
    }

    /**
     * Create new message template
     */
    public function createTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:message_templates,slug',
            'type' => 'required|in:sms,email,both',
            'category' => 'required|in:promotional,transactional,notification,reminder',
            'email_subject' => 'required_if:type,email,both|string',
            'email_body' => 'required_if:type,email,both|string',
            'sms_body' => 'required_if:type,sms,both|string',
            'recipient_type' => 'required|in:customers,suppliers,staff',
            'variables' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        $template = MessageTemplate::create([
            ...$validated,
            'company_id' => Auth::user()->company_id,
        ]);

        Log::info("Message template created", ['template_id' => $template->id, 'name' => $template->name]);

        return response()->json(['template' => $template, 'message' => 'Template created successfully'], 201);
    }

    /**
     * Update message template
     */
    public function updateTemplate(Request $request, MessageTemplate $template)
    {
        if ($template->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:sms,email,both',
            'category' => 'sometimes|required|in:promotional,transactional,notification,reminder',
            'email_subject' => 'nullable|string',
            'email_body' => 'nullable|string',
            'sms_body' => 'nullable|string',
            'recipient_type' => 'sometimes|required|in:customers,suppliers,staff',
            'variables' => 'nullable|array',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        Log::info("Message template updated", ['template_id' => $template->id]);

        return response()->json(['template' => $template, 'message' => 'Template updated successfully']);
    }

    /**
     * Delete message template
     */
    public function deleteTemplate(MessageTemplate $template)
    {
        if ($template->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $template->delete();

        Log::info("Message template deleted", ['template_id' => $template->id]);

        return response()->json(['message' => 'Template deleted successfully']);
    }

    /**
     * Send message to single recipient
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required_unless:use_custom_content,true|exists:message_templates,id',
            'type' => 'required|in:sms,email,both',
            'use_custom_content' => 'boolean',
            'custom_subject' => 'required_if:use_custom_content,true|nullable|string',
            'custom_body' => 'required_if:use_custom_content,true|nullable|string',
            'recipient_email' => 'required_if:type,email,both|nullable|email',
            'recipient_phone' => 'required_if:type,sms,both|nullable|string',
            'recipient_name' => 'nullable|string',
            'variables' => 'nullable|array',
            'campaign_name' => 'nullable|string',
        ]);

        $company = Auth::user()->company;
        $results = [];

        try {
            if ($validated['use_custom_content']) {
                // Send custom content
                if (in_array($validated['type'], ['email', 'both']) && $validated['recipient_email']) {
                    $results['email'] = $this->emailService->send(
                        $validated['recipient_email'],
                        $validated['custom_subject'],
                        $validated['custom_body'],
                        $validated['recipient_name'],
                        null,
                        ['campaign_name' => $validated['campaign_name'] ?? 'Custom Message']
                    );
                }

                if (in_array($validated['type'], ['sms', 'both']) && $validated['recipient_phone']) {
                    $results['sms'] = $this->smsService->send(
                        $validated['recipient_phone'],
                        $validated['custom_body'],
                        ['campaign_name' => $validated['campaign_name'] ?? 'Custom Message']
                    );
                }
            } else {
                // Send using template
                $template = MessageTemplate::findOrFail($validated['template_id']);

                if ($template->company_id !== $company->id) {
                    return response()->json(['error' => 'Template not found'], 404);
                }

                if (in_array($template->type, ['email', 'both']) && $validated['recipient_email']) {
                    $results['email'] = $this->emailService->sendFromTemplate(
                        $validated['recipient_email'],
                        $template,
                        $validated['variables'] ?? [],
                        $validated['recipient_name'],
                        ['campaign_name' => $validated['campaign_name'] ?? $template->slug]
                    );
                }

                if (in_array($template->type, ['sms', 'both']) && $validated['recipient_phone']) {
                    $body = $template->renderBody($validated['variables'] ?? [], 'sms');
                    $results['sms'] = $this->smsService->send(
                        $validated['recipient_phone'],
                        $body,
                        ['campaign_name' => $validated['campaign_name'] ?? $template->slug]
                    );
                }
            }

            return response()->json([
                'message' => 'Message(s) sent successfully',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error("Message sending failed: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Send message to multiple recipients
     */
    public function sendBulkMessage(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required_unless:use_custom_content,true|nullable|exists:message_templates,id',
            'use_custom_content' => 'boolean',
            'custom_subject' => 'required_if:use_custom_content,true|nullable|string',
            'custom_body' => 'required_if:use_custom_content,true|nullable|string',
            'type' => 'required|in:sms,email,both',
            'recipient_type' => 'nullable|in:customers,suppliers,staff,custom',
            'recipients' => 'required|array|min:1',
            'recipients.*.email' => 'nullable|email',
            'recipients.*.phone' => 'nullable|string',
            'recipients.*.name' => 'nullable|string',
            'variables_template' => 'nullable|array',
            'campaign_name' => 'nullable|string',
        ]);

        $company = Auth::user()->company;
        $template = null;
        if (!($validated['use_custom_content'] ?? false) && $validated['template_id'] ?? null) {
            $template = MessageTemplate::findOrFail($validated['template_id']);
        }

        if ($template->company_id !== $company->id) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $results = [
            'total' => count($validated['recipients']),
            'sent' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($validated['recipients'] as $recipient) {
            try {
                $useCustom = $validated['use_custom_content'] ?? false;
                $msgType = $validated['type'];
                $campaignName = $validated['campaign_name'] ?? ($template ? $template->slug : 'Custom Bulk');

                if ($useCustom) {
                    // Custom content path
                    if (in_array($msgType, ['email', 'both']) && !empty($recipient['email'])) {
                        $result = $this->emailService->send(
                            $recipient['email'],
                            $validated['custom_subject'] ?? '',
                            $validated['custom_body'] ?? '',
                            $recipient['name'] ?? null,
                            null,
                            ['campaign_name' => $campaignName]
                        );
                        $results['details'][] = ['email' => $recipient['email'], 'status' => $result->status];
                        $result->status === 'sent' ? $results['sent']++ : $results['failed']++;
                    }

                    if (in_array($msgType, ['sms', 'both']) && !empty($recipient['phone'])) {
                        $result = $this->smsService->send(
                            $recipient['phone'],
                            $validated['custom_body'] ?? '',
                            ['campaign_name' => $campaignName]
                        );
                        $results['details'][] = ['phone' => $recipient['phone'], 'status' => $result->status];
                        $result->status === 'sent' ? $results['sent']++ : $results['failed']++;
                    }
                } else {
                    // Template path
                    if (in_array($template->type, ['email', 'both']) && !empty($recipient['email'])) {
                        $result = $this->emailService->sendFromTemplate(
                            $recipient['email'],
                            $template,
                            $validated['variables_template'] ?? [],
                            $recipient['name'] ?? null,
                            ['campaign_name' => $campaignName]
                        );
                        $results['details'][] = ['email' => $recipient['email'], 'status' => $result->status];
                        $result->status === 'sent' ? $results['sent']++ : $results['failed']++;
                    }

                    if (in_array($template->type, ['sms', 'both']) && !empty($recipient['phone'])) {
                        $body = $template->renderBody($validated['variables_template'] ?? [], 'sms');
                        $result = $this->smsService->send(
                            $recipient['phone'],
                            $body,
                            ['campaign_name' => $campaignName]
                        );
                        $results['details'][] = ['phone' => $recipient['phone'], 'status' => $result->status];
                        $result->status === 'sent' ? $results['sent']++ : $results['failed']++;
                    }
                }

            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'recipient' => $recipient,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info("Bulk messages sent", [
            'template_id' => $template?->id,
            'total' => $results['total'],
            'sent' => $results['sent'],
        ]);

        return response()->json([
            'message' => 'Bulk message campaign completed',
            'results' => $results,
        ]);
    }

    /**
     * Get message logs/history
     */
    public function getMessageLogs(Request $request)
    {
        $company = Auth::user()->company;

        $query = MessageLog::where('company_id', $company->id)
            ->with('messageTemplate', 'sentByUser');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by campaign
        if ($request->has('campaign')) {
            $query->where('campaign_type', $request->campaign);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($logs);
    }

    /**
     * Get messaging statistics
     */
    public function getStats(Request $request)
    {
        $company = Auth::user()->company;
        $days = $request->get('days', 7);

        $stats = MessageLog::getStats($company->id, null, $days);

        return response()->json([
            'period_days' => $days,
            'stats' => $stats,
        ]);
    }

    /**
     * Initialize default templates for company
     */
    public function initializeDefaultTemplates()
    {
        $company = Auth::user()->company;

        // Check if templates already exist
        if (MessageTemplate::where('company_id', $company->id)->exists()) {
            return response()->json(['message' => 'Templates already initialized']);
        }

        $defaults = MessageTemplate::getDefaultTemplates();
        $created = 0;

        foreach ($defaults as $template) {
            MessageTemplate::create([
                ...$template,
                'company_id' => $company->id,
            ]);
            $created++;
        }

        Log::info("Default templates initialized", ['company_id' => $company->id, 'count' => $created]);

        return response()->json([
            'message' => 'Default templates initialized',
            'count' => $created,
        ]);
    }

    /**
     * Test send message (preview)
     */
    public function testTemplate(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:message_templates,id',
            'variables' => 'required|array',
        ]);

        $company = Auth::user()->company;
        $template = MessageTemplate::findOrFail($validated['template_id']);

        if ($template->company_id !== $company->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $preview = [
            'email_subject' => $template->renderSubject($validated['variables']),
            'email_body' => $template->renderBody($validated['variables'], 'email'),
            'sms_body' => $template->renderBody($validated['variables'], 'sms'),
        ];

        return response()->json(['preview' => $preview]);
    }
}
