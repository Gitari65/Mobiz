<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'discount_value',
        'buy_quantity',
        'get_quantity',
        'minimum_purchase',
        'minimum_quantity',
        'scope',
        'scope_items',
        'first_time_only',
        'customer_groups',
        'start_date',
        'end_date',
        'usage_limit_total',
        'usage_limit_per_customer',
        'usage_count',
        'is_active',
        'priority',
        'is_stackable',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'scope_items' => 'array',
        'customer_groups' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'first_time_only' => 'boolean',
        'is_stackable' => 'boolean',
        'discount_value' => 'decimal:2',
        'minimum_purchase' => 'decimal:2',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages()
    {
        return $this->hasMany(PromotionUsage::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Helper methods
    public function isValid()
    {
        if (!$this->is_active) return false;
        
        $now = Carbon::now();
        
        if ($this->start_date && $now->lt($this->start_date)) return false;
        if ($this->end_date && $now->gt($this->end_date)) return false;
        
        if ($this->usage_limit_total && $this->usage_count >= $this->usage_limit_total) return false;
        
        return true;
    }

    public function canBeUsedBy($customerId)
    {
        if (!$this->isValid()) return false;
        
        if ($this->usage_limit_per_customer) {
            $customerUsage = $this->usages()->where('customer_id', $customerId)->count();
            if ($customerUsage >= $this->usage_limit_per_customer) return false;
        }
        
        return true;
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }
}
