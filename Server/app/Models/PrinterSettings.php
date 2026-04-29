<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinterSettings extends Model
{
    protected $fillable = [
        'company_id',
        'header_message',
        'footer_message',
        'show_logo',
        'receipt_logo_path',
        'show_taxes',
        'show_discounts',
        'paper_size',
        'font_size',
        'alignment',
        'copies',
        'invoice_title',
        'invoice_subtitle',
        'invoice_footer_note',
        'invoice_show_logo',
    ];

    protected $casts = [
        'show_logo' => 'boolean',
        'show_taxes' => 'boolean',
        'show_discounts' => 'boolean',
        'invoice_show_logo' => 'boolean',
        'font_size' => 'integer',
        'copies' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    //
}
