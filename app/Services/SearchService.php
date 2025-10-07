<?php

namespace App\Services;

use App\Models\Service;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SearchService
{
    /**
     * البحث في الخدمات
     */
    public function searchServices(array $filters = []): Builder
    {
        $query = Service::with(['category', 'user', 'event', 'event.media', 'catering', 'catering.media', 'catering.items', 'catering.items.media', 'cateringItem', 'cateringItem.media', 'restaurant', 'property', 'favorites.user', 'bookings.user'])
            ->where('is_approved', true);

        if (!empty($filters['service_id'])) {
            $query->where('id', $filters['service_id']);
            // عند طلب خدمة محددة: حمّل العلاقات المطلوبة دائمًا لتلبية الواجهة
            $query->with([
                'bookings.user.customerProfile',
                'bookings.user.companyProfile',
                'reviews.user.customerProfile',
                'reviews.user.companyProfile',
                'favorites.user.customerProfile',
                'favorites.user.companyProfile',
            ]);
        }

        if (!empty($filters['service_ids']) && is_array($filters['service_ids'])) {
            $query->whereIn('id', $filters['service_ids']);
        }

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhereHas('category.translations', fn($t) => $t->where('name', 'like', "%{$searchTerm}%"))
                  ->orWhereHas('event', fn($e) => $e->where('event_name', 'like', "%{$searchTerm}%")->orWhere('description', 'like', "%{$searchTerm}%"))
                  ->orWhereHas('restaurant', fn($r) => $r->where('description', 'like', "%{$searchTerm}%"))
                  ->orWhereHas('catering.items', fn($i) => $i->where('description', 'like', "%{$searchTerm}%"))
                  ->orWhereHas('property', fn($p) => $p->where('description', 'like', "%{$searchTerm}%"));
            });
        // Extended search also covers property description
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->orWhereHas('property', fn($p) => $p->where('description', 'like', "%{$searchTerm}%"));
        }

        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $query->whereIn('category_id', $filters['category_ids']);
        }

        if (!empty($filters['main_service_id'])) {
            $query->whereHas('category', fn($q) => $q->where('main_service_id', $filters['main_service_id']));
        }

        $typeMap = ['event' => 'event', 'catering' => 'catering', 'restaurant' => 'restaurant', 'property' => 'property'];
        if (!empty($filters['service_type']) && isset($typeMap[$filters['service_type']])) {
            $query->whereHas($typeMap[$filters['service_type']]);
        }
        if (!empty($filters['service_types']) && is_array($filters['service_types'])) {
            $validTypes = array_values(array_intersect($filters['service_types'], array_keys($typeMap)));
            if (!empty($validTypes)) {
                $query->where(function($q) use ($validTypes, $typeMap) {
                    foreach ($validTypes as $type) {
                        $q->orWhereHas($typeMap[$type]);
                    }
                });
            }
        }

        if (!empty($filters['min_price'])) {
            $min = (float)$filters['min_price'];
            $query->where(function($q) use ($min) {
                $q->whereHas('event', fn($e) => $e->where('base_price', '>=', $min))
                  ->orWhereHas('restaurant.tables', fn($rt) => $rt->where('price_per_person', '>=', $min))
                  ->orWhereHas('catering.items', fn($i) => $i->where('price', '>=', $min))
                  ->orWhereHas('property', fn($p) => $p->where('nightly_price', '>=', $min));
            });
        }
        if (!empty($filters['max_price'])) {
            $max = (float)$filters['max_price'];
            $query->where(function($q) use ($max) {
                $q->whereHas('event', fn($e) => $e->where('base_price', '<=', $max))
                  ->orWhereHas('restaurant.tables', fn($rt) => $rt->where('price_per_person', '<=', $max))
                  ->orWhereHas('catering.items', fn($i) => $i->where('price', '<=', $max))
                  ->orWhereHas('property', fn($p) => $p->where('nightly_price', '<=', $max));
            });
        }
        // Properties can carry their own location fields; prefer properties.* when present
        if (!empty($filters['region_id'])) {
            $query->whereHas('property', fn($p) => $p->where('region_id', $filters['region_id']))
                  ->orWhere('city_id', $filters['city_id'] ?? null);
        }
        if (!empty($filters['neigbourhood_id'])) {
            $query->whereHas('property', fn($p) => $p->where('neigbourhood_id', $filters['neigbourhood_id']));
        }
        if (!empty($filters['city_id'])) {
            $query->where(function($q) use ($filters) {
                $q->where('city_id', $filters['city_id'])
                  ->orWhereHas('property', fn($p) => $p->where('city_id', $filters['city_id']));
            });
        }



        if (!empty($filters['min_rating'])) {
            $query->where('rating_avg', '>=', (float)$filters['min_rating']);
        }
        if (!empty($filters['city_id'])) {
            $query->whereHas('user.companyProfile', fn($q) => $q->where('city_id', $filters['city_id']));
        }
        if (!empty($filters['gender_type'])) {
            $gt = $filters['gender_type'];
            $query->where(fn($q) => $q->whereHas('event', fn($e) => $e->where('gender_type', $gt))->orWhere('gender_type', $gt));
        }

        if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
            $lat = $filters['latitude'];
            $lon = $filters['longitude'];
            $radius = $filters['radius'] ?? 10;
            $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lon)) + sin(radians($lat)) * sin(radians(latitude))))";
            $query->where(function($q) use ($haversine, $radius) {
                $q->whereNotNull('latitude')->whereNotNull('longitude')->whereRaw("$haversine <= ?", [$radius])
                  ->orWhereHas('property', fn($p) => $p->whereRaw("$haversine <= ?", [$radius]));
            });
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $from = Carbon::parse($filters['date_from']);
            $to = Carbon::parse($filters['date_to']);
            $query->whereDoesntHave('bookings', function ($q) use ($from, $to) {
                $q->where('status', '!=', 'cancelled')
                  ->where(fn($sub) => $sub->whereBetween('start_date', [$from, $to])->orWhereBetween('end_date', [$from, $to])->orWhere(fn($sub2) => $sub2->where('start_date', '<=', $from)->where('end_date', '>=', $to)));
            });
        }

        if (!empty($filters['number_of_people'])) {
            $query->whereHas('event', fn($q) => $q->where('max_individuals', '>=', $filters['number_of_people']));
        }
        if (!empty($filters['number_of_nights'])) {
            $query->whereHas('property', fn($q) => $q->where('min_nights', '<=', $filters['number_of_nights']));
        }

        // Property-specific filters
        if (!empty($filters['min_bedrooms'])) {
            $minBeds = (int)$filters['min_bedrooms'];
            $query->whereHas('property.bedrooms', function($q) use ($minBeds) {
                $q->select('property_id')
                  ->groupBy('property_id')
                  ->havingRaw('SUM(beds_count) >= ?', [$minBeds]);
            });
        }
        if (!empty($filters['region_id'])) {
            // Filter by provider's customer region not applicable; for properties, we can scope via service.city/latlng later if needed
        }
        if (!empty($filters['neigbourhood_id'])) {
            // Not stored on service/property directly; leaving for future when field exists
        }

        // Force scope to properties when requested
        if (!empty($filters['service_type']) && $filters['service_type'] === 'property') {
            $query->whereHas('property');
        }
        if (!empty($filters['main_service_id'])) {
            $query->whereHas('category', fn($q) => $q->where('main_service_id', $filters['main_service_id']));
        }


        if (!empty($filters['include']) && is_array($filters['include'])) {
            // تحميل خفيف افتراضياً + إتاحة التحميل الكامل عند الطلب
            if (in_array('reviews', $filters['include'])) {
                $query->with(['reviews.user.customerProfile','reviews.user.companyProfile']);
                $query->withCount('reviews');
            }
            if (in_array('favorites', $filters['include'])) {
                $query->with(['favorites.user.customerProfile','favorites.user.companyProfile']);
                $query->withCount('favorites');
                if (auth()->check()) {
                    $query->withExists(['favorites as is_favorited' => function($q){ $q->where('user_id', auth()->id()); }]);
                }
            }
            if (in_array('booked_by_users', $filters['include'])) {
                $query->with(['bookings.user.customerProfile','bookings.user.companyProfile']);
                $query->withCount('bookings');
            }
        }

$sortBy = $filters['sort_by'] ?? 'created_at';
$sortDirection = $filters['sort_direction'] ?? 'desc';

switch ($sortBy) {
    case 'price':
        // Optimized price sorting with better indexing strategy
        $query->leftJoin('events','events.service_id','=','services.id')
              ->leftJoin('restaurants','restaurants.service_id','=','services.id')
              ->leftJoin('restaurant_tables','restaurant_tables.restaurant_id','=','restaurants.id')
              ->leftJoin('properties','properties.service_id','=','services.id')
              ->leftJoin('caterings','caterings.service_id','=','services.id')
              ->leftJoin('catering_items','catering_items.catering_id','=','caterings.id')
              ->addSelect(DB::raw('COALESCE(events.base_price, restaurant_tables.price_per_person, properties.nightly_price, catering_items.price) as final_price'))
              ->orderBy('final_price', $sortDirection)
              ->groupBy('services.id')
              ->limit(1000); // Add limit to prevent excessive memory usage
        break;

    case 'rating':
        $query->orderBy('rating_avg', $sortDirection);
        break;

    case 'distance':
        if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
            $lat = $filters['latitude'];
            $lon = $filters['longitude'];
            $distanceHaversine = "(6371 * acos(cos(radians($lat)) * cos(radians(COALESCE(services.latitude, properties.latitude))) * cos(radians(COALESCE(services.longitude, properties.longitude)) - radians($lon)) + sin(radians($lat)) * sin(radians(COALESCE(services.latitude, properties.latitude)))))";
            $query->leftJoin('properties','properties.service_id','=','services.id')
                  ->addSelect(DB::raw("$distanceHaversine as distance_km"))
                  ->orderBy('distance_km', $sortDirection)
                  ->groupBy('services.id'); // ⬅️ وتمت إضافته هنا أيضاً
        }
        break;

    default:
        $query->orderBy("services.{$sortBy}", $sortDirection);
}

// ⬅️ قم بإزالة .groupBy('services.id') من هنا إن كانت موجودة

return $query;
        return $query;
    }


    /**
     * البحث في المستخدمين
     */
    public function searchUsers(array $filters = []): Builder
    {
        $query = User::with(['customerProfile', 'companyProfile'])
            ->where('type', '!=', 'admin')
            ->withExists(['follows as is_following' => function($q){ $q->where('follower_id', auth()->id()); }])
            ->withExists(['followers as has_accepted_follow' => function($q){ $q->where('follower_id', auth()->id())->where('status', 'accepted'); }]);

        // By default show only approved users publicly; allow override via filter
        if (array_key_exists('is_approved', $filters)) {
            $query->where('is_approved', (bool) $filters['is_approved']);
        } else {
            $query->where('is_approved', true);
        }

        // البحث بالكلمات المفتاحية
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        // فلترة حسب نوع المستخدم
        if (!empty($filters['user_type'])) {
            $query->where('type', $filters['user_type']);
        }

        // فلترة حسب الحالة
        if (!empty($filters['is_approved'])) {
            $query->where('is_approved', $filters['is_approved']);
        }

        // فلترة حسب الموقع (للعملاء)
        if (!empty($filters['region_id'])) {
            $query->whereHas('customerProfile', function ($profileQuery) use ($filters) {
                $profileQuery->where('region_id', $filters['region_id']);
            });
        }

        if (!empty($filters['city_id'])) {
            $query->whereHas('companyProfile', function ($profileQuery) use ($filters) {
                $profileQuery->where('city_id', $filters['city_id']);
            });
        }

        // فلترة حسب عدد الخدمات (للمزودين)
        if (!empty($filters['min_services'])) {
            $query->whereHas('services', function ($serviceQuery) use ($filters) {
                $serviceQuery->where('is_approved', true);
            }, '>=', $filters['min_services']);
        }

        // فلترة حسب التقييم (للمزودين)
        if (!empty($filters['min_rating'])) {
            $query->whereHas('services.reviews', function ($reviewQuery) use ($filters) {
                $reviewQuery->approved()->havingRaw('AVG(rating) >= ?', [$filters['min_rating']]);
            });
        }

        // الترتيب
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        switch ($sortBy) {
            case 'services_count':
                $query->withCount('services')->orderBy('services_count', $sortDirection);
                break;
            case 'rating':
                $query->withAvg('services.reviews', 'rating')->orderBy('services_reviews_avg_rating', $sortDirection);
                break;
            default:
                $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    /**
     * البحث المتقدم مع اقتراحات
     */
    public function advancedSearch(array $filters = []): array
    {
        $services = $this->searchServices($filters)->paginate($filters['per_page'] ?? 20);

        // اقتراحات البحث
        $suggestions = $this->getSearchSuggestions($filters['search'] ?? '');

        // إحصائيات البحث
        $stats = $this->getSearchStats($filters);

        return [
            'services' => $services,
            'suggestions' => $suggestions,
            'stats' => $stats,
        ];
    }

    /**
     * الحصول على اقتراحات البحث
     */
    private function getSearchSuggestions(string $searchTerm): array
    {
        if (empty($searchTerm)) {
            return [];
        }

        $suggestions = [];

        // اقتراحات من أسماء الخدمات
        $serviceNames = Service::where('name', 'like', "%{$searchTerm}%")
            ->where('is_approved', true)
            ->select('name')
            ->distinct()
            ->limit(5)
            ->pluck('name')
            ->toArray();

        $suggestions['services'] = $serviceNames;

        // اقتراحات من أسماء الفئات
        $categoryNames = Category::query()
            ->whereHas('translations', function($t) use ($searchTerm) { $t->where('name', 'like', "%{$searchTerm}%"); })
            ->with(['translations' => function($t) { $t->select('category_id','name'); }])
            ->limit(5)
            ->get()
            ->pluck('name')
            ->unique()
            ->toArray();

        $suggestions['categories'] = $categoryNames;

        // اقتراحات من أسماء المدن
        $cityNames = \App\Models\City::query()
            ->whereHas('translations', function($t) use ($searchTerm) { $t->where('name', 'like', "%{$searchTerm}%"); })
            ->with(['translations' => function($t) { $t->select('city_id','name'); }])
            ->limit(5)
            ->get()
            ->pluck('name')
            ->unique()
            ->toArray();

        $suggestions['cities'] = $cityNames;

        return $suggestions;
    }

    /**
     * الحصول على إحصائيات البحث
     */
    public function getSearchStats(array $filters): array
    {
        $baseQuery = Service::where('is_approved', true);

        // تطبيق نفس الفلاتر
        if (!empty($filters['category_id'])) {
            $baseQuery->where('category_id', $filters['category_id']);
        }

        $stats = [
            'total_services' => (clone $baseQuery)->count(),
            'services_by_type' => [
                'events' => (clone $baseQuery)->whereHas('event')->count(),
                'catering' => (clone $baseQuery)->whereHas('catering')->count(),
                'restaurants' => (clone $baseQuery)->whereHas('restaurant')->count(),
                'properties' => (clone $baseQuery)->whereHas('property')->count(),
            ],
            'price_range' => [
                // compute per head price ranges
                'min' => min(array_filter([
                    (clone $baseQuery)->whereHas('event')->join('events','events.service_id','=','services.id')->min('events.base_price'),
                    (clone $baseQuery)->whereHas('restaurant')->join('restaurants','restaurants.service_id','=','services.id')->join('restaurant_tables','restaurant_tables.restaurant_id','=','restaurants.id')->min('restaurant_tables.price_per_person'),
                    (clone $baseQuery)->whereHas('catering')->join('caterings','caterings.service_id','=','services.id')->join('catering_items','catering_items.catering_id','=','caterings.id')->min('catering_items.price'),
                    (clone $baseQuery)->whereHas('property')->join('properties','properties.service_id','=','services.id')->min('properties.nightly_price'),
                ])),
                'max' => max(array_filter([
                    (clone $baseQuery)->whereHas('event')->join('events','events.service_id','=','services.id')->max('events.base_price'),
                    (clone $baseQuery)->whereHas('restaurant')->join('restaurants','restaurants.service_id','=','services.id')->join('restaurant_tables','restaurant_tables.restaurant_id','=','restaurants.id')->max('restaurant_tables.price_per_person'),
                    (clone $baseQuery)->whereHas('catering')->join('caterings','caterings.service_id','=','services.id')->join('catering_items','catering_items.catering_id','=','caterings.id')->max('catering_items.price'),
                    (clone $baseQuery)->whereHas('property')->join('properties','properties.service_id','=','services.id')->max('properties.nightly_price'),
                ])),
            ],
            'average_rating' => (clone $baseQuery)->avg('rating_avg'),
        ];

        return $stats;
    }

    /**
     * البحث السريع
     */
    public function quickSearch(string $term): array
    {
        $results = [];

        // البحث في الخدمات
        $services = Service::where('is_approved', true)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                      ->orWhereHas('event', function ($e) use ($term) { $e->where('event_name', 'like', "%{$term}%")->orWhere('description','like',"%{$term}%"); })
                      ->orWhereHas('restaurant', function ($r) use ($term) { $r->where('description', 'like', "%{$term}%"); })
                      ->orWhereHas('cateringItem', function ($c) use ($term) { $c->where('description', 'like', "%{$term}%"); })
                      ->orWhereHas('property', function ($p) use ($term) { $p->where('description', 'like', "%{$term}%"); });
            })
            ->with(['category', 'user'])
            ->limit(5)
            ->get();

        $results['services'] = $services;

        // البحث في الفئات
        $categories = Category::query()
            ->whereHas('translations', function($t) use ($term) { $t->where('name', 'like', "%{$term}%"); })
            ->limit(3)
            ->get();

        $results['categories'] = $categories;

        // البحث في المستخدمين
        $users = User::where('is_approved', true)
            ->where(function ($query) use ($term) {
                $query->where('full_name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
            })
            ->with(['customerProfile', 'companyProfile'])
            ->limit(3)
            ->get();

        $results['users'] = $users;

        return $results;
    }
}
