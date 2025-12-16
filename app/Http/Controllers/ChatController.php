<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * GET /chat/conversations
     */
    public function index()
    {
        $userId = auth()->id();

        $conversations = Conversation::where(fn ($q) =>
                $q->where('user_id_1', $userId)
                  ->orWhere('user_id_2', $userId)
            )
            ->with([
                'user1:id,name',
                'user2:id,name'
            ])
            ->withCount([
                'messages as unread_count' => fn ($q) =>
                    $q->whereNull('read_at')
                      ->where('user_id', '!=', $userId)
            ])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        $data = $conversations->getCollection()->map(function ($conv) use ($userId) {
            $otherUser = $conv->user_id_1 === $userId
                ? $conv->user2
                : $conv->user1;

            return [
                'id' => $conv->id,
                'other_user_name' => $otherUser->name,
                'last_message' => $conv->last_message,
                'last_message_at' => $conv->last_message_at,
                'unread_count' => $conv->unread_count,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
            ]
        ]);
    }

    /**
     * POST /chat/conversations
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $userId = auth()->id();
        $otherUserId = (int) $request->user_id;

        if ($userId === $otherUserId) {
            return response()->json(['message' => 'Invalid conversation'], 422);
        }

        $otherUser = User::find($otherUserId);
        if (!$otherUser || !$otherUser->is_active) {
            return response()->json(['message' => 'User unavailable'], 403);
        }

        // Always store users in consistent order (prevents duplicates)
        [$u1, $u2] = collect([$userId, $otherUserId])->sort()->values();

        $conversation = Conversation::firstOrCreate([
            'user_id_1' => $u1,
            'user_id_2' => $u2,
        ]);

        return response()->json([
            'id' => $conversation->id,
            'conversation' => $conversation
        ], 201);
    }

    /**
     * GET /chat/conversations/{id}/messages
     */
    public function messages($id)
    {
        $conversation = Conversation::find($id);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        if (!in_array(auth()->id(), [$conversation->user_id_1, $conversation->user_id_2])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = Message::where('conversation_id', $id)
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'user_id' => $msg->user_id,
                'message' => $msg->message,
                'type' => $msg->type,
                'is_sent' => $msg->user_id === auth()->id(),
                'created_at' => $msg->created_at,
            ]);

        Message::where('conversation_id', $id)
            ->whereNull('read_at')
            ->where('user_id', '!=', auth()->id())
            ->update(['read_at' => now()]);

        return response()->json(['data' => $messages]);
    }

    /**
     * POST /chat/conversations/{id}/messages
     */
    public function send(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:2000'
        ]);

        $conversation = Conversation::find($id);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        if (!in_array(auth()->id(), [$conversation->user_id_1, $conversation->user_id_2])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'type' => 'text'
        ]);

        $conversation->update([
            'last_message' => $message->message,
            'last_message_at' => $message->created_at
        ]);

        return response()->json([
            'id' => $message->id,
            'message' => $message->message,
            'created_at' => $message->created_at
        ], 201);
    }
}
