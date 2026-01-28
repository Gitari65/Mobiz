<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'company_id',
        'customer_id',
        'user_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax',
        'discount',
        'total',
        'paid_amount',
        'balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
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

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public static function generateInvoiceNumber()
    {
        $prefix = 'INV-';
        $year = date('Y');
        $lastInvoice = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) str_replace($prefix . $year . '-', '', $lastInvoice->invoice_number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function updateBalance()
    {
        $this->paid_amount = $this->payments()->sum('amount');
        $this->balance = $this->total - $this->paid_amount;
        
        if ($this->balance <= 0 && $this->status !== 'paid') {
            $this->status = 'paid';
        } elseif ($this->balance > 0 && $this->status === 'paid') {
            $this->status = 'sent';
        }
        
        $this->save();
    }
}
