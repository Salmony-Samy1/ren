<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Message;
use App\Models\Service;
use App\Models\Conversation;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardActionsController extends Controller
{
    public function __construct(private readonly AlertService $alerts) {}

    // POST /support/quick-response
    public function quickResponse(Request $request)
    {
        $user = auth('api')->user();
        abort_unless($user && $user->type === 'admin', 403);

        $v = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:conversations,id',
            'template' => 'required|string|max:500',
        ]);
        if ($v->fails()) { return response()->json(['success' => false, 'message' => 'Invalid', 'errors' => $v->errors()], 422); }

        $conv = Conversation::findOrFail($request->integer('conversation_id'));
        // Send from admin (current) to the other participant (UI will show as support)
        Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user->id,
            'message_content' => (string) $request->string('template'),
        ]);

        // Optional alert signal
        $this->alerts->raise('support.quick_response', 'info', 'Quick response sent', ['conversation_id' => $conv->id, 'admin_id' => $user->id], $user->id);

        return response()->json(['success' => true]);
    }

    // POST /services/{service}/reactivate
    public function reactivateService(Service $service)
    {
        $user = auth('api')->user();
        abort_unless($user && $user->type === 'admin', 403);

        // Minimal policy: mark approved and clear any soft-deactivation flags if exist
        $service->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        $this->alerts->raise('service.reactivated', 'warning', 'Service reactivated', ['service_id' => $service->id]);

        return response()->json(['success' => true, 'data' => ['service_id' => $service->id]]);
    }

    // POST /alerts/{alert}/acknowledge
    public function acknowledge(Alert $alert)
    {
        $user = auth('api')->user();
        abort_unless($user && $user->type === 'admin', 403);
        $this->alerts->acknowledge($alert, $user->id);
        return response()->json(['success' => true]);
    }
}

