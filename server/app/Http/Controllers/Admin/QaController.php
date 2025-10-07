<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QualityReview;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class QaController extends Controller
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

    // GET /qa/kpis
    public function kpis(Request $request)
    {
        abort_unless($this->tokenHas('qa.view') || auth('api')->user()?->can('qa.view'), 403);

        $v = Validator::make($request->all(), [
            'service_id' => 'nullable|exists:services,id',
            'provider_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }

        $q = QualityReview::query()->with('reviewer');
        if ($request->filled('service_id')) { $q->where('reviewable_type', Service::class)->where('reviewable_id', $request->integer('service_id')); }
        if ($request->filled('provider_id')) {
            $q->whereHasMorph('reviewable', [Service::class], function($qb) use ($request){
                $qb->where('user_id', $request->integer('provider_id'));
            });
        }
        if ($request->filled('date_from')) { $q->where('created_at', '>=', $request->date('date_from')); }
        if ($request->filled('date_to')) { $q->where('created_at', '<=', $request->date('date_to')); }

        $avgByKpi = (clone $q)->selectRaw('kpi, AVG(score) as avg_score, COUNT(*) as samples')->groupBy('kpi')->get();
        return format_response(true, 'OK', ['kpi' => $avgByKpi]);
    }

    // POST /qa/reviews
    public function storeReview(Request $request)
    {
        abort_unless($this->tokenHas('qa.manage') || auth('api')->user()?->can('qa.manage'), 403);

        $v = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'score' => 'required|integer|min:0|max:100',
            'kpi' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }

        $r = QualityReview::create([
            'reviewable_type' => Service::class,
            'reviewable_id' => $request->integer('service_id'),
            'reviewer_id' => auth('api')->id(),
            'score' => $request->integer('score'),
            'kpi' => $request->string('kpi'),
            'notes' => $request->string('notes'),
        ]);
        return format_response(true, __('Created'), $r);
    }
}

