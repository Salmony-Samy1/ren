<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthenticationLog;
use App\Models\TwoFactorSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SecurityCenterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['force.guard.api','auth:api','user_type:admin','throttle:admin']);
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


    // GET /security/activity-log
    public function activityLog(Request $request)
    {
        abort_unless($this->tokenHas('security.view') || auth('api')->user()?->can('security.view'), 403);

        $v = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'login_successful' => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }

        $q = AuthenticationLog::query()->orderByDesc('login_at');
        if ($request->filled('user_id')) { $q->where('authenticatable_id', $request->integer('user_id')); }
        if ($request->filled('login_successful')) { $q->where('login_successful', $request->boolean('login_successful')); }
        if ($request->filled('date_from')) { $q->where('login_at', '>=', $request->date('date_from')); }
        if ($request->filled('date_to')) { $q->where('login_at', '<=', $request->date('date_to')); }

        $perPage = (int)($request->integer('per_page') ?: 50);
        $logs = $q->paginate($perPage);
        return format_response(true, 'OK', $logs);
    }

    // GET /security/2fa-status
    public function twoFaStatus()
    {
        abort_unless($this->tokenHas('security.view') || auth('api')->user()?->can('security.view'), 403);

        $byStatus = TwoFactorSetting::query()
            ->selectRaw('enabled, COUNT(*) as cnt')
            ->groupBy('enabled')->pluck('cnt','enabled');
        return format_response(true, 'OK', [
            'enabled' => (int)($byStatus[1] ?? 0),
            'disabled' => (int)($byStatus[0] ?? 0),
        ]);
    }
}

