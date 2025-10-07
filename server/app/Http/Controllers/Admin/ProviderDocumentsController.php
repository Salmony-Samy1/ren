<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyLegalDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProviderDocumentsController extends Controller
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


    // POST /providers/{provider}/documents
    public function store(User $provider, Request $request)
    {
        abort_unless($this->tokenHas('legal.docs.manage') || auth('api')->user()?->can('legal.docs.manage'), 403);

        $v = Validator::make($request->all(), [
            'main_service_id' => 'required|exists:main_services,id',
            'doc_type' => 'required|in:tourism_license,commercial_registration,food_safety_cert,catering_permit',
            'file' => 'required|file',
            'expires_at' => 'nullable|date',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }

        $path = $request->file('file')->store('legal_docs/'.$provider->id, 'public');
        $doc = CompanyLegalDocument::create([
            'company_profile_id' => $provider->companyProfile?->id,
            'main_service_id' => $request->integer('main_service_id'),
            'doc_type' => (string) $request->string('doc_type'),
            'file_path' => $path,
            'expires_at' => $request->date('expires_at'),
            'status' => \App\Enums\ReviewStatus::PENDING,
        ]);
        return format_response(true, __('Uploaded'), $doc);
    }

    // GET /providers/{provider}/documents
    public function index(User $provider)
    {
        abort_unless($this->tokenHas('legal.docs.view') || auth('api')->user()?->can('legal.docs.view'), 403);

        $docs = CompanyLegalDocument::with(['mainService'])
            ->where('company_profile_id', $provider->companyProfile?->id)
            ->orderByDesc('created_at')
            ->paginate(20);
        return format_response(true, 'OK', $docs);
    }
}

