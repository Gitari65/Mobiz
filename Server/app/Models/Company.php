<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'category',
        'phone',
        'email',
        'kra_pin',
        'address',
        'city',
        'county',
        'zip_code',
        'country',
        'owner_name',
        'owner_position',
        'approved',
        'active',
        'created_at',
        'updated_at'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'company_payment_methods')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    public function enabledPaymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'company_payment_methods')
            ->wherePivot('is_enabled', true)
            ->withTimestamps();
    }
}
