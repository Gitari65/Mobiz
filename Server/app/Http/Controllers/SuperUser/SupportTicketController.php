<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket; // create model/migration later
use App\Models\AuditLog;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::query();
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($qr) use ($q) {
                $qr->where('subject','like','%'.$q.'%')->orWhere('message','like','%'.$q.'%');
            });
        }
        $tickets = $query->orderBy('created_at','desc')->paginate(25);
        return response()->json($tickets);
    }

    public function reply(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $data = $request->validate(['message'=>'required|string']);
        // In production you'd create a SupportReply model, send notifications, etc.
        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'message' => $data['message']
        ]);
        AuditLog::create([
            'action'=>'support_reply',
            'user_id'=>$request->user()->id,
            'user_name'=>$request->user()->name,
            'auditable_type'=>SupportTicket::class,
            'auditable_id'=>$ticket->id,
            'notes'=>'Support reply added'
        ]);
        return response()->json(['message'=>'Reply added']);
    }

    public function resolve(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->status = 'resolved';
        $ticket->resolved_by = $request->user()->id;
        $ticket->resolved_at = now();
        $ticket->save();
        AuditLog::create([
            'action'=>'support_resolve',
            'user_id'=>$request->user()->id,
            'user_name'=>$request->user()->name,
            'auditable_type'=>SupportTicket::class,
            'auditable_id'=>$ticket->id,
            'notes'=>'Ticket resolved'
        ]);
        return response()->json(['message'=>'Ticket marked as resolved']);
    }
}
