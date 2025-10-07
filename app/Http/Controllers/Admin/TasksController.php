<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamTask;
use App\Models\TeamTaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TasksController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api','user_type:admin','throttle:admin']);
    }

    private function tokenHas(string $permission): bool
    {
        try {
            $payload = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->getPayload();
            $perms = (array) ($payload->get('permissions') ?? []);
            return in_array($permission, $perms, true);
        } catch (\Throwable $e) {
            return false;
        }
    }


    // GET/POST /tasks
    public function index(Request $request)
    {
        abort_unless($this->tokenHas('tasks.view'), 403);

        $q = TeamTask::with(['creator:id,full_name','assignee:id,full_name'])
            ->orderByDesc('created_at');
        if ($request->filled('status')) { $q->where('status', $request->string('status')); }
        return format_response(true, 'OK', $q->paginate(20));
    }

    public function store(Request $request)
    {
        abort_unless($this->tokenHas('tasks.manage'), 403);

        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }
        $task = TeamTask::create([
            'title' => (string) $request->string('title'),
            'description' => (string) $request->string('description'),
            'assigned_to' => $request->filled('assigned_to') ? $request->integer('assigned_to') : null,
            'priority' => (string) ($request->string('priority') ?: 'normal'),
            'created_by' => auth('api')->id(),
        ]);
        return format_response(true, __('Created'), $task);
    }

    // PATCH /tasks/{task}/status
    public function updateStatus(TeamTask $task, Request $request)
    {
        abort_unless($this->tokenHas('tasks.manage'), 403);

        $v = Validator::make($request->all(), [
            'status' => 'required|in:open,in_progress,blocked,done,cancelled',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }
        $task->update(['status' => $request->string('status')]);
        return format_response(true, __('Updated'), $task);
    }

    // POST /tasks/{task}/comments
    public function addComment(TeamTask $task, Request $request)
    {
        abort_unless($this->tokenHas('tasks.manage'), 403);

        $v = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }
        $c = TeamTaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth('api')->id(),
            'comment' => $request->string('comment'),
        ]);
        return format_response(true, __('Created'), $c);
    }
}

