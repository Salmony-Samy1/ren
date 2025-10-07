<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointsLedger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PointsLedgerReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'type' => 'nullable|string|in:earn,spend,expire,adjust',
            'source' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $perPage = (int) ($request->integer('per_page') ?: 50);

        $q = PointsLedger::query()->with('user:id,full_name,email');
        if ($request->filled('user_id')) $q->where('user_id', $request->integer('user_id'));
        if ($request->filled('type')) $q->where('type', $request->string('type'));
        if ($request->filled('source')) $q->where('source', $request->string('source'));
        if ($request->filled('date_from')) $q->where('created_at', '>=', $request->date('date_from'));
        if ($request->filled('date_to')) $q->where('created_at', '<=', $request->date('date_to'));

        $paginator = $q->orderByDesc('created_at')->paginate($perPage);
        $data = $paginator->getCollection()->map(function (PointsLedger $r) {
            return [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'user_name' => optional($r->user)->name,
                'user_email' => optional($r->user)->email,
                'type' => $r->type,
                'points' => (int) $r->points,
                'source' => $r->source,
                'meta' => $r->meta,
                'expires_at' => optional($r->expires_at)->toDateTimeString(),
                'created_at' => $r->created_at->toDateTimeString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $fileName = 'points_ledger_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ];

        $callback = function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id','user_id','user_name','user_email','type','points','source','expires_at','created_at']);

            $q = PointsLedger::query()->with('user:id,name,email');
            if ($request->filled('user_id')) $q->where('user_id', $request->integer('user_id'));
            if ($request->filled('type')) $q->where('type', $request->string('type'));
            if ($request->filled('source')) $q->where('source', $request->string('source'));
            if ($request->filled('date_from')) $q->where('created_at', '>=', $request->date('date_from'));
            if ($request->filled('date_to')) $q->where('created_at', '<=', $request->date('date_to'));

            $q->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->id,
                        $r->user_id,
                        optional($r->user)->name,
                        optional($r->user)->email,
                        $r->type,
                        (int)$r->points,
                        $r->source,
                        optional($r->expires_at)->toDateTimeString(),
                        $r->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}

