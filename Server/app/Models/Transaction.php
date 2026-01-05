<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['subscription_id', 'amount', 'currency', 'status', 'transaction_id', 'payment_method', 'description', 'processed_at'];
    protected $casts = ['processed_at' => 'datetime'];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
