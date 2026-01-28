<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditHistory extends Model
{
    protected $table = 'credit_history';

    protected $fillable = [
        'company_id',
        'customer_id',
        'user_id',
        'transaction_type',
        'reference_id',
        'reference_number',
        'amount',
        'balance_before',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record($customerId, $companyId, $userId, $type, $amount, $description, $referenceId = null, $referenceNumber = null)
    {
        $customer = Customer::find($customerId);
        if (!$customer) return null;

        $balanceBefore = $customer->credit_balance;
        $balanceAfter = $balanceBefore + $amount;

        return self::create([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'user_id' => $userId,
            'transaction_type' => $type,
            'reference_id' => $referenceId,
            'reference_number' => $referenceNumber,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);
    }
}
