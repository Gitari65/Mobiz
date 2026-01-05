<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;

class ChatController extends Controller
{
    // GET /api/super/chats - list all chats for superuser
    public function index(Request $request)
    {
        $chats = Chat::where(function ($q) {
            $q->where('initiator_id', auth()->id())
              ->orWhere('recipient_id', auth()->id());
        })
        ->with('initiator', 'recipient')
        ->orderBy('updated_at', 'desc')
        ->paginate(20);

        return response()->json($chats);
    }

    // POST /api/super/chats - create or get existing chat
    public function store(Request $request)
    {
        $payload = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'nullable|string'
        ]);

        // Check if chat already exists
        $chat = Chat::where(function ($q) use ($payload) {
            $q->where('initiator_id', auth()->id())
              ->where('recipient_id', $payload['recipient_id']);
        })
        ->orWhere(function ($q) use ($payload) {
            $q->where('initiator_id', $payload['recipient_id'])
              ->where('recipient_id', auth()->id());
        })
        ->first();

        if (!$chat) {
            $chat = Chat::create([
                'initiator_id' => auth()->id(),
                'recipient_id' => $payload['recipient_id'],
                'subject' => $payload['subject'] ?? null,
                'status' => 'active'
            ]);
        }

        return response()->json($chat->load('initiator', 'recipient'), 201);
    }

    // GET /api/super/chats/{id} - get chat with messages
    public function show($id)
    {
        $chat = Chat::findOrFail($id);
        
        // Check authorization
        if (auth()->id() !== $chat->initiator_id && auth()->id() !== $chat->recipient_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $chat->load('initiator', 'recipient', 'messages.sender');

        // Mark messages as read
        ChatMessage::where('chat_id', $id)
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($chat);
    }

    // POST /api/super/chats/{id}/messages - send message
    public function sendMessage(Request $request, $id)
    {
        $chat = Chat::findOrFail($id);

        if (auth()->id() !== $chat->initiator_id && auth()->id() !== $chat->recipient_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $payload = $request->validate(['message' => 'required|string|max:5000']);

        $message = ChatMessage::create([
            'chat_id' => $id,
            'sender_id' => auth()->id(),
            'message' => $payload['message'],
            'is_read' => false
        ]);

        $chat->touch(); // Update chat timestamp for sorting

        return response()->json($message->load('sender'), 201);
    }

    // PATCH /api/super/chats/{id}/close - close chat
    public function closeChat($id)
    {
        $chat = Chat::findOrFail($id);

        if (auth()->id() !== $chat->initiator_id && auth()->id() !== $chat->recipient_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $chat->status = 'closed';
        $chat->save();

        return response()->json(['message' => 'Chat closed']);
    }

    // GET /api/super/chats/unread-count - get unread message count
    public function unreadCount()
    {
        $count = ChatMessage::whereHas('chat', function ($q) {
            $q->where(function ($subQ) {
                $subQ->where('initiator_id', auth()->id())
                      ->orWhere('recipient_id', auth()->id());
            });
        })
        ->where('sender_id', '!=', auth()->id())
        ->where('is_read', false)
        ->count();

        return response()->json(['unread_count' => $count]);
    }
}
