<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'mpesa_type',
        'mpesa_number',
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_payment_methods')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }
}
