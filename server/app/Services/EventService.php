<?php

namespace App\Services;

use App\Models\Service;
use App\Services\Contracts\IEventService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventService implements IEventService
{
    public function createEvent(array $payload): Service
    {
        return DB::transaction(function () use ($payload) {
            $service = Service::create($this->mapServiceData($payload));
            $eventPayload = $payload['event'];
            // Normalize event fields per new spec
            unset($eventPayload['prices_by_age']);
            $event = $service->event()->create($eventPayload);

            // Attach uploaded media if present on root
            if (!empty(request()->file('images'))) {
                foreach (request()->file('images') as $image) {
                    $event->addMedia($image)->toMediaCollection('event_images');
                }
            }
            if (!empty(request()->file('videos'))) {
                foreach (request()->file('videos') as $video) {
                    $event->addMedia($video)->toMediaCollection('event_videos');
                }
            }

            // Mirror price to parent service for discovery
            if (isset($eventPayload['base_price'])) {
                $service->update([
                    'price_amount' => (float)$eventPayload['base_price'],
                ]);
            }

            // Attach uploaded media on update (only when updating an existing event)


            $this->setPendingAndNotify($service, 'event_created');
            Log::info("Successfully created service #{$service->id} with event #{$event->id}");
            return $service->fresh('event');
        });
    }

    public function updateEvent(Service $service, array $payload): Service
    {
        return DB::transaction(function () use ($service, $payload) {
            $this->assertOwnedByCurrentUser($service);
            $service->update($this->mapServiceData($payload, true));
            if (isset($payload['event'])) {
                $eventPayload = $payload['event'];
                unset($eventPayload['prices_by_age']);
                $service->event()->update($eventPayload);

                // Mirror price to parent service for discovery
                if (isset($eventPayload['base_price'])) {
                    $service->update([
                        'price_amount' => (float)$eventPayload['base_price'],
                    ]);
                }
            }

            // Attach uploaded media on update
            if (!empty(request()->file('images'))) {
                $service->event->clearMediaCollection('event_images');
                foreach (request()->file('images') as $image) {
                    $service->event->addMedia($image)->toMediaCollection('event_images');
                }
            }
            if (!empty(request()->file('videos'))) {
                $service->event->clearMediaCollection('event_videos');
                foreach (request()->file('videos') as $video) {
                    $service->event->addMedia($video)->toMediaCollection('event_videos');
                }
            }

            $this->setPendingAndNotify($service, 'event_updated');
            Log::info("Successfully updated service #{$service->id}");
            return $service->fresh('event');
        });
    }

    public function deleteEvent(Service $service): void
    {
        DB::transaction(function () use ($service) {
            $this->assertOwnedByCurrentUser($service);
            optional($service->event)->delete();
            $service->delete();
            $this->setPendingAndNotify($service, 'event_deleted');
            Log::info("Successfully deleted service #{$service->id}");
        });
    }

    private function mapServiceData(array $payload, bool $isUpdate = false): array
    {
        $serviceData = [];
        if (!$isUpdate) {
            // Allow admin to explicitly set the service owner (provider)
            $serviceData['user_id'] = $payload['user_id'] ?? Auth::id();
        }
        if (array_key_exists('category_id', $payload)) {
            $serviceData['category_id'] = $payload['category_id'];
        }
        if (array_key_exists('name', $payload)) {
            $locale = strtolower(request()->header('Accept-Language', 'ar')) === 'en' ? 'en' : 'ar';
            $name = is_array($payload['name']) ? ($payload['name'][$locale] ?? ($payload['name']['ar'] ?? $payload['name']['en'])) : $payload['name'];
            $serviceData['name'] = $name;
        }
        foreach (['address','latitude','longitude','place_id','city_id','district','gender_type','country_id','price_amount'] as $f) {
            if (array_key_exists($f, $payload)) { $serviceData[$f] = $payload[$f]; }
        }
        
        // Set price_currency_id from country_id automatically
        if (array_key_exists('country_id', $payload)) {
            $country = \App\Models\Country::find($payload['country_id']);
            if ($country && $country->currency_id) {
                $serviceData['price_currency_id'] = $country->currency_id;
            }
        }
        
        return $serviceData;
    }

    private function assertOwnedByCurrentUser(Service $service): void
    {
        if ((int)$service->user_id !== (int)Auth::id()) {
            abort(403);
        }
    }

    private function setPendingAndNotify(Service $service, string $action): void
    {
        try {
            $service->update([
                'is_approved' => false,
                'approved_at' => null,
                'approval_notes' => trim(($service->approval_notes ? $service->approval_notes.' ' : '').'(Auto) '.$action.'; pending review'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to set pending state (event): '.$e->getMessage());
        }

        // Notify provider
        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => $service->user_id,
                'action' => 'service_pending',
                'message' => __('تم إرسال خدمتك للمراجعة بعد تحديث: ').$action,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify provider failed (event): '.$e->getMessage()); }

        // Notify admin
        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => 1,
                'action' => 'service_change_review',
                'message' => __('تغيير جديد على خدمة يتطلب المراجعة: ').$service->name,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify admin failed (event): '.$e->getMessage()); }
    }
}


