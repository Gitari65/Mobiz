<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TaxConfiguration;

class Sale extends Model
{
    protected $fillable = ['total', 'company_id', 'customer_id', 'user_id', 'payment_method', 'discount', 'tax', 'tax_configuration_id', 'amount_paid', 'balance_due'];

    protected $casts = [
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
    
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function taxConfiguration()
    {
        return $this->belongsTo(TaxConfiguration::class);
    }
}

