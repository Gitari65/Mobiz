<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category',
        'brand',
        'description',
        'price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer'
    ];

    // Accessors
    public function getProfitMarginAttribute()
    {
        if ($this->cost_price && $this->price) {
            return (($this->price - $this->cost_price) / $this->cost_price) * 100;
        }
        return 0;
    }

    public function getIsLowStockAttribute()
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function getIsOutOfStockAttribute()
    {
        return $this->stock_quantity <= 0;
    }

    public function getTotalValueAttribute()
    {
        return $this->price * $this->stock_quantity;
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= low_stock_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', 0);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    // Relationships
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Helper Methods
    public function updateStock($quantity, $operation = 'subtract')
    {
        if ($operation === 'add') {
            $this->increment('stock_quantity', $quantity);
        } else {
            $this->decrement('stock_quantity', $quantity);
        }
        
        $this->save();
        return $this;
    }

    public function generateSku()
    {
        if (!$this->sku) {
            $category_prefix = strtoupper(substr($this->category ?? 'GEN', 0, 3));
            $random_suffix = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $this->sku = $category_prefix . '-' . $random_suffix;
            $this->save();
        }
        return $this->sku;
    }
}
