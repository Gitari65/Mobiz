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
        'show_taxes',
        'show_discounts',
        'paper_size',
        'font_size',
        'alignment',
        'copies',
    ];

    protected $casts = [
        'show_logo' => 'boolean',
        'show_taxes' => 'boolean',
        'show_discounts' => 'boolean',
        'font_size' => 'integer',
        'copies' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    //
}
