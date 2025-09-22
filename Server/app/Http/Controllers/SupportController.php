<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\Company;
use App\Models\User;

class SupportController extends Controller
{
    // List/filter support tickets for super user
    public function tickets(Request $request)
    {
        $query = SupportTicket::with(['business', 'user']);
        if ($request->has('business')) {
            $query->whereHas('business', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->business . '%');
            });
        }
        if ($request->has('user')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->user . '%');
            });
        }
        if ($request->has('subject')) {
            $query->where('subject', 'like', '%' . $request->subject . '%');
        }
        $tickets = $query->orderByDesc('created_at')->limit(200)->get()->map(function($t) {
            return [
                'id' => $t->id,
                'business_name' => $t->business->name ?? '',
                'user_name' => $t->user->name ?? '',
                'subject' => $t->subject,
                'message' => $t->message,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ];
        });
        return response()->json($tickets);
    }

    // Reply to a support ticket
    public function reply($id, Request $request)
    {
        $request->validate(['message' => 'required|string']);
        $ticket = SupportTicket::findOrFail($id);
        $reply = new SupportTicketReply();
        $reply->ticket_id = $ticket->id;
    $reply->user_id = (Auth::check() && Auth::user()) ? Auth::user()->id : 1; // super user fallback
        $reply->message = $request->message;
        $reply->created_at = now();
        $reply->save();
        $ticket->status = 'answered';
        $ticket->save();
        return response()->json(['success' => true]);
    }
}
