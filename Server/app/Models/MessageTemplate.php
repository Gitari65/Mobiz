<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'type',
        'category',
        'email_subject',
        'email_body',
        'sms_body',
        'variables',
        'is_active',
        'recipient_type',
        'description',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function scheduledMessages(): HasMany
    {
        return $this->hasMany(ScheduledMessage::class);
    }

    /**
     * Replace variables in template with actual values
     */
    public function renderBody(array $variables, string $channel = 'sms'): string
    {
        $body = $channel === 'email' ? $this->email_body : $this->sms_body;
        
        foreach ($variables as $key => $value) {
            $body = str_replace("{{$key}}", $value, $body);
        }
        
        return $body;
    }

    public function renderSubject(array $variables): ?string
    {
        if (!$this->email_subject) return null;
        
        $subject = $this->email_subject;
        foreach ($variables as $key => $value) {
            $subject = str_replace("{{$key}}", $value, $subject);
        }
        
        return $subject;
    }

    /**
     * Get templates by category
     */
    public static function getByCategory(string $category, int $companyId)
    {
        return self::where('company_id', $companyId)
            ->where('category', $category)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Built-in template defaults for new companies
     */
    public static function getDefaultTemplates(): array
    {
        return [
            [
                'name' => 'Welcome New Customer',
                'slug' => 'welcome_customer',
                'type' => 'both',
                'category' => 'promotional',
                'recipient_type' => 'customers',
                'email_subject' => 'Welcome to {{company_name}}!',
                'email_body' => 'Dear {{customer_name}},\n\nThank you for shopping with us! We\'re excited to serve you.\n\nBest regards,\n{{company_name}}',
                'sms_body' => 'Hi {{customer_name}}, Welcome to {{company_name}}! Thank you for your business.',
                'variables' => ['customer_name', 'company_name'],
            ],
            [
                'name' => 'Purchase Confirmation',
                'slug' => 'purchase_confirmation',
                'type' => 'both',
                'category' => 'transactional',
                'recipient_type' => 'customers',
                'email_subject' => 'Your Purchase Receipt - Order #{{sale_id}}',
                'email_body' => 'Dear {{customer_name}},\n\nThank you for your purchase!\n\nOrder #{{sale_id}}\nTotal: {{total_amount}}\n\nBest regards,\n{{company_name}}',
                'sms_body' => 'Hi {{customer_name}}, Your order #{{sale_id}} for {{total_amount}} has been confirmed. Thank you!',
                'variables' => ['customer_name', 'sale_id', 'total_amount', 'company_name'],
            ],
            [
                'name' => 'Low Stock Alert',
                'slug' => 'low_stock_alert',
                'type' => 'email',
                'category' => 'notification',
                'recipient_type' => 'staff',
                'email_subject' => 'Low Stock Alert: {{product_name}}',
                'email_body' => 'Product "{{product_name}}" is running low!\n\nCurrent Stock: {{current_stock}} {{uom}}\nMinimum Level: {{minimum_level}} {{uom}}\n\nPlease reorder immediately.',
                'sms_body' => null,
                'variables' => ['product_name', 'current_stock', 'minimum_level', 'uom'],
            ],
            [
                'name' => 'Weekly Sales Report',
                'slug' => 'weekly_sales_report',
                'type' => 'email',
                'category' => 'notification',
                'recipient_type' => 'staff',
                'email_subject' => 'Weekly Sales Report - {{week_ending}}',
                'email_body' => 'Dear {{manager_name}},\n\nHere\'s your weekly sales summary:\n\nTotal Sales: {{total_sales}}\nTransactions: {{transaction_count}}\nTop Product: {{top_product}}\n\nBest regards,\n{{company_name}}',
                'sms_body' => null,
                'variables' => ['manager_name', 'week_ending', 'total_sales', 'transaction_count', 'top_product', 'company_name'],
            ],
            [
                'name' => 'Invoice Payment Reminder',
                'slug' => 'invoice_reminder',
                'type' => 'both',
                'category' => 'reminder',
                'recipient_type' => 'customers',
                'email_subject' => 'Payment Reminder - Invoice #{{invoice_id}}',
                'email_body' => 'Dear {{customer_name}},\n\nThis is a friendly reminder about invoice #{{invoice_id}} due on {{due_date}}.\n\nAmount Due: {{amount_due}}\n\nPlease process payment at your earliest convenience.\n\nBest regards,\n{{company_name}}',
                'sms_body' => 'Hi {{customer_name}}, Invoice #{{invoice_id}} of {{amount_due}} is due on {{due_date}}. Please pay. Contact {{company_name}}.',
                'variables' => ['customer_name', 'invoice_id', 'due_date', 'amount_due', 'company_name'],
            ],
            [
                'name' => 'Stock Transfer Complete',
                'slug' => 'stock_transfer_complete',
                'type' => 'email',
                'category' => 'notification',
                'recipient_type' => 'staff',
                'email_subject' => 'Stock Transfer Complete - {{transfer_id}}',
                'email_body' => 'Stock transfer {{transfer_id}} has been completed.\n\nFrom: {{from_warehouse}}\nTo: {{to_warehouse}}\nItems: {{item_count}}\n\nPlease verify receipt.',
                'sms_body' => null,
                'variables' => ['transfer_id', 'from_warehouse', 'to_warehouse', 'item_count'],
            ],
            [
                'name' => 'Daily Closing Summary',
                'slug' => 'daily_closing_summary',
                'type' => 'email',
                'category' => 'notification',
                'recipient_type' => 'staff',
                'email_subject' => 'Daily Closing Summary - {{date}}',
                'email_body' => 'Daily Summary for {{date}}:\n\nTotal Sales: {{total_sales}}\nCash Received: {{cash_received}}\nTransactions: {{transaction_count}}\nTop Item: {{top_item}}\n\nReady for deposit? {{needs_deposit_alert}}',
                'sms_body' => null,
                'variables' => ['date', 'total_sales', 'cash_received', 'transaction_count', 'top_item', 'needs_deposit_alert'],
            ],
            [
                'name' => 'Customer Loyalty Offer',
                'slug' => 'loyalty_offer',
                'type' => 'both',
                'category' => 'promotional',
                'recipient_type' => 'customers',
                'email_subject' => 'Special Offer for You, {{customer_name}}!',
                'email_body' => 'Dear {{customer_name}},\n\nWe have a special {{discount_percent}}% discount on {{product_category}} just for you!\n\nOffer valid until {{expiry_date}}.\n\nCome visit us soon!\n\nBest regards,\n{{company_name}}',
                'sms_body' => 'Hi {{customer_name}}, Special {{discount_percent}}% off {{product_category}}! Valid until {{expiry_date}}. Visit us now!',
                'variables' => ['customer_name', 'discount_percent', 'product_category', 'expiry_date', 'company_name'],
            ],
            [
                'name' => 'Return Processed',
                'slug' => 'return_processed',
                'type' => 'both',
                'category' => 'transactional',
                'recipient_type' => 'customers',
                'email_subject' => 'Your Return Has Been Processed',
                'email_body' => 'Dear {{customer_name}},\n\nYour return has been successfully processed.\n\nRefund Amount: {{refund_amount}}\nRefund Date: {{refund_date}}\n\nThank you for shopping with us!\n\nBest regards,\n{{company_name}}',
                'sms_body' => 'Hi {{customer_name}}, Your return of {{refund_amount}} has been processed. Refund by {{refund_date}}.',
                'variables' => ['customer_name', 'refund_amount', 'refund_date', 'company_name'],
            ],
        ];
    }
}
