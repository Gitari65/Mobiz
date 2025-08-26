<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class Purchase extends Model
{
    protected $fillable = ['supplier_name', 'notes', 'total_cost'];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
