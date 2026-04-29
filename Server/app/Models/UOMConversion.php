<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UOMConversion extends Model
{
    protected $table = 'uom_conversions';

    protected $fillable = [
        'from_uom_id',
        'to_uom_id',
        'conversion_factor',
        'description',
    ];

    protected $casts = [
        'conversion_factor' => 'float',
    ];

    // Relationships
    public function fromUom()
    {
        return $this->belongsTo(UOM::class, 'from_uom_id');
    }

    public function toUom()
    {
        return $this->belongsTo(UOM::class, 'to_uom_id');
    }
}
