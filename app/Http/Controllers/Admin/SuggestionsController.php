<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\Request;

class SuggestionsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Suggestion::class);
        $perPage = (int)$request->integer('per_page', 20);
        $q = Suggestion::with(['user:id,full_name,email', 'reviewer:id,full_name,email'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) { $q->where('status', $request->get('status')); }
        if ($request->filled('priority')) { $q->where('priority', $request->get('priority')); }
        if ($request->filled('reviewed_by')) { $q->where('reviewed_by', $request->integer('reviewed_by')); }
        if ($request->filled('q')) { $q->where('title', 'like', '%'.$request->get('q').'%'); }

        $p = $q->paginate($perPage)->withQueryString();
        
        // Add attachment count to each suggestion
        $p->getCollection()->each(function ($suggestion) {
            $suggestion->attachments_count = $suggestion->getMedia('attachments')->count();
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

    public function show(Suggestion $suggestion)
    {
        $this->authorize('view', $suggestion);
        $suggestion->load(['user:id,full_name,email', 'reviewer:id,full_name,email']);
        
        // Load attachments
        $attachments = $suggestion->getMedia('attachments')->map(function ($media) {
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
            'suggestion' => $suggestion,
            'attachments' => $attachments,
        ]);
    }

    public function updateStatus(Request $request, Suggestion $suggestion)
    {
        $this->authorize('update', $suggestion);
        
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,implemented',
            'admin_notes' => 'nullable|string|max:1000',
        ]);
        
        $suggestion->update([
            'status' => $request->status,
            'reviewed_by' => auth('api')->id(),
            'reviewed_at' => now(),
            'admin_notes' => $request->admin_notes,
        ]);
        
        return format_response(true, __('Updated'), $suggestion->fresh());
    }

    public function assign(Request $request, Suggestion $suggestion)
    {
        $this->authorize('update', $suggestion);
        
        $request->validate([
            'reviewed_by' => 'required|exists:users,id',
        ]);
        
        $suggestion->update([
            'reviewed_by' => $request->reviewed_by,
            'status' => 'in_review',
        ]);
        
        return format_response(true, __('Updated'), $suggestion->load('reviewer:id,full_name,email'));
    }
}
