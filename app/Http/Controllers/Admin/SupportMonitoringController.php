<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SupportMonitoringController extends Controller
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


    // GET /support/performance-report
    public function performanceReport(Request $request)
    {
        abort_unless($this->tokenHas('support.monitoring.view') || auth('api')->user()?->can('support.monitoring.view'), 403);

        $v = Validator::make($request->all(), [
            'agent_id' => 'nullable|integer|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }

        $from = $request->date('date_from') ?: now()->subDays(30);
        $to = $request->date('date_to') ?: now();
        $q = SupportTicket::query()
            ->leftJoin('ticket_replies as tr','tr.ticket_id','=','support_tickets.id')
            ->when(config('database.default') !== 'sqlite', fn($qb)=>$qb->whereBetween('support_tickets.created_at', [$from, $to]))
            ->when($request->filled('agent_id'), fn($qb)=>$qb->where('support_tickets.assigned_to',$request->integer('agent_id')))
            ->groupBy('support_tickets.assigned_to')
            ->selectRaw(config('database.default') === 'sqlite'
                ? 'support_tickets.assigned_to as agent_id, COUNT(DISTINCT support_tickets.id) as tickets, 0 as avg_resolution_minutes, COUNT(tr.id) as replies'
                : 'support_tickets.assigned_to as agent_id, COUNT(DISTINCT support_tickets.id) as tickets, AVG(TIMESTAMPDIFF(MINUTE, support_tickets.created_at, support_tickets.closed_at)) as avg_resolution_minutes, COUNT(tr.id) as replies')
            ->orderByDesc('tickets')
            ->get();

        // SQLite compatibility for AVG(TIMESTAMPDIFF...)
        if (config('database.default') === 'sqlite') {
            $rows = SupportTicket::query()
                ->when($request->filled('agent_id'), fn($qb)=>$qb->where('assigned_to',$request->integer('agent_id')))
                ->whereBetween('created_at', [$from, $to])
                ->get();
            $stats = $rows->groupBy('assigned_to')->map(function($g){
                $tickets = $g->count();
                $avg = $g->filter(fn($t)=>$t->closed_at)->avg(fn($t)=>$t->created_at->diffInMinutes($t->closed_at));
                return ['tickets' => $tickets, 'avg_resolution_minutes' => (float)round($avg,2), 'replies' => $g->sum(fn($t)=>$t->replies()->count())];
            })->map(fn($row,$agent)=>array_merge(['agent_id'=>$agent],$row))->values();
        } else {
            $stats = $q;
        }

        return format_response(true, 'OK', ['agents' => $stats]);
    }
}

