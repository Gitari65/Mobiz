<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'business_hours_start',
        'business_hours_end',
        'timezone',
        'currency',
        'currency_symbol',
        'decimal_places',
        'tax_enabled',
        'tax_name',
        'tax_rate',
        'tax_inclusive',
        'receipt_header',
        'receipt_footer',
        'auto_print_receipt',
        'receipt_logo_path',
        'invoice_prefix',
        'invoice_number_start',
        'low_stock_alerts',
        'low_stock_threshold',
        'allow_negative_stock',
        'track_stock_expiry',
        'require_customer_info',
        'allow_discount',
        'max_discount_percent',
        'allow_credit_sales',
        'email_notifications',
        'sms_notifications',
        'notification_email',
        'notification_phone',
        'require_receipt_approval',
        'enable_audit_log',
        'session_timeout_minutes',
        'two_factor_auth',
        'auto_backup',
        'backup_frequency',
        'backup_retention_days',
    ];

    protected $casts = [
        'tax_enabled' => 'boolean',
        'tax_inclusive' => 'boolean',
        'auto_print_receipt' => 'boolean',
        'low_stock_alerts' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'track_stock_expiry' => 'boolean',
        'require_customer_info' => 'boolean',
        'allow_discount' => 'boolean',
        'allow_credit_sales' => 'boolean',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'require_receipt_approval' => 'boolean',
        'enable_audit_log' => 'boolean',
        'two_factor_auth' => 'boolean',
        'auto_backup' => 'boolean',
        'tax_rate' => 'decimal:2',
        'max_discount_percent' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
