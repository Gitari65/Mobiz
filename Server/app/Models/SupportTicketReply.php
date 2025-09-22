<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketReply extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'message', 'created_at'
    ];
    public $timestamps = false;

    public function ticket() {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
