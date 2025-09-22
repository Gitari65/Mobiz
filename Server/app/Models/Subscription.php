<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'business_id', 'plan', 'status', 'renewal_date', 'features'
    ];
    public $timestamps = false;

    protected $casts = [
        'features' => 'array',
        'renewal_date' => 'datetime',
    ];

    public function business() {
        return $this->belongsTo(Company::class, 'business_id');
    }
}
