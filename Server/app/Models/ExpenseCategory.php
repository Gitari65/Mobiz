<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'color',
        'icon',
        'is_active',
        'budget_limit',
        'alert_threshold'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'budget_limit' => 'decimal:2',
        'alert_threshold' => 'decimal:2'
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }

    public function getTotalExpensesAttribute()
    {
        return $this->expenses()->sum('amount');
    }

    public function getCurrentMonthExpensesAttribute()
    {
        return $this->expenses()
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');
    }

    public function getBudgetUsagePercentageAttribute()
    {
        if (!$this->budget_limit || $this->budget_limit == 0) {
            return 0;
        }
        
        return ($this->current_month_expenses / $this->budget_limit) * 100;
    }

    public function getIsOverBudgetAttribute()
    {
        return $this->budget_limit && $this->current_month_expenses > $this->budget_limit;
    }

    public function getIsNearThresholdAttribute()
    {
        return $this->alert_threshold && 
               $this->current_month_expenses >= ($this->budget_limit * $this->alert_threshold / 100);
    }
}
