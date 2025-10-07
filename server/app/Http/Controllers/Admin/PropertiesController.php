<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Properties\PropertyStoreRequest;
use App\Http\Requests\Admin\Properties\PropertyPricingRuleStoreRequest;
use App\Http\Requests\Admin\Properties\PropertyCalendarRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Property;
use App\Models\PropertyPricingRule;
use App\Models\Service;
use App\Models\Booking;
use App\Services\PropertyService;
use App\Services\ServiceManagement\AdminServiceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class PropertiesController extends Controller
{
    public function __construct(
        private readonly PropertyService $props,
        private readonly AdminServiceManager $adminServiceManager
    ) {
        $this->middleware(['auth:api','user_type:admin','throttle:admin']);
    }

    // POST /api/v1/admin/properties
    public function store(PropertyStoreRequest $request)
    {
        try {
            $data = $request->validated();
            
                // استخدام AdminServiceManager لإنشاء الخدمة
                $providerId = is_array($data['provider_id'] ?? null) ? ($data['provider_id'][0] ?? null) : ($data['provider_id'] ?? null);
                
                if (!$providerId) {
                    return format_response(false, 'Provider ID is required', [], 422);
                }
                
                $service = $this->adminServiceManager->createServiceForProvider($data, (int)$providerId);
            
            return format_response(true, __('Created'), new ServiceResource($this->adminServiceManager->getService($service)));
            
        } catch (\Exception $e) {
            \Log::error("Admin property service creation failed", [
                'error' => $e->getMessage(),
                'provider_id' => $data['provider_id'] ?? null,
                'admin_id' => auth()->id()
            ]);
            
            return format_response(false, 'Failed to create property service', ['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/v1/admin/properties
    public function index(Request $request)
    {
        $v = Validator::make($request->all(), [
            'country_code' => 'nullable|in:SA,BH',
            'city_id' => 'nullable|integer|exists:cities,id',
            'region_id' => 'nullable|integer|exists:regions,id',
            'neigbourhood_id' => 'nullable|integer|exists:neigbourhoods,id',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid filters', $v->errors(), 422); }

        $q = Service::query()->with(['property','user:id,full_name','category:id'])
            ->whereHas('property')
            ->when($request->filled('city_id'), fn($qb)=>$qb->whereHas('property', fn($qq)=>$qq->where('city_id', $request->integer('city_id'))))
            ->when($request->filled('region_id'), fn($qb)=>$qb->whereHas('property', fn($qq)=>$qq->where('region_id', $request->integer('region_id'))))
            ->when($request->filled('neigbourhood_id'), fn($qb)=>$qb->whereHas('property', fn($qq)=>$qq->where('neigbourhood_id', $request->integer('neigbourhood_id'))))
            ->orderByDesc('id');

        $perPage = (int)($request->integer('per_page') ?: 20);
        $items = $q->paginate($perPage);
        return format_response(true, 'OK', $items);
    }

    // GET /api/v1/admin/properties/calendar
    public function calendar(PropertyCalendarRequest $request)
    {
        $validated = $request->validated();
        $property = Property::with('service')->findOrFail($validated['property_id']);
        $serviceId = $property->service_id;

        $from = $validated['date_from'] ? Carbon::parse($validated['date_from']) : now()->startOfMonth();
        $to = $validated['date_to'] ? Carbon::parse($validated['date_to']) : now()->endOfMonth();

        $bookings = Booking::query()
            ->where('service_id', $serviceId)
            ->where(function($qb) use ($from, $to) {
                $qb->whereBetween('start_date', [$from, $to])
                   ->orWhereBetween('end_date', [$from, $to])
                   ->orWhere(function($q) use ($from, $to){
                       $q->where('start_date','<=',$from)->where('end_date','>=',$to);
                   });
            })
            ->orderBy('start_date')
            ->get(['id','start_date','end_date','status']);

        $blocks = DB::table('availability_blocks')
            ->where('service_id', $serviceId)
            ->where(function($qb) use ($from, $to) {
                $qb->whereBetween('start_date', [$from, $to])
                   ->orWhereBetween('end_date', [$from, $to])
                   ->orWhere(function($q) use ($from, $to){
                       $q->where('start_date','<=',$from)->where('end_date','>=',$to);
                   });
            })
            ->get(['id','start_date','end_date','reason']);

        return format_response(true, 'OK', [
            'property' => $property->only(['id','property_name','address']),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'bookings' => $bookings,
            'blocks' => $blocks,
        ]);
    }

    // POST /api/v1/admin/properties/{property}/pricing-rules
    public function storePricingRule(Property $property, PropertyPricingRuleStoreRequest $request)
    {
        $rule = $property->pricingRules()->create($request->validated());
        return format_response(true, __('Created'), $rule);
    }

    // POST /api/v1/admin/properties/{property}/sync
    public function sync(Property $property)
    {
        dispatch(new \App\Jobs\SyncPropertyToChannelsJob($property->id));
        return format_response(true, __('Sync started'), ['queued' => true]);
    }

    // POST /api/v1/admin/properties/{property}/google-places-link
    public function linkGooglePlace(Property $property, Request $request)
    {
        $v = Validator::make($request->all(), [
            'place_id' => 'required|string',
        ]);
        if ($v->fails()) { return format_response(false, 'Invalid', $v->errors(), 422); }

        $property->update(['place_id' => $request->string('place_id')]);
        // Mirror to parent service for geo-search consistency if needed
        $property->service->update(['place_id' => $request->string('place_id')]);
        return format_response(true, __('Linked'), ['place_id' => $property->place_id]);
    }
}

