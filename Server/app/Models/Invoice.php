<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'type', 'supplier_id', 'customer_id', 'company_id', 'user_id',
        'invoice_date', 'due_date', 'subtotal', 'tax', 'discount', 'total',
        'paid_amount', 'balance', 'status', 'notes'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
