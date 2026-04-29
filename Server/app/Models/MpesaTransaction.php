<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'sale_id',
        'phone_number',
        'amount',
        'reference',
        'description',
        'merchant_request_id',
        'checkout_request_id',
        'status',
        'result_code',
        'result_desc',
        'mpesa_receipt_number',
        'transaction_date',
        'raw_callback',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'raw_callback' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}