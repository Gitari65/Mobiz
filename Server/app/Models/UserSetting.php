<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme',
        'language',
        'items_per_page',
        'date_format',
        'time_format',
        'email_notifications',
        'push_notifications',
        'low_stock_alerts',
        'sale_alerts',
        'report_alerts',
        'dashboard_widgets',
        'default_page',
        'auto_print_receipt',
        'printer_name',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'low_stock_alerts' => 'boolean',
        'sale_alerts' => 'boolean',
        'report_alerts' => 'boolean',
        'auto_print_receipt' => 'boolean',
        'dashboard_widgets' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
