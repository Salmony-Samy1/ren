<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RestaurantTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'capacity_people' => $this->capacity_people,
            'price_per_person' => $this->price_per_person,
            'price_per_table' => $this->price_per_table,
            'quantity' => $this->quantity,
            're_availability_type' => $this->re_availability_type,
            'auto_re_availability_minutes' => $this->auto_re_availability_minutes,
            
            // صور الطاولة من جدول media
            'images' => $this->getTableImages(),
            
            // شروط الحجز
            'booking_conditions' => $this->getBookingConditions(),
            
            // المميزات
            'amenities' => $this->getAmenities(),
            
            // الأوقات المحجوزة
            'booked_times' => $this->getBookedTimes(),
            
            // معلومات إضافية
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * جلب صور الطاولة من جدول media
     */
    private function getTableImages(): array
    {
        try {
            // استخدام العلاقة مباشرة بدلاً من getMedia()
            return $this->media()
                ->where('collection_name', 'table_images')
                ->get()
                ->map(function (Media $media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'thumbnail' => $media->getUrl('thumb'),
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'size' => $media->size,
                        'mime_type' => $media->mime_type,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * تنسيق شروط الحجز
     */
    private function getBookingConditions(): array
    {
        $defaultConditions = [
            'minimum_advance_booking' => '2 hours',
            'maximum_advance_booking' => '30 days',
            'cancellation_policy' => 'Free cancellation up to 24 hours before',
            'deposit_required' => false,
            'deposit_amount' => 0,
            'age_restrictions' => null,
            'dress_code' => 'Smart casual',
            'special_requirements' => [],
        ];

        return array_merge($defaultConditions, $this->conditions ?? []);
    }

    /**
     * تنسيق المميزات
     */
    private function getAmenities(): array
    {
        $defaultAmenities = [
            'wifi' => false,
            'air_conditioning' => true,
            'smoking_area' => false,
            'outdoor_seating' => false,
            'wheelchair_accessible' => false,
            'private_dining' => false,
            'live_music' => false,
            'parking' => false,
            'valet_parking' => false,
        ];

        return array_merge($defaultAmenities, $this->amenities ?? []);
    }

    /**
     * جلب الأوقات المحجوزة للطاولة
     */
    private function getBookedTimes(): array
    {
        try {
            // البحث في جدول table_reservations
            $reservations = $this->tableReservations()
                ->where('status', '!=', 'cancelled')
                ->where('start_time', '>=', now())
                ->orderBy('start_time')
                ->get()
                ->map(function ($reservation) {
                    return [
                        'reservation_id' => $reservation->id,
                        'start_time' => $reservation->start_time,
                        'end_time' => $reservation->end_time,
                        'status' => $reservation->status,
                        'customer_name' => $reservation->user->name ?? 'Unknown',
                        'notes' => $reservation->notes,
                    ];
                });

            // البحث في جدول bookings أيضاً
            $bookings = \App\Models\Booking::where('service_id', $this->restaurant->service_id)
                ->where('status', '!=', 'cancelled')
                ->where('booking_details->table_id', $this->id)
                ->where('start_date', '>=', now())
                ->orderBy('start_date')
                ->get()
                ->map(function ($booking) {
                    return [
                        'booking_id' => $booking->id,
                        'start_time' => $booking->start_date,
                        'end_time' => $booking->end_date,
                        'status' => $booking->status,
                        'customer_name' => $booking->user->name ?? 'Unknown',
                        'number_of_people' => $booking->booking_details['number_of_people'] ?? 1,
                    ];
                });

            return $reservations->concat($bookings)->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}

