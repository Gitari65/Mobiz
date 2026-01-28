<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'price_group_id',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    /**
     * Get the product associated with this price.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the price group associated with this price.
     */
    public function priceGroup()
    {
        return $this->belongsTo(PriceGroup::class);
    }
}
