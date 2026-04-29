<?php

namespace App\Models;

use App\Services\UOMConversionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category',
        'subcategory',
        'brand',
        'description',
        'price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'company_id',
        'warehouse_id',
        'uom_id',
        'purchase_uom_id',
        'sale_uom_id',
        'conversion_ratio',
        'track_by_purchase_unit',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'decimal:4',
        'low_stock_threshold' => 'integer',
        'conversion_ratio' => 'decimal:4',
        'track_by_purchase_unit' => 'boolean'
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

    // Purchase and Sale UoM relationships
    public function purchaseUom()
    {
        return $this->belongsTo(UOM::class, 'purchase_uom_id');
    }

    public function saleUom()
    {
        return $this->belongsTo(UOM::class, 'sale_uom_id');
    }

    // Multiple sale UOMs (many-to-many)
    public function saleUoms()
    {
        return $this->belongsToMany(UOM::class, 'product_sale_uoms', 'product_id', 'uom_id')
            ->withPivot('conversion_ratio', 'is_default')
            ->withTimestamps();
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

    /**
     * Get available quantity in sale units
     * e.g., if stock_quantity=2 (50L each) and conversion_ratio=50 (1 purchase = 50 sale units of 1L)
     * then available quantity in sale units = 2 * 50 = 100 (1L units)
     */
    public function getAvailableQuantityInSaleUnits()
    {
        return $this->convertBaseUnitsToQuantity((float) $this->stock_quantity, $this->getDefaultSaleUomId());
    }

    /**
     * Get stock quantity formatted for display in sale units
     */
    public function getStockInSaleUnits()
    {
        return $this->getAvailableQuantityInSaleUnits();
    }

    /**
     * Get stock quantity in smallest unit (for inventory tracking)
     * E.g., if purchase_uom is "Kg" and quantity is 10, returns 10000g
     * Uses UOMConversionService for accurate conversions
     */
    public function getStockInSmallestUnit()
    {
        return (float) $this->stock_quantity;
    }

    /**
     * Get stock remaining needed to finish (in smallest unit)
     * E.g., stock = 10Kg, smallest unit = g, returns 10000g
     * This shows total inventory needed to be consumed
     */
    public function getStockRemainingInSmallestUnit()
    {
        return (float) $this->stock_quantity;
    }

    /**
     * Calculate margin with UOM conversion awareness
     * Accounts for conversion ratios when calculating profit
     */
    public function getMarginWithConversion(float $quantity = null, int $saleUomId = null)
    {
        $quantity = $quantity ?? 1;
        $saleUomId = $saleUomId ?? $this->sale_uom_id;

        if (!$this->cost_price || !$this->purchase_uom_id) {
            return 0;
        }

        // Get the cost per unit in purchase UOM
        $costPerPurchaseUnit = $this->cost_price;

        // If we have sale UOM, calculate cost per sale unit
        if ($this->purchase_uom_id && $saleUomId && $this->purchase_uom_id !== $saleUomId) {
            // Convert 1 sale unit to purchase unit to get its cost
            $conversionRatio = \App\Services\UOMConversionService::getConversionFactor(
                $saleUomId,
                $this->purchase_uom_id
            );
            $costPerSaleUnit = $costPerPurchaseUnit * $conversionRatio;
        } else {
            $costPerSaleUnit = $costPerPurchaseUnit;
        }

        // Get the sale price
        $salePrice = $this->getPrice($saleUomId);

        // Calculate margin
        if ($costPerSaleUnit == 0) {
            return 0;
        }

        $margin = (($salePrice - $costPerSaleUnit) / $costPerSaleUnit) * 100;
        return max(0, $margin);
    }

    /**
     * Get price for a specific UOM
     */
    public function getPrice(int $uomId = null): float
    {
        $uomId = $uomId ?? $this->sale_uom_id;

        if (!$uomId) {
            return $this->price ?? 0;
        }

        // Check if we have a specific price for this UOM
        $priceEntry = $this->prices()
            ->where('uom_id', $uomId)
            ->first();

        if ($priceEntry) {
            return (float) $priceEntry->price;
        }

        // Fall back to default price
        return $this->price ?? 0;
    }

    /**
     * Check if stock needs replenishment (in smallest unit)
     * E.g., if threshold is 10kg and stock is 5kg, shows need 5kg more
     */
    public function getReplenishmentNeededInSmallestUnit()
    {
        return max(0, (int) ($this->low_stock_threshold ?? 0) - (int) $this->stock_quantity);
    }

    /**
     * Convert inventory to multiple sale UOM quantities
     * Returns array like: [uom_id => quantity, ...]
     */
    public function getStockInAllSaleUoms()
    {
        return $this->getAvailableStockByUom();
    }

    /**
     * Deduct stock based on sale units sold
     * Converts sale units back to purchase units for storage
     */
    public function deductStockBySaleUnit($saleQuantity)
    {
        return $this->subtractStockForUom((float) $saleQuantity, $this->getDefaultSaleUomId());
    }

    /**
     * Add stock based on purchase units
     */
    public function addStockByPurchaseUnit($purchaseQuantity)
    {
        return $this->addStockForUom((float) $purchaseQuantity, $this->purchase_uom_id ?: $this->getBaseUomId());
    }

    public function getBaseUomId(): ?int
    {
        return $this->uom_id ?: $this->sale_uom_id ?: $this->purchase_uom_id;
    }

    public function getDefaultSaleUomId(): ?int
    {
        if ($this->sale_uom_id) {
            return (int) $this->sale_uom_id;
        }

        $saleUoms = $this->relationLoaded('saleUoms') ? $this->saleUoms : $this->saleUoms()->get();
        $defaultSaleUom = $saleUoms->first(function ($uom) {
            return (bool) optional($uom->pivot)->is_default;
        });

        return $defaultSaleUom?->id
            ?: $saleUoms->first()?->id
            ?: $this->getBaseUomId();
    }

    public function getAvailableStockByUom(): array
    {
        $uomIds = array_values(array_unique(array_filter(array_merge(
            [$this->getBaseUomId(), $this->purchase_uom_id, $this->sale_uom_id],
            $this->relationLoaded('saleUoms')
                ? $this->saleUoms->pluck('id')->all()
                : $this->saleUoms()->pluck('u_o_m_s.id')->all()
        ))));

        $stockByUom = [];
        foreach ($uomIds as $uomId) {
            $stockByUom[$uomId] = $this->convertBaseUnitsToQuantity((float) $this->stock_quantity, (int) $uomId);
        }

        return $stockByUom;
    }

    public function hasEnoughStockForQuantity(float $quantity, ?int $uomId = null): bool
    {
        return (float) $this->stock_quantity + 0.0001 >= $this->convertQuantityToBaseUnits($quantity, $uomId);
    }

    public function convertQuantityToBaseUnits(float $quantity, ?int $uomId = null): float
    {
        $quantity = max(0, $quantity);
        $multiplier = $this->getBaseUnitsPerUom($uomId ?: $this->getBaseUomId());

        if ($multiplier === null) {
            throw new \RuntimeException("Missing UOM conversion for product {$this->name}");
        }

        return $this->normalizeBaseQuantity($quantity * $multiplier);
    }

    public function convertBaseUnitsToQuantity(float $baseQuantity, ?int $uomId = null): float
    {
        $uomId = $uomId ?: $this->getBaseUomId();
        $multiplier = $this->getBaseUnitsPerUom($uomId);

        if (!$multiplier) {
            return round($baseQuantity, 4);
        }

        return round($baseQuantity / $multiplier, 4);
    }

    public function subtractStockForUom(float $quantity, ?int $uomId = null): bool
    {
        $baseQuantity = $this->convertQuantityToBaseUnits($quantity, $uomId);
        if ((float) $this->stock_quantity + 0.0001 < $baseQuantity) {
            return false;
        }

        $this->decrement('stock_quantity', $baseQuantity);
        return true;
    }

    public function addStockForUom(float $quantity, ?int $uomId = null): self
    {
        $baseQuantity = $this->convertQuantityToBaseUnits($quantity, $uomId);
        $this->increment('stock_quantity', $baseQuantity);
        return $this->fresh();
    }

    protected function getBaseUnitsPerUom(?int $uomId): ?float
    {
        $baseUomId = $this->getBaseUomId();
        if (!$baseUomId) {
            return 1.0;
        }

        if (!$uomId || (int) $uomId === (int) $baseUomId) {
            return 1.0;
        }

        $globalFactor = UOMConversionService::resolveConversionFactor((int) $uomId, (int) $baseUomId);
        if ($globalFactor !== null) {
            return $globalFactor;
        }

        $purchaseToBase = $this->getPurchaseToBaseMultiplier();
        if ($this->purchase_uom_id && (int) $uomId === (int) $this->purchase_uom_id) {
            return $purchaseToBase;
        }

        $saleUnitsPerPurchase = $this->getSaleUnitsPerPurchase((int) $uomId);
        if ($saleUnitsPerPurchase && $purchaseToBase) {
            return $purchaseToBase / $saleUnitsPerPurchase;
        }

        return null;
    }

    protected function getPurchaseToBaseMultiplier(): ?float
    {
        $baseUomId = $this->getBaseUomId();
        if (!$baseUomId || !$this->purchase_uom_id) {
            return 1.0;
        }

        if ((int) $this->purchase_uom_id === (int) $baseUomId) {
            return 1.0;
        }

        $globalFactor = UOMConversionService::resolveConversionFactor((int) $this->purchase_uom_id, (int) $baseUomId);
        if ($globalFactor !== null) {
            return $globalFactor;
        }

        $pivotRatio = $this->getSaleUnitsPerPurchase((int) $baseUomId);
        if ($pivotRatio) {
            return $pivotRatio;
        }

        if ($this->sale_uom_id && (int) $this->sale_uom_id === (int) $baseUomId && $this->conversion_ratio) {
            return (float) $this->conversion_ratio;
        }

        return null;
    }

    protected function getSaleUnitsPerPurchase(int $uomId): ?float
    {
        if ($this->purchase_uom_id && (int) $this->purchase_uom_id !== $uomId) {
            $purchaseToSaleFactor = UOMConversionService::resolveConversionFactor((int) $this->purchase_uom_id, $uomId);
            if ($purchaseToSaleFactor !== null && $purchaseToSaleFactor > 0) {
                return (float) $purchaseToSaleFactor;
            }
        }

        if ($this->sale_uom_id && (int) $this->sale_uom_id === $uomId && $this->conversion_ratio) {
            return (float) $this->conversion_ratio;
        }

        $saleUoms = $this->relationLoaded('saleUoms') ? $this->saleUoms : $this->saleUoms()->get();
        $saleUom = $saleUoms->firstWhere('id', $uomId);

        return $saleUom && $saleUom->pivot && $saleUom->pivot->conversion_ratio
            ? (float) $saleUom->pivot->conversion_ratio
            : null;
    }

    protected function normalizeBaseQuantity(float $quantity): float
    {
        return round($quantity, 4);
    }
}
