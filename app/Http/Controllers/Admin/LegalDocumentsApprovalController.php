<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewLegalDocumentRequest;
use App\Models\CompanyLegalDocument;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class LegalDocumentsApprovalController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }
    public function index(Request $request)
    {
        $q = CompanyLegalDocument::query()->with(['companyProfile', 'mainService'])
            ->when($request->filled('status'), fn($qb) => $qb->where('status', $request->get('status')))
            ->orderByDesc('created_at');

        $docs = $q->paginate(20);
        return response()->json(['data' => $docs]);
    }

    public function approve(CompanyLegalDocument $document, Request $request)
    {
        $document->update([
            'status' => ReviewStatus::APPROVED,
            'approved_at' => now(),
            'review_notes' => $request->get('review_notes'),
        ]);
        $this->notificationService->created([
            'user_id' => Auth::user()->id,
            'action' => 'new_message',
            'message' => 'تم قبول اوراق اعتمادة الخدمة الرئيسية',
        ]);
        return response()->json(['success' => true, 'data' => $document]);
    }

    public function reject(CompanyLegalDocument $document, Request $request)
    {
        $request->validate(['review_notes' => 'required|string|max:500']);
        $document->update([
            'status' => ReviewStatus::REJECTED,
            'review_notes' => $request->get('review_notes'),
        ]);
        $this->notificationService->created([
            'user_id' => Auth::user()->id,
            'action' => 'new_message',
            'message' => 'تم رفض اوراق اعتمادة الخدمة الرئيسية بسبب ' . $request->get('review_notes'),
        ]);
        return response()->json(['success' => true, 'data' => $document]);
    }
}

