<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_id',
        'notes',
        'company_id',
        'created_by',
        'total_orders',
        'credit_balance',
        'credit_limit',
        'price_group_id',
    ];

    protected $casts = [
        'total_orders' => 'integer',
        'credit_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    /**
     * Get the user who created this customer.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the price group for this customer.
     */
    public function priceGroup()
    {
        return $this->belongsTo(PriceGroup::class);
    }

    /**
     * Get all sales for this customer.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get all credit transactions for this customer.
     */
    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class)->orderBy('created_at', 'desc');
    }
}
