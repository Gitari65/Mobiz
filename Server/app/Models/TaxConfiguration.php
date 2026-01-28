<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxConfiguration extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'tax_type',
        'rate',
        'is_inclusive',
        'is_default',
        'is_active',
        'is_system_default',
        'description',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_inclusive' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where(function($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhereNull('company_id');
        });
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function calculateTax($amount, $inclusive = null)
    {
        $inclusive = $inclusive ?? $this->is_inclusive;
        
        if ($inclusive) {
            return $amount - ($amount / (1 + $this->rate / 100));
        } else {
            return $amount * ($this->rate / 100);
        }
    }

    public function calculateAmountWithTax($amount)
    {
        if ($this->is_inclusive) {
            return $amount;
        } else {
            return $amount + $this->calculateTax($amount, false);
        }
    }

    public function calculateAmountWithoutTax($amount)
    {
        if ($this->is_inclusive) {
            return $amount / (1 + $this->rate / 100);
        } else {
            return $amount;
        }
    }
}
