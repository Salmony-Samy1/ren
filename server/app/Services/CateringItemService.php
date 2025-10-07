<?php

namespace App\Services;

use App\Models\Service;
use App\Services\Contracts\ICateringItemService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CateringItemService implements ICateringItemService
{
    public function createCateringItem(array $payload): Service
    {
        return DB::transaction(function () use ($payload) {
            $service = Service::create($this->mapServiceData($payload));
            $cateringPayload = $payload['catering_item'];
            $cateringItem = $service->cateringItem()->create($cateringPayload);

            $this->setPendingAndNotify($service, 'catering_created');
            Log::info("Successfully created service #{$service->id} with catering item #{$cateringItem->id}");
            return $service->fresh('cateringItem');
        });
    }

    public function updateCateringItem(Service $service, array $payload): Service
    {
        return DB::transaction(function () use ($service, $payload) {
            $this->assertOwnedByCurrentUser($service);
            $service->update($this->mapServiceData($payload, true));
            if (isset($payload['catering_item'])) {
                $service->cateringItem()->update($payload['catering_item']);
            }
            $this->setPendingAndNotify($service, 'catering_updated');
            Log::info("Successfully updated service #{$service->id}");
            return $service->fresh('cateringItem');
        });
    }

    public function deleteCateringItem(Service $service): void
    {
        DB::transaction(function () use ($service) {
            $this->assertOwnedByCurrentUser($service);
            optional($service->cateringItem)->delete();
            $service->delete();
            $this->setPendingAndNotify($service, 'catering_deleted');
            Log::info("Successfully deleted service #{$service->id}");
        });
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
            Log::warning('Failed to set pending state (catering): '.$e->getMessage());
        }

        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => $service->user_id,
                'action' => 'service_pending',
                'message' => __('تم إرسال خدمتك للمراجعة بعد تحديث: ').$action,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify provider failed (catering): '.$e->getMessage()); }

        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => 1,
                'action' => 'service_change_review',
                'message' => __('تغيير جديد على خدمة يتطلب المراجعة: ').$service->name,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify admin failed (catering): '.$e->getMessage()); }
    }
    
    private function mapServiceData(array $payload, bool $isUpdate = false): array
    {
        $serviceData = [];
        if (!$isUpdate) {
            $serviceData['user_id'] = Auth::id();
        }
        if (array_key_exists('category_id', $payload)) {
            $serviceData['category_id'] = $payload['category_id'];
        }
        if (array_key_exists('name', $payload)) {
            $locale = strtolower(request()->header('Accept-Language', 'ar')) === 'en' ? 'en' : 'ar';
            $name = is_array($payload['name']) ? ($payload['name'][$locale] ?? ($payload['name']['ar'] ?? $payload['name']['en'])) : $payload['name'];
            $serviceData['name'] = $name;
        }
        return $serviceData;
    }
}
