<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceGroup extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_percentage',
        'is_system',
        'company_id'
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'is_system' => 'boolean'
    ];

    /**
     * Get the company that owns the price group.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customers in this price group.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
