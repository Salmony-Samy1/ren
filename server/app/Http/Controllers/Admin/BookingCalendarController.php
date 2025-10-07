<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingCalendarController extends Controller
{
    /**
     * عرض التقويم العام لجميع الحجوزات مع ترشيحات
     */
    public function index(Request $request)
    {
        $v = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|string|in:pending,confirmed,completed,cancelled,refunded',
            'service_id' => 'nullable|integer',
            'provider_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);
        if ($v->fails()) {
            return format_response(false, 'بيانات غير صحيحة', $v->errors(), 422);
        }

        $perPage = (int) ($request->integer('per_page') ?: 50);

        $q = Booking::query()
            ->with(['service:id,user_id,name', 'service.user:id,full_name', 'user:id,full_name,email'])
            ->orderBy('start_date');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('service_id')) {
            $q->where('service_id', $request->integer('service_id'));
        }
        if ($request->filled('provider_id')) {
            $q->whereHas('service', function ($qq) use ($request) {
                $qq->where('user_id', $request->integer('provider_id'));
            });
        }
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            // تداخل مع النطاق: يبدأ قبل نهاية النطاق وينتهي بعد بداية النطاق
            $from = $request->date('date_from');
            $to = $request->date('date_to');
            $q->where(function ($qq) use ($from, $to) {
                $qq->where('start_date', '<=', $to)
                   ->where('end_date', '>=', $from);
            });
        } elseif ($request->filled('date_from')) {
            $from = $request->date('date_from');
            $q->where('end_date', '>=', $from);
        } elseif ($request->filled('date_to')) {
            $to = $request->date('date_to');
            $q->where('start_date', '<=', $to);
        }

        $paginator = $q->paginate($perPage);

        $data = $paginator->getCollection()->map(function (Booking $b) {
            return [
                'id' => $b->id,
                'start' => optional($b->start_date)->toIso8601String(),
                'end' => optional($b->end_date)->toIso8601String(),
                'status' => $b->status,
                'service_id' => $b->service_id,
                'service_name' => optional($b->service)->name,
                'provider_id' => optional($b->service)->user_id,
                'provider_name' => optional(optional($b->service)->user)->name,
                'user_id' => $b->user_id,
                'user_name' => optional($b->user)->name,
                'user_email' => optional($b->user)->email,
                'total' => $b->total,
                'privacy' => $b->privacy,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الحجوزات بنجاح',
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}

