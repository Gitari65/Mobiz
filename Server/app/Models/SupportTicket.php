<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'business_id', 'user_id', 'subject', 'message', 'status', 'created_at'
    ];
    public $timestamps = false;

    public function business() {
        return $this->belongsTo(Company::class, 'business_id');
    }
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function replies() {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id');
    }
}
