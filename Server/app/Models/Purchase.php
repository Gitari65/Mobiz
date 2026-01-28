<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'supplier_name',
        'invoice_number',
        'invoice_date',
        'warehouse_id',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'discount',
        'total_cost',
        'notes'
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
