<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;

class ChatController extends Controller
{
    // GET /api/chats - list all chats for user
    public function index(Request $request)
    {
        $chats = Chat::where(function ($q) {
            $q->where('initiator_id', auth()->id())
              ->orWhere('recipient_id', auth()->id());
        })
        ->with([
            'initiator',
            'recipient',
            'messages' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(1);
            }
        ])
        ->orderBy('updated_at', 'desc')
        ->paginate(20);

        return response()->json($chats);
    }

    // GET /api/chats/available-users - get users current user can chat with
    public function getAvailableUsers(Request $request)
    {
        $currentUser = auth()->user()->load('role');
        $currentRole = strtolower($currentUser->role->name ?? '');
        $query = User::where('id', '!=', auth()->id());

        if ($currentRole === 'superuser') {
            // Superuser can chat with all users
            $users = $query->select('id', 'name', 'email')->get();
        } elseif ($currentRole === 'admin') {
            // Admin can chat with cashiers from same company and other admins
            $users = $query->where(function ($q) use ($currentUser) {
                $q->where(function ($subQ) use ($currentUser) {
                    $subQ->where('company_id', $currentUser->company_id)
                         ->whereHas('role', function ($roleQ) {
                            $roleQ->where('name', 'cashier');
                         });
                })->orWhereHas('role', function ($roleQ) {
                    $roleQ->where('name', 'admin');
                });
            })->select('id', 'name', 'email')->get();
        } elseif ($currentRole === 'cashier') {
            // Cashier can chat with other cashiers and admin from same company
            $users = $query->where('company_id', $currentUser->company_id)
                ->whereHas('role', function ($q) {
                    $q->whereIn('name', ['cashier', 'admin']);
                })
                ->select('id', 'name', 'email')->get();
        } else {
            $users = collect();
        }

        return response()->json(['data' => $users]);
    }

    // POST /api/super/chats - create or get existing chat
    public function store(Request $request)
    {
        $payload = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'nullable|string'
        ]);

        $currentUser = auth()->user();
        $recipient = User::find($payload['recipient_id']);

        // Check access control based on role
        if (!$this->canChatWith($currentUser, $recipient)) {
            return response()->json(['error' => 'You cannot chat with this user'], 403);
        }

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
        $userId = auth()->id();
        $cacheKey = 'unread_count_user_' . $userId;
        $count = \Cache::remember($cacheKey, now()->addSeconds(15), function () use ($userId) {
            return ChatMessage::whereHas('chat', function ($q) use ($userId) {
                $q->where(function ($subQ) use ($userId) {
                    $subQ->where('initiator_id', $userId)
                          ->orWhere('recipient_id', $userId);
                });
            })
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
        });
        return response()->json(['unread_count' => $count]);
    }

    /**
     * Check if current user can chat with another user based on role
     * Superuser can chat with anyone
     * Admin can chat with cashiers from their company
     * Cashier can chat with cashiers from same company
     */
    private function canChatWith(User $currentUser, User $recipient)
    {
        // Load roles
        $currentUser->load('role');
        $recipient->load('role');

        $currentRole = strtolower($currentUser->role->name ?? '');
        $recipientRole = strtolower($recipient->role->name ?? '');

        // Superuser can chat with anyone
        if ($currentRole === 'superuser') {
            return true;
        }

        // Admin can chat with cashiers from their company, and other admins
        if ($currentRole === 'admin') {
            // Can't chat with themselves
            if ($currentUser->id === $recipient->id) {
                return false;
            }
            // Can chat with cashiers from same company
            if ($recipientRole === 'cashier' && $currentUser->company_id === $recipient->company_id) {
                return true;
            }
            // Can chat with other admins
            if ($recipientRole === 'admin') {
                return true;
            }
            return false;
        }

        // Cashier can chat with other cashiers from same company and admins from their company
        if ($currentRole === 'cashier') {
            // Can't chat with themselves
            if ($currentUser->id === $recipient->id) {
                return false;
            }
            // Can chat with cashiers from same company
            if ($recipientRole === 'cashier' && $currentUser->company_id === $recipient->company_id) {
                return true;
            }
            // Can chat with admin from same company
            if ($recipientRole === 'admin' && $currentUser->company_id === $recipient->company_id) {
                return true;
            }
            return false;
        }

        // Default: deny chat
        return false;
    }
}
