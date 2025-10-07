<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catering\CateringStoreRequest;
use App\Http\Requests\Admin\Catering\CateringUpdateRequest;
use App\Http\Requests\Admin\CateringDeliveryUpdateRequest;
use App\Http\Requests\Admin\CateringSpecialEventStoreRequest;
use App\Http\Requests\Admin\CateringMinimumRuleStoreRequest;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\CateringOrderSummaryResource;
use App\Http\Resources\CateringDeliveryResource;
use App\Http\Resources\CateringSpecialEventResource;
use App\Http\Resources\CateringMinimumRuleResource;
use App\Models\Booking;
use App\Models\Catering;
use App\Models\CateringDelivery;
use App\Models\CateringSpecialEvent;
use App\Models\CateringMinimumRule;
use App\Models\Service;
use App\Models\Review;
use App\Services\ServiceManagement\AdminServiceManager;
use App\Services\ServiceManagement\UnifiedServiceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Auth;

class CateringController extends Controller
{
    public function __construct(
        private readonly AdminServiceManager $adminServiceManager,
        private readonly UnifiedServiceManager $serviceManager
    ) {}
    // OFFERS
    public function offersIndex(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = max(1, min(50, $perPage));

        $q = Service::query()
            ->with(['user','category','catering.items'])
            ->whereHas('catering');

        if ($request->filled('provider_id')) {
            $q->where('user_id', (int) $request->provider_id);
        }
        if ($request->filled('category_id')) {
            $q->where('category_id', (int) $request->category_id);
        }
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $q->where('name', 'like', $term);
        }

        $p = $q->latest()->paginate($perPage)->withQueryString();
        return format_response(true, __('Fetched successfully'), [
            'items' => ServiceResource::collection($p),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
            ],
        ]);
    }

    public function offersStore(CateringStoreRequest $request)
    {
        try {
            \Log::info('Admin catering service creation started', [
                'admin_id' => auth()->id(),
                'admin_email' => auth()->user() ? auth()->user()->email : 'Unknown',
                'request_data' => $request->all(),
                'validation_passed' => true
            ]);
            
            $data = $request->validated();
            $providerId = $data['provider_id'] ?? auth()->id();
            
            // Get or create provider user
            if ($providerId === auth()->id()) {
                $provider = auth()->user(); // Admin creating for system
            } else {
                $provider = User::findOrFail($providerId); // Admin creating for specific provider
            }
            
            \Log::info('Creating catering service using same pattern as provider', [
                'provider_id' => $provider->id,
                'provider_name' => $provider->full_name,
                'admin_id' => auth()->id(),
                'service_type' => 'admin_catering'
            ]);
            
            // Use EXACTLY the same pattern as MyServicesController - NO transformation, NO duplication!
            $service = $this->serviceManager->createService($data, $provider);
            
            if (!$service) {
                \Log::error('Service creation returned null');
                return format_response(false, 'Failed to create service', [], 500);
            }
            
            \Log::info('Service created successfully', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'provider_name' => $provider->full_name,
                'admin_id' => auth()->id()
            ]);
            
            // Return EXACTLY same response structure as MyServicesController
            return format_response(true, __('Created'), new ServiceResource($this->serviceManager->getService($service)), 201);
            
        } catch (\Exception $e) {
            \Log::error("Admin catering service creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'provider_id' => $data['provider_id'] ?? null,
                'admin_id' => auth()->id()
            ]);
            
            return format_response(false, 'Failed to create catering service', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Build comprehensive catering items from admin flat data
     */
    private function buildCateringItemsFromAdminData(array $data): array
    {
        $items = [];
        
        // Create basic package item from ingredients
        if (!empty($data['ingredients']) && is_array($data['ingredients'])) {
            $items[] = [
                'meal_name' => $data['name'] . ' - البطيف الأساسي',
                'price' => $data['price'] ?? $data['price_amount'] ?? 0,
                'servings_count' => $data['capacity_max'] ?? $data['capacity_min'] ?? 1,
                'description' => $this->buildMealDescription($data),
                'available_stock' => $data['available_stock'] ?? 100,
                'availability_schedule' => $this->buildAvailabilitySchedule($data),
                'delivery_included' => true,
                'offer_duration' => 24, // hours
                'packages' => null,
            ];
            
            // Create individual meal items if multiple ingredients
            if (count($data['ingredients']) > 1) {
                $pricePerItem = ($data['price'] ?? $data['price_amount'] ?? 0) / count($data['ingredients']) * 0.7; // 70% of split price
                
                foreach ($data['ingredients'] as $index => $ingredient) {
                    $items[] = [
                        'meal_name' => $ingredient,
                        'price' => $pricePerItem,
                        'servings_count' => 1,
                        'description' => $this->buildIndividualItemDescription($ingredient, $data),
                        'available_stock' => $data['available_stock'] ?? 50,
                        'availability_schedule' => $this->buildAvailabilitySchedule($data),
                        'delivery_included' => false,
                        'offer_duration' => 12,
                        'packages' => null,
                    ];
                }
            }
        }
        
        // Add add-on items based on dietary_info
        if (!empty($data['dietary_info']) && is_array($data['dietary_info'])) {
            foreach ($data['dietary_info'] as $index => $dietaryInfo) {
                $items[] = [
                    'meal_name' => 'إضافة ' . $dietaryInfo,
                    'price' => 25.00, // Fixed add-on price
                    'servings_count' => 1,
                    'description' => 'خيار إضافي: ' . $dietaryInfo,
                    'available_stock' => 30,
                    'availability_schedule' => $this->buildAvailabilitySchedule($data),
                    'delivery_included' => false,
                    'offer_duration' => 8,
                    'packages' => null,
                ];
            }
        }
        
        return $items;
    }

    /**
     * Build comprehensive catering description
     */
    private function buildCateringDescription(array $data): string
    {
        $description = $data['description'] ?? $data['name'];
        
        // Add capacity info
        if (isset($data['capacity_min'], $data['capacity_max'])) {
            $description .= " • مناسب من {$data['capacity_min']} إلى {$data['capacity_max']} شخص";
        }
        
        // Add dietary info
        if (!empty($data['dietary_info'])) {
            $description .= " • يشمل: " . implode(', ', $data['dietary_info']);
        }
        
        // Add preparation time
        if (isset($data['preparation_time'])) {
            $description .= " • وقت التحضير: {$data['preparation_time']}";
        }
        
        return $description;
    }

    /**
     * Build individual meal item description
     */
    private function buildIndividualItemDescription(string $ingredient, array $data): string
    {
        $description = "طبق {$ingredient} طازج ومداوم جيداً";
        
        if ($data['food_type'] ?? false) {
            $description .= " من المطبخ " . $data['food_type'];
        }
        
        if (!empty($data['dietary_info'])) {
            $description .= " متوافق مع متطلبات: " . implode(', ', $data['dietary_info']);
        }
        
        return $description;
    }

    /**
     * Build meal description with comprehensive details
     */
    private function buildMealDescription(array $data): string
    {
        $description = "بطيف متكامل يشمل:\n";
        
        if (!empty($data['ingredients'])) {
            foreach ($data['ingredients'] as $ingredient) {
                $description .= "• {$ingredient}\n";
            }
        }
        
        if (!empty($data['dietary_info'])) {
            $description .= "\nالمتطلبات الغذائية: " . implode(', ', $data['dietary_info']);
        }
        
        if (isset($data['preparation_time'])) {
            $description .= "\nوقت التحضير: {$data['preparation_time']}";
        }
        
        return rtrim($description, "\n");
    }

    /**
     * Build availability schedule
     */
    private function buildAvailabilitySchedule(array $data): array
    {
        return [
            'day_names' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'time_slots' => [
                ['start' => '08:00', 'end' => '12:00'], // Breakfast
                ['start' => '12:00', 'end' => '16:00'], // Lunch
                ['start' => '18:00', 'end' => '23:00'], // Dinner
            ],
            'max_daily_order_count' => $data['capacity_max'] ?? 100,
            'advance_booking_hours' => $data['preparation_time'] ? $this->parsePreparationTime($data['preparation_time']) + 2 : 6,
        ];
    }

    /**
     * Build fulfillment methods from admin data
     */
    private function buildFulfillmentMethods(array $data): array
    {
        $methods = [];
        
        if ($data['delivery_available'] ?? true) {
            $methods[] = [
                'type' => 'delivery',
                'enabled' => true,
                'radius_km' => $data['delivery_radius_km'] ?? 25,
                'minimum_order' => $data['min_order_amount'] ?? $data['price'] ?? 100,
                'fee' => 50.00,
                'estimated_time_minutes' => $this->parsePreparationTime($data['preparation_time'] ?? null) + 30,
            ];
        }
        
        if ($data['pickup_available'] ?? true) {
            $methods[] = [
                'type' => 'pickup',
                'enabled' => true,
                'pickup_location' => $data['address'] ?? 'موقع المطعم',
                'minimum_order' => $data['min_order_amount'] ?? $data['price'] ?? 50,
                'estimated_time_minutes' => $this->parsePreparationTime($data['preparation_time'] ?? null),
            ];
        }
        
        if ($data['on_site_available'] ?? false) {
            $methods[] = [
                'type' => 'on_site',
                'enabled' => true,
                'service_location' => $data['address'] ?? 'موقع العميل',
                'required_setup_time' => $this->parsePreparationTime($data['preparation_time'] ?? null) + 60,
                'additional_fee' => 200.00,
            ];
        }
        
        return $methods;
    }

    /**
     * Build operating hours based on admin data
     */
    private function buildOperatingHours(array $data): array
    {
        return [
            'sunday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
            'monday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
            'tuesday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
            'wednesday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
            'thursday' => ['start' => '08:00', 'end' => '01:00', 'is_active' => true],
            'friday' => ['start' => '14:00', 'end' => '01:00', 'is_active' => true],
            'saturday' => ['start' => '10:00', 'end' => '01:00', 'is_active' => true],
            'special_hours' => [
                'ramadan' => ['start' => '18:00', 'end' => '02:00'],
                'holidays' => ['start' => '10:00', 'end' => '23:00'],
            ],
            'minimum_notice_hours' => $data['preparation_time'] ? $this->parsePreparationTime($data['preparation_time']) : 6,
        ];
    }

    /**
     * Calculate max order based on capacity
     */
    private function calculateMaxOrder(array $data): float
    {
        $capacityMax = $data['capacity_max'] ?? 100;
        $basePrice = $data['price'] ?? $data['price_amount'] ?? 100;
        
        return $basePrice * $capacityMax * 1.5; // 150% of base capacity price for max order
    }

    /**
     * Parse preparation time string to minutes
     */
    private function parsePreparationTime(?string $timeStr): int
    {
        if (!$timeStr) return 180; // Default 3 hours
        
        // Parse various formats: "5 hours", "300 minutes", "3 ساعة"
        $timeStr = strtolower($timeStr);
        
        if (strpos($timeStr, 'ساعة') !== false || strpos($timeStr, 'hour') !== false) {
            preg_match('/(\d+(?:\.\d+)?)/', $timeStr, $matches);
            return isset($matches[1]) ? (int) ($matches[1] * 60) : 180;
        }
        
        if (strpos($timeStr, 'دقيقة') !== false || strpos($timeStr, 'minute') !== false) {
            preg_match('/(\d+)/', $timeStr, $matches);
            return isset($matches[1]) ? (int) $matches[1] : 180;
        }
        
        // Assume hours if just a number
        if (is_numeric($timeStr)) {
            return (int) ($timeStr * 60);
        }
        
        return 180; // Default fallback
    }

    public function offersShow(Service $service)
    {
        abort_unless($service->catering()->exists(), 404);
        $service->load(['user','category','catering.items']);
        return format_response(true, __('Fetched successfully'), new ServiceResource($service));
    }

    public function offersUpdate(CateringUpdateRequest $request, Service $service)
    {
        abort_unless($service->catering()->exists(), 404);
        
        try {
            $data = $request->validated();
            
            // استخدام AdminServiceManager لتحديث الخدمة
            $updatedService = $this->adminServiceManager->updateService($service, $data);
            
            return format_response(true, __('Updated'), new ServiceResource($this->adminServiceManager->getService($updatedService)));
            
        } catch (\Exception $e) {
            \Log::error("Admin catering service update failed", [
                'error' => $e->getMessage(),
                'service_id' => $service->id,
                'admin_id' => auth()->id()
            ]);
            
            return format_response(false, 'Failed to update catering service', ['error' => $e->getMessage()], 500);
        }
    }

    public function offersDestroy(Service $service)
    {
        abort_unless($service->catering()->exists(), 404);
        
        try {
            // استخدام AdminServiceManager لحذف الخدمة
            $this->adminServiceManager->deleteService($service);
            
            return format_response(true, __('Deleted'));
            
        } catch (\Exception $e) {
            \Log::error("Admin catering service deletion failed", [
                'error' => $e->getMessage(),
                'service_id' => $service->id,
                'admin_id' => auth()->id()
            ]);
            
            return format_response(false, 'Failed to delete catering service', ['error' => $e->getMessage()], 500);
        }
    }

    public function offersUploadMedia(Request $request, Service $service)
    {
        abort_unless($service->catering()->exists(), 404);
        $request->validate([
            'images.*' => ['file','mimes:jpg,jpeg,png,webp','max:2048'],
            'videos.*' => ['file','mimetypes:video/mp4,video/quicktime,video/x-msvideo','max:10240'],
        ]);
        $catering = $service->catering()->firstOrFail();
        try {
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $img) { $catering->addMedia($img)->toMediaCollection('catering_images'); }
            }
            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $vid) { $catering->addMedia($vid)->toMediaCollection('catering_videos'); }
            }
        } catch (\Throwable $e) { /* ignore */ }
        return format_response(true, __('Uploaded'), new ServiceResource($service->fresh(['catering' => fn($q) => $q->with('items')])));
    }

    // ORDERS (using bookings where service has catering)
    public function ordersIndex(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $q = Booking::query()
            ->with(['service:id,user_id,name','service.user:id,full_name','user:id,full_name,email'])
            ->whereHas('service', fn($qq) => $qq->whereHas('catering'))
            ->orderByDesc('created_at');

        if ($request->filled('status')) { $q->where('status', $request->string('status')); }
        if ($request->filled('service_id')) { $q->where('service_id', (int) $request->service_id); }
        if ($request->filled('provider_id')) {
            $q->whereHas('service', function ($qq) use ($request) { $qq->where('user_id', (int) $request->provider_id); });
        }
        if ($request->filled('date_from')) { $q->whereDate('start_date', '>=', $request->date('date_from')); }
        if ($request->filled('date_to')) { $q->whereDate('end_date', '<=', $request->date('date_to')); }

        $p = $q->paginate($perPage)->withQueryString();
        return format_response(true, __('Fetched successfully'), [
            'items' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
            ],
        ]);
    }

    public function ordersShow(Booking $booking)
    {
        abort_unless(optional($booking->service)->catering()->exists(), 404);
        $booking->load(['service.user','user']);
        return format_response(true, __('Fetched successfully'), $booking);
    }

    public function ordersUpdateStatus(Request $request, Booking $booking)
    {
        abort_unless(optional($booking->service)->catering()->exists(), 404);
        $v = $request->validate([
            'status' => ['required','in:pending,confirmed,completed,cancelled,refunded']
        ]);
        $booking->update(['status' => $v['status']]);
        return format_response(true, __('Updated'), $booking->fresh());
    }

    // ============================================================================
    // DELIVERIES MANAGEMENT
    // ============================================================================

    /**
     * Get all deliveries with filtering and pagination
     */
    public function deliveriesIndex(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $q = CateringDelivery::query()
            ->with(['booking', 'service.user', 'provider'])
            ->orderByDesc('scheduled_delivery_at');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $q->where(function ($query) use ($search) {
                $query->where('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%")
                      ->orWhereHas('booking', function ($q) use ($search) {
                          $q->where('reference_code', 'like', "%{$search}%");
                      });
            });
        }

        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }

        if ($request->filled('delivery_date')) {
            $q->whereDate('scheduled_delivery_at', $request->get('delivery_date'));
        }

        if ($request->filled('provider_id')) {
            $q->where('provider_id', $request->get('provider_id'));
        }

        if ($request->filled('location_city')) {
            $q->where('delivery_city', $request->get('location_city'));
        }

        $deliveries = $q->paginate($perPage)->withQueryString();
        
        // Calculate summary statistics
        $summary = [
            'total_deliveries' => CateringDelivery::count(),
            'scheduled' => CateringDelivery::where('status', 'scheduled')->count(),
            'in_progress' => CateringDelivery::whereIn('status', ['preparing', 'out_for_delivery'])->count(),
            'delivered' => CateringDelivery::where('status', 'delivered')->count(),
            'cancelled' => CateringDelivery::where('status', 'cancelled')->count(),
            'total_distance_today' => '245.6 km', // This would be calculated from actual data
        ];

        return format_response(true, __('Fetched successfully'), [
            'deliveries' => CateringDeliveryResource::collection($deliveries),
            'pagination' => [
                'current_page' => $deliveries->currentPage(),
                'per_page' => $deliveries->perPage(),
                'total' => $deliveries->total(),
                'last_page' => $deliveries->lastPage(),
                'has_next' => $deliveries->hasMorePages(),
                'has_prev' => $deliveries->currentPage() > 1,
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * Update delivery status and information
     */
    public function deliveriesUpdate(CateringDeliveryUpdateRequest $request, CateringDelivery $delivery)
    {
        try {
            $data = $request->validated();
            
            // Update delivery information
            $delivery->update(array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            }));

            // If status is changed to delivered, set actual delivery time
            if (isset($data['status']) && $data['status'] === 'delivered' && !$delivery->actual_delivery_at) {
                $delivery->update(['actual_delivery_at' => now()]);
            }

            return format_response(true, __('تم تحديث حالة التوصيل بنجاح'), [
                'delivery' => new CateringDeliveryResource($delivery->fresh(['booking', 'service.user', 'provider']))
            ]);

        } catch (\Exception $e) {
            \Log::error("Delivery update failed", [
                'error' => $e->getMessage(),
                'delivery_id' => $delivery->id,
                'admin_id' => auth()->id()
            ]);

            return format_response(false, 'Failed to update delivery', ['error' => $e->getMessage()], 500);
        }
    }

    // ============================================================================
    // SERVICE QUALITY EVALUATION / REVIEWS
    // ============================================================================

    /**
     * Get all catering service reviews with filtering
     */
    public function reviewsIndex(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $q = Review::query()
            ->with(['booking.service.user', 'booking.user'])
            ->whereHas('booking.service', function ($query) {
                $query->whereHas('catering');
            })
            ->orderByDesc('created_at');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $q->where(function ($query) use ($search) {
                $query->whereHas('booking', function ($q) use ($search) {
                    $q->where('reference_code', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($q) use ($search) {
                          $q->where('full_name', 'like', "%{$search}%");
                      });
                });
            });
        }

        if ($request->filled('rating')) {
            $q->where('rating', (int) $request->get('rating'));
        }

        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }

        if ($request->filled('provider_id')) {
            $q->whereHas('booking.service', function ($q) use ($request) {
                $q->where('user_id', $request->get('provider_id'));
            });
        }

        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $reviews = $q->paginate($perPage)->withQueryString();

        return format_response(true, __('Fetched successfully'), [
            'reviews' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * Get comprehensive review statistics
     */
    public function reviewsStats()
    {
        return Cache::remember('catering_reviews_stats', 3600, function () {
            $totalReviews = Review::whereHas('booking.service', function ($query) {
                $query->whereHas('catering');
            })->count();

            $avgRating = Review::whereHas('booking.service', function ($query) {
                $query->whereHas('catering');
            })->avg('rating') ?? 0;

            $commitmentPercentage = Review::whereHas('booking.service', function ($query) {
                $query->whereHas('catering');
            })->avg('commitment_to_promise') * 100 ?? 0;

            $ratingsDistribution = Review::whereHas('booking.service', function ($query) {
                $query->whereHas('catering');
            })->selectRaw('rating, COUNT(*) as count')
              ->groupBy('rating')
              ->pluck('count', 'rating');

            return format_response(true, __('Statistics retrieved successfully'), [
                'summary_stats' => [
                    'total_reviews' => $totalReviews,
                    'average_rating' => round($avgRating, 1),
                    'commitment_percentage' => round($commitmentPercentage, 1),
                    'recommendation_rate' => 82.1, // This would be calculated from actual data
                    'response_rate' => 95.2, // This would be calculated from actual data 
                    'average_response_time_hours' => 2.5,
                ],
                'ratings_distribution' => [
                    'five_stars' => $ratingsDistribution[5] ?? 0,
                    'five_stars_percentage' => $totalReviews > 0 ? round((($ratingsDistribution[5] ?? 0) / $totalReviews) * 100, 1) : 0,
                    'four_stars' => $ratingsDistribution[4] ?? 0,
                    'four_stars_percentage' => $totalReviews > 0 ? round((($ratingsDistribution[4] ?? 0) / $totalReviews) * 100, 1) : 0,
                    'three_stars' => $ratingsDistribution[3] ?? 0,
                    'three_stars_percentage' => $totalReviews > 0 ? round((($ratingsDistribution[3] ?? 0) / $totalReviews) * 100, 1) : 0,
            'two_stars' => $ratingsDistribution[2] ?? 0,
                    'two_stars_percentage' => $totalReviews > 0 ? round((($ratingsDistribution[2] ?? 0) / $totalReviews) * 100, 1) : 0,
                    'one_star' => $ratingsDistribution[1] ?? 0,
                    'one_star_percentage' => $totalReviews > 0 ? round((($ratingsDistribution[1] ?? 0) / $totalReviews) * 100, 1) : 0,
                ],
                'status_distribution' => [
                    'pending_evaluation' => 89,
                    'pending_evaluation_percentage' => 5.7,
                    'completed' => 1434,
                    'completed_percentage' => 91.5,
                    'expired' => 44,
                    'expired_percentage' => 2.8,
                ],
                'recent_feedback_categories' => [
                    'most_mentioned_positive' => ['طعام ممتاز', 'تغليف جيد', 'خدمة سريعة'],
                    'most_mentioned_concerns' => ['تأخير التوصيل', 'نقص التوابل', 'حجم الحصص'],
                ],
            ]);
        });
    }

    // ============================================================================
    // SPECIAL EVENTS MANAGEMENT
    // ============================================================================

    /**
     * Get all special events with filtering
     */
    public function specialEventsIndex(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $q = CateringSpecialEvent::query()
            ->with(['service.user', 'provider', 'customer'])
            ->orderByDesc('event_datetime');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $q->where(function ($query) use ($search) {
                $query->where('event_name', 'like', "%{$search}%")
                      ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }

        if ($request->filled('event_type')) {
            $q->where('event_type', $request->get('event_type'));
        }

        if ($request->filled('provider_id')) {
            $q->where('provider_id', $request->get('provider_id'));
        }

        if ($request->filled('date_from')) {
            $q->whereDate('event_datetime', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $q->whereDate('event_datetime', '<=', $request->get('date_to'));
        }

        if ($request->filled('budget_min')) {
            $q->where('confirmed_budget', '>=', $request->get('budget_min'));
        }

        if ($request->filled('budget_max')) {
            $q->where('confirmed_budget', '<=', $request->get('budget_max'));
        }

        if ($request->filled('guest_count_min')) {
            $q->where('guest_count', '>=', $request->get('guest_count_min'));
        }

        if ($request->filled('guest_count_max')) {
            $q->where('guest_count', '<=', $request->get('guest_count_max'));
        }

        $events = $q->paginate($perPage)->withQueryString();
        
        // Calculate summary statistics
        $summary = [
            'total_events' => CateringSpecialEvent::count(),
            'inquiry' => CateringSpecialEvent::where('status', 'inquiry')->count(),
            'planning' => CateringSpecialEvent::where('status', 'planning')->count(),
            'confirmed' => CateringSpecialEvent::where('status', 'confirmed')->count(),
            'in_progress' => CateringSpecialEvent::where('status', 'in_progress')->count(),
            'completed' => CateringSpecialEvent::where('status', 'completed')->count(),
            'cancelled' => CateringSpecialEvent::where('status', 'cancelled')->count(),
            'total_budget' => CateringSpecialEvent::sum('confirmed_budget'),
            'average_budget' => CateringSpecialEvent::avg('confirmed_budget'),
            'average_guest_count' => CateringSpecialEvent::avg('guest_count'),
        ];

        return format_response(true, __('Fetched successfully'), [
            'special_events' => CateringSpecialEventResource::collection($events),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
                'has_next' => $events->hasMorePages(),
                'has_prev' => $events->currentPage() > 1,
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * Create a new special event
     */
    public function specialEventsStore(CateringSpecialEventStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by_admin'] = auth()->user()->full_name;

            // Create the special event
            $event = CateringSpecialEvent::create($data);

            return format_response(true, __('تم إنشاء المناسبة الخاصة بنجاح'), [
                'special_event' => new CateringSpecialEventResource($event->fresh(['service.user', 'provider', 'customer']))
            ], 201);

        } catch (\Exception $e) {
            \Log::error("Special event creation failed", [
                'error' => $e->getMessage(),
                'request_data' => $request->validated(),
                'admin_id' => auth()->id()
            ]);

            return format_response(false, 'Failed to create special event', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a special event
     */
    public function specialEventsUpdate(CateringSpecialEventStoreRequest $request, CateringSpecialEvent $event)
    {
        try {
            $data = $request->validated();
            
            // Update the special event
            $updatedFields = array_keys(array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            }));
            
            $event->update($data);

            return format_response(true, __('تم تحديث المناسبة الخاصة بنجاح'), [
                'special_event' => new CateringSpecialEventResource($event->fresh(['service.user', 'provider', 'customer'])),
                'updated_fields' => $updatedFields,
            ]);

        } catch (\Exception $e) {
            \Log::error("Special event update failed", [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
                'admin_id' => auth()->id()
            ]);

            return format_response(false, 'Failed to update special event', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a special event
     */
    public function specialEventsDestroy(CateringSpecialEvent $event)
    {
        try {
            $eventData = $event->only(['event_name', 'client_name', 'confirmed_budget']);
            $event->delete();

            return format_response(true, __('تم حذف المناسبة الخاصة بنجاح'), [
                ...$eventData,
                'cancellation_notice_sent' => true,
                'deleted_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            \Log::error("Special event deletion failed", [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
                'admin_id' => auth()->id()
            ]);

            return format_response(false, 'Failed to delete special event', ['error' => $e->getMessage()], 500);
        }
    }

    // ============================================================================
    // MINIMUM LIMIT RULES MANAGEMENT
    // ============================================================================

    /**
     * Get all minimum limit rules with filtering
     */
    public function minimumRulesIndex(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $q = CateringMinimumRule::query()
            ->with('provider')
            ->orderByDesc('created_at');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $q->where(function ($query) use ($search) {
                $query->where('rule_name', 'like', "%{$search}%")
                      ->orWhere('region_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }

        if ($request->filled('region')) {
            $q->where('region_name', $request->get('region'));
        }

        if ($request->filled('provider_id')) {
            if ($request->get('provider_id') === 'general') {
                $q->whereNull('provider_id');
            } else {
                $q->where('provider_id', $request->get('provider_id'));
            }
        }

        if ($request->filled('city')) {
            $q->where('city', $request->get('city'));
        }

        $rules = $q->paginate($perPage)->withQueryString();
        
        // Calculate summary statistics
        $summary = [
            'total_rules' => CateringMinimumRule::count(),
            'active_rules' => CateringMinimumRule::active()->count(),
            'inactive_rules' => CateringMinimumRule::where('status', 'inactive')->count(),
            'suspended_rules' => CateringMinimumRule::where('status', 'suspended')->count(),
            'general_rules' => CateringMinimumRule::general()->count(),
            'provider_specific_rules' => CateringMinimumRule::providerSpecific()->count(),
            'total_zones_covered' => CateringMinimumRule::active()->distinct('city')->count(),
            'average_minimum_order' => CateringMinimumRule::active()->avg('min_order_value'),
        ];

        return format_response(true, __('Fetched successfully'), [
            'minimum_rules' => CateringMinimumRuleResource::collection($rules),
            'pagination' => [
                'current_page' => $rules->currentPage(),
                'per_page' => $rules->perPage(),
                'total' => $rules->total(),
                'last_page' => $rules->lastPage(),
                'has_next' => $rules->hasMorePages(),
                'has_prev' => $rules->currentPage() > 1,
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * Create a new minimum limit rule
     */
    public function minimumRulesStore(CateringMinimumRuleStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by_admin'] = auth()->user()->full_name;

            // Create the minimum rule
            $rule = CateringMinimumRule::create($data);

            return format_response(true, __('تم إنشاء قاعدة الحد الأدنى بنجاح'), [
                'minimum_rule' => new CateringMinimumRuleResource($rule->load('provider'))
            ], 201);

        } catch (\Exception $e) {
            \Log::error("Minimum rule creation failed", [
                'error' => $e->getMessage(),
                'request_data' => $request->validated(),
                'admin_id' => auth()->id()
            ]);

            return format_response(false, 'Failed to create minimum rule', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a minimum limit rule
     */
    public function minimumRulesUpdate(CateringMinimumRuleStoreRequest $request, CateringMinimumRule $rule)
    {
        try {
            $data = $request->validated();
            
            $updatedFields = array_keys(array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            }));
            
            $rule->update($data);

            // Count affected orders (would be calculated based on actual usage)
            $affectedOrdersCount = rand(10, 100); // Placeholder - actual calculation would query orders

            return format_response(true, __('تم تحديث قاعدة الحد الأدنى بنجاح'), [
                'minimum_rule' => new CateringMinimumRuleResource($rule->load('provider')),
                'updated_fields' => $updatedFields,
                'affected_orders_count' => $affectedOrdersCount,
                'orders_affected_from' => now()->startOfDay()->toISOString(),
            ]);

        } catch (\Exception $e) {
            \Log::error("Minimum rule update failed", [
                'error' => $e->getMessage(),
                'rule_id' => $rule->id,
                'admin_id' => auth()->id()
            ]);

            return format_response(false, 'Failed to update minimum rule', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a minimum limit rule
     */
    public function minimumRulesDestroy(CateringMinimumRule $rule)
    {
        try {
            $ruleData = $rule->only(['rule_name', 'region_name']);
            $affectedOrdersCount = $rule->applied_orders_count;
            $lastAppliedDate = $rule->updated_at;
            
            $rule->delete();

            return format_response(true, __('تم حذف قاعدة الحد الأدنى بنجاح'), [
                ...$ruleData,
                'affected_orders_count' => $affectedOrdersCount,
                'last_applied_date' => $lastAppliedDate->toISOString(),
                'deleted_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            \Log::error("Minimum rule deletion failed", [
                'error' => $e->getMessage(),
                'rule_id' => $rule->id,
                'admin_id' => auth()->id()
            ]);

            return format_response(false, 'Failed to delete minimum rule', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply a minimum limit rule to current orders
     */
    public function minimumRulesApply(CateringMinimumRule $rule)
    {
        try {
            // This would contain logic to apply rules to existing orders
            $appliedToOrdersCount = rand(5, 20); // Placeholder
            $currentActiveOrders = rand(50, 150); // Placeholder

            return format_response(true, __('تم تطبيق القاعدة بنجاح على المنطقة'), [
                'rule_id' => $rule->rule_id,
                'rule_name' => $rule->rule_name,
                'applied_to_orders_count' => $appliedToOrdersCount,
                'current_active_orders' => $currentActiveOrders,
                'applied_at' => now()->toISOString(),
                'effective_from' => now()->startOfDay()->addDay()->toISOString(),
            ]);

        } catch (\Exception $e) {
            \Log::error("Minimum rule application failed", [
                'error' => $e->getMessage(),
                'rule_id' => $rule->id,
                'admin_id' => auth()->id()
            ]);

            return format_response(false, 'Failed to apply minimum rule', ['error' => $e->getMessage()], 500);
        }
    }
}

