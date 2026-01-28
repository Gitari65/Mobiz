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
        'low_stock_threshold',
        'company_id',
        'warehouse_id',
        'uom_id',
        'created_by',
        'updated_by'
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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class);
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Empties/Returnables Relationships
    public function empties()
    {
        return $this->belongsToMany(Product::class, 'product_empties', 'product_id', 'empty_product_id')
            ->withPivot('quantity', 'deposit_amount', 'is_active')
            ->withTimestamps()
            ->where('product_empties.is_active', true);
    }

    public function parentProducts()
    {
        return $this->belongsToMany(Product::class, 'product_empties', 'empty_product_id', 'product_id')
            ->withPivot('quantity', 'deposit_amount', 'is_active')
            ->withTimestamps();
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

    /**
     * Get the price for a specific price group
     * If custom price exists for the group, use it; otherwise calculate from base price with group discount
     */
    public function getPriceForGroup($priceGroupId)
    {
        // Check if there's a custom price for this group
        $customPrice = $this->prices()
            ->where('price_group_id', $priceGroupId)
            ->first();

        if ($customPrice) {
            return $customPrice->price;
        }

        // If no custom price, calculate from base price using group discount
        $priceGroup = PriceGroup::find($priceGroupId);
        if ($priceGroup && $priceGroup->discount_percentage > 0) {
            return $this->price * (1 - $priceGroup->discount_percentage / 100);
        }

        // Default to base price
        return $this->price;
    }

    /**
     * Get the price for a customer (convenience method)
     */
    public function getPriceForCustomer($customer)
    {
        if (!$customer || !$customer->price_group_id) {
            return $this->price;
        }

        return $this->getPriceForGroup($customer->price_group_id);
    }
}
