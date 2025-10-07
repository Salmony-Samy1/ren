<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\NewMessage;
use App\Services\NotificationService;

class MessagesController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Conversation $conversation)
    {
        if (auth()->user()->id != $conversation->user1_id && auth()->user()->id != $conversation->user2_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    
        return response()->json($conversation->messages()->with('sender')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Conversation $conversation)
    {
        if (auth()->user()->id != $conversation->user1_id && auth()->user()->id != $conversation->user2_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message_content' => 'required|string',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'message_content' => $request->input('message_content'),
        ]);

        event(new NewMessage($message));

        $receiver = $conversation->user1->id === auth()->id() ? $conversation->user2 : $conversation->user1;
        $this->notificationService->created([
            'user_id' => $receiver->id,
            'action' => 'new_message',
            'message' => 'لديك رسالة جديدة في المحادثة.',
        ]);

        return response()->json($message);
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
        return response()->json(['message' => 'Updating a message is not allowed'], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Conversation $conversation, Message $message)
    {
        return response()->json(['message' => 'Deleting a message is not allowed'], 405);
    }
}
