<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnModel extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'return_number',
        'company_id',
        'sale_id',
        'customer_id',
        'user_id',
        'return_date',
        'refund_amount',
        'refund_method',
        'status',
        'reason',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'refund_amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public static function generateReturnNumber()
    {
        $prefix = 'RET-';
        $year = date('Y');
        $lastReturn = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReturn) {
            $lastNumber = (int) str_replace($prefix . $year . '-', '', $lastReturn->return_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
