<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Support\SupportTicketAssignRequest;
use App\Http\Requests\Admin\Support\SupportTicketReplyRequest;
use App\Http\Requests\Admin\Support\SupportTicketStoreRequest;
use App\Http\Requests\Admin\Support\SupportTicketUpdateStatusRequest;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\Request;

class SupportTicketsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SupportTicket::class);
        $perPage = (int)$request->integer('per_page', 20);
        $q = SupportTicket::with(['user:id,full_name,email', 'assignee:id,full_name,email'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) { $q->where('status', $request->get('status')); }
        if ($request->filled('priority')) { $q->where('priority', $request->get('priority')); }
        if ($request->filled('assigned_to')) { $q->where('assigned_to', $request->integer('assigned_to')); }
        if ($request->filled('q')) { $q->where('subject', 'like', '%'.$request->get('q').'%'); }

        $p = $q->paginate($perPage)->withQueryString();
        
        // Add attachment count to each ticket
        $p->getCollection()->each(function ($ticket) {
            $ticket->attachments_count = $ticket->getMedia('attachments')->count();
        });
        
        return format_response(true, __('Fetched successfully'), [
            'items' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
            ],
        ]);
    }

    public function store(SupportTicketStoreRequest $request)
    {
        $this->authorize('create', SupportTicket::class);
        $data = $request->validated();
        $ticket = SupportTicket::create($data);
        // Broadcast urgent complaints as realtime alerts
        if (($ticket->priority ?? 'normal') === 'urgent') {
            event(new \App\Events\AdminRealtimeAlert('complaint.urgent', [
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->user_id,
                'subject' => $ticket->subject,
            ]));
        }
        return format_response(true, __('Created'), $ticket);
    }

    public function show(SupportTicket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->load(['user:id,full_name,email', 'assignee:id,full_name,email', 'replies.user:id,full_name,email']);
        
        // Load attachments
        $attachments = $ticket->getMedia('attachments')->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb'),
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'created_at' => $media->created_at,
            ];
        });
        
        return format_response(true, __('Fetched successfully'), [
            'ticket' => $ticket,
            'attachments' => $attachments,
        ]);
    }

    public function addReply(SupportTicket $ticket, SupportTicketReplyRequest $request)
    {
        $this->authorize('update', $ticket);
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth('api')->id(),
            'message' => $request->validated()['message']
        ]);
        return format_response(true, __('Created'), $reply->load('user:id,full_name,email'));

        // Broadcast urgent complaints as realtime alerts
        if (($ticket->priority ?? 'normal') === 'urgent') {
            event(new \App\Events\AdminRealtimeAlert('complaint.urgent', [
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->user_id,
                'subject' => $ticket->subject,
            ]));
        }

    }

    public function assign(SupportTicket $ticket, SupportTicketAssignRequest $request)
    {
        $this->authorize('update', $ticket);
        $ticket->update(['assigned_to' => $request->validated()['assigned_to'], 'status' => 'in_progress']);
        return format_response(true, __('Updated'), $ticket->load('assignee:id,full_name,email'));
    }

    public function updateStatus(SupportTicket $ticket, SupportTicketUpdateStatusRequest $request)
    {
        $this->authorize('update', $ticket);
        $status = $request->validated()['status'];
        $payload = ['status' => $status];
        if ($status === 'closed') { $payload['closed_at'] = now(); }
        $ticket->update($payload);
        return format_response(true, __('Updated'), $ticket->fresh());
    }
}

