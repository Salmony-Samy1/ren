<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Service;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Service $service)
    {
        \Gate::authorize('view', $service);
        $blocks = AvailabilityBlock::where('service_id', $service->id)
            ->orderByDesc('start_date')
            ->paginate(20);
        return format_response(true, __('Fetched successfully'), $blocks);
    }

    public function store(Request $request, Service $service)
    {
        \Gate::authorize('update', $service);
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
        ]);

        $block = AvailabilityBlock::create([
            'service_id' => $service->id,
            'created_by' => $request->user()->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json($block, 201);
    }

    public function destroy(Service $service, AvailabilityBlock $block)
    {
        \Gate::authorize('update', $service);
        abort_unless($block->service_id === $service->id, 404);
        $block->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

