<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventBrowseController extends Controller
{
    public function today(Request $request)
    {
        $today = Carbon::today();
        $services = Service::with(['category','user','event','property','restaurant'])
            ->whereHas('event', function($q) use ($today){
                $q->whereDate('start_at', '<=', $today)
                  ->whereDate('end_at', '>=', $today);
            })
            ->when($request->filled('gender_type'), fn($q) => $q->whereHas('event', fn($e) => $e->where('gender_type', $request->gender_type)))
            ->when($request->filled('city_id'), fn($q) => $q->where('city_id', $request->city_id))
            ->when($request->filled('district'), fn($q) => $q->where('district', 'like', '%'.$request->district.'%'))
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->where('is_approved', true)
            ->latest()
            ->paginate($request->input('per_page', 20));

        $services->setCollection($services->getCollection()->map(fn($s) => new \App\Http\Resources\ServiceResource($s)));
        return response()->json(['success' => true, 'data' => $services]);
    }

    public function tomorrow(Request $request)
    {
        $tomorrow = Carbon::tomorrow();
        $services = Service::with(['category','user','event','property','restaurant'])
            ->whereHas('event', function($q) use ($tomorrow){
                $q->whereDate('start_at', '<=', $tomorrow)
                  ->whereDate('end_at', '>=', $tomorrow);
            })
            ->when($request->filled('gender_type'), fn($q) => $q->whereHas('event', fn($e) => $e->where('gender_type', $request->gender_type)))
            ->when($request->filled('city_id'), fn($q) => $q->where('city_id', $request->city_id))
            ->when($request->filled('district'), fn($q) => $q->where('district', 'like', '%'.$request->district.'%'))
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->where('is_approved', true)
            ->latest()
            ->paginate($request->input('per_page', 20));

        $services->setCollection($services->getCollection()->map(fn($s) => new \App\Http\Resources\ServiceResource($s)));
        return response()->json(['success' => true, 'data' => $services]);
    }

    public function betweenDates(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);
        $from = Carbon::parse($request->input('from'));
        $to = Carbon::parse($request->input('to'));

        $services = Service::with(['category','user','event','property','restaurant'])
            ->whereHas('event', function($q) use ($from,$to){
                $q->whereDate('start_at', '<=', $to)
                  ->whereDate('end_at', '>=', $from);
            })
            ->when($request->filled('gender_type'), fn($q) => $q->whereHas('event', fn($e) => $e->where('gender_type', $request->gender_type)))
            ->when($request->filled('city_id'), fn($q) => $q->where('city_id', $request->city_id))
            ->when($request->filled('district'), fn($q) => $q->where('district', 'like', '%'.$request->district.'%'))
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->where('is_approved', true)
            ->latest()
            ->paginate($request->input('per_page', 20));

        $services->setCollection($services->getCollection()->map(fn($s) => new \App\Http\Resources\ServiceResource($s)));
        return response()->json(['success' => true, 'data' => $services]);
    }
}

