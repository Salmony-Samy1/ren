<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\ServiceResource; // <-- تأكد من وجود هذا السطر
use App\Models\User;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function show(User $user)
    {
        $currentUserId = auth()->id();
        $user->loadCount(['followers', 'follows']);
        $user->load(['companyProfile', 'customerProfile']);
        if ($currentUserId) {
            $user->load([
                'followers' => fn($q) => $q->where('follower_id', $currentUserId),
                'follows'   => fn($q) => $q->where('user_id', $currentUserId),
            ]);
        }
        
        $request = request();
        $includeDetails = $request->has('include_details') && $request->query('include_details') !== '0';

        // --- Provider Logic ---
        if ($user->type === 'provider') {
            $mainServiceId = $request->query('main_service_id');

            // ✅ الخطوة 1: تحديث الـ Query ليحمل كل أنواع الخدمات وتفاصيلها
            $servicesQuery = $user->services()->with([
                'category', 'favorites', 'bookings', 'reviews', 'user',
                'event',
                'catering.items',
                'restaurant.tables',
                'property.bedrooms',
                'property.kitchens',
                'property.pools',
                'property.bathrooms',
                'property.livingRooms',
                'property.facilities',
            ]);

            if ($mainServiceId) {
                $servicesQuery->whereHas('category', fn($q) => $q->where('main_service_id', (int)$mainServiceId));
            }
            $services = $servicesQuery->get();

            $now = now();
            $past = $services->filter(fn($s) => ($s->event->end_at ?? $s->available_to) && now()->gt($s->event->end_at ?? $s->available_to))->values();
            $current = $services->filter(fn($s) => ($s->event->start_at ?? $s->available_from) && ($s->event->end_at ?? $s->available_to) && now()->between($s->event->start_at ?? $s->available_from, $s->event->end_at ?? $s->available_to))->values();
            $upcoming = $services->filter(fn($s) => ($s->event->start_at ?? $s->available_from) && now()->lt($s->event->start_at ?? $s->available_from))->values();

            // ✅ الخطوة 2: تبسيط دالة العرض لتعتمد دائمًا على ServiceResource
            // الـ ServiceResource ذكي كفاية ليعرض كل أنواع الخدمات بشكل صحيح
            $mapServices = function($collection) {
                return ServiceResource::collection($collection);
            };

            return format_response(true, 'Fetched', [
                'user' => (new UserResource($user))->resolve(),
                'provider_services' => [
                    'past' => $mapServices($past),
                    'current' => $mapServices($current),
                    'upcoming' => $mapServices($upcoming),
                ],
            ]);
        }

        // --- Customer Logic ---
        $bookings = \App\Models\Booking::query()
            ->with([
                'service.category', 'service.user.companyProfile', 'service.reviews', 'service.favorites',
                'service.event', 'service.catering.items', 'service.restaurant.tables',
                'service.property.bedrooms', 'service.property.kitchens', 'service.property.pools',
                'service.property.bathrooms', 'service.property.livingRooms', 'service.property.facilities',
            ])
            ->where('user_id', $user->id)
            ->where('privacy', 'public')
            ->where('status', '!=', 'cancelled')
            ->get();

        $now = now();
        $pastB = $bookings->filter(fn($b) => optional($b->end_date)->lt($now))->values();
        $currentB = $bookings->filter(fn($b) => optional($b->start_date)->lte($now) && optional($b->end_date)->gte($now))->values();
        $upcomingB = $bookings->filter(fn($b) => optional($b->start_date)->gt($now))->values();

        $mapBookings = function($collection) use ($includeDetails, $request) {
            return $collection->map(function($b) use ($includeDetails, $request) {
                $s = $b->service;
                $base = [
                    'booking_id' => $b->id,
                    'start_date' => optional($b->start_date)->toDateTimeString(),
                    'end_date' => optional($b->end_date)->toDateTimeString(),
                    'status' => $b->status,
                ];
                if ($includeDetails && $s) {
                    $base['service'] = new ServiceResource($s);
                } else if ($s) {
                    $base['service'] = [ 'id' => $s->id, 'name' => $s->name /* ... other simple fields if needed */ ];
                } else {
                     $base['service'] = null;
                }
                return $base;
            })->values();
        };

        return format_response(true, 'Fetched', [
            'user' => (new UserResource($user))->resolve(),
            'customer_participations' => [
                'past' => $mapBookings($pastB),
                'current' => $mapBookings($currentB),
                'upcoming' => $mapBookings($upcomingB),
            ],
        ]);
    }
}