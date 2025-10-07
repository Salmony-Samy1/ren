<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Services\NotificationService;
use App\Models\User;

class ConversationsController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $conversations = Conversation::where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->with(['user1', 'user2', 'messages' => function ($query) {
                $query->latest()->take(1);
            }])
            ->get();
        return response()->json($conversations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $user1Id = auth()->id();
        $user2Id = $request->input('receiver_id');

        $conversation = Conversation::where(function ($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user1Id)->where('user2_id', $user2Id);
        })->orWhere(function ($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user2Id)->where('user2_id', $user1Id);
        })->first();

        if ($conversation) {
            return response()->json($conversation);
        }

        $newConversation = Conversation::create([
            'user1_id' => $user1Id,
            'user2_id' => $user2Id,
        ]);

        $this->notificationService->created([
            'user_id' => $user2Id,
            'action' => 'new_conversation',
            'message' => 'لديك محادثة جديدة من مستخدم.',
        ]);

        return response()->json($newConversation);
    }

    public function support(Request $request)
    {
        $user1Id = auth()->id();
        $user = User::where('email', "user@site.com")->first();
        $user2Id = $user->id;

        $conversation = Conversation::where(function ($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user1Id)->where('user2_id', $user2Id);
        })->orWhere(function ($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user2Id)->where('user2_id', $user1Id);
        })->first();

        if ($conversation) {
            return response()->json($conversation);
        }

        $newConversation = Conversation::create([
            'user1_id' => $user1Id,
            'user2_id' => $user2Id,
        ]);

        // Broadcast that a live support session has started
        event(new \App\Events\AdminRealtimeAlert('support.session.started', [
            'conversation_id' => $newConversation->id,
            'user_id' => $user1Id,
        ]));

        $this->notificationService->created([
            'user_id' => $user2Id,
            'action' => 'new_conversation',
            'message' => 'لديك محادثة جديدة من مستخدم.',
        ]);

        return response()->json($newConversation);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json(['message' => 'This method is not supported'], 405);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json(['message' => 'This method is not supported'], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Conversation $conversation)
    {
        if (auth()->user()->id !== $conversation->user1_id && auth()->user()->id !== $conversation->user2_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->delete();
        return response()->json(['message' => 'Conversation deleted successfully'], 200);
    }

}
