<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'price', 'billing_cycle', 'features', 'is_active'];
    protected $casts = ['features' => 'array'];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
