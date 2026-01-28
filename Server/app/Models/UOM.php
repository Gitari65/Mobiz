<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UOM extends Model
{
    protected $table = 'u_o_m_s';
    
    protected $fillable = [
        'name',
        'abbreviation',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class, 'uom_id');
    }
}
