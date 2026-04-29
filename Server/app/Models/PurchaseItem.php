<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseItem extends Model

{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_price',
        'total_price',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
