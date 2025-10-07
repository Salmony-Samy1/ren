<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\RestaurantMenuItem;
use App\Models\RestaurantTable;
use App\Models\Service;
use App\Services\Contracts\IRestaurantService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestaurantService implements IRestaurantService
{
    public function createRestaurant(array $payload): Service
    {
        return DB::transaction(function () use ($payload) {
            $service = Service::create($this->mapServiceData($payload));
            $restaurantPayload = $payload['restaurant'] ?? [];
            $restaurant = $service->restaurant()->create($restaurantPayload);

            $this->setPendingAndNotify($service, 'restaurant_created');
            Log::info("Successfully created service #{$service->id} with restaurant #{$restaurant->id}");
            return $service->fresh('restaurant');
        });
    }

    public function updateRestaurant(Service $service, array $payload): Service
    {
        return DB::transaction(function () use ($service, $payload) {
            $service->update($this->mapServiceData($payload, true));
            if (isset($payload['restaurant'])) {
                $service->restaurant()->update($payload['restaurant']);
            }
            $this->setPendingAndNotify($service, 'restaurant_updated');
            Log::info("Successfully updated service #{$service->id}");
            return $service->fresh('restaurant');
        });
    }

    public function deleteRestaurant(Service $service): void
    {
        DB::transaction(function () use ($service) {
            optional($service->restaurant)->delete();
            $service->delete();
            $this->setPendingAndNotify($service, 'restaurant_deleted');
            Log::info("Successfully deleted service #{$service->id}");
        });
    }

    // ===== Menu Items =====
    public function listMenuItems(Restaurant $restaurant)
    {
        $this->assertOwnedByCurrentUser($restaurant->service);
        return $restaurant->menuItems()->latest()->get();
    }

    public function createMenuItem(Restaurant $restaurant, array $data): RestaurantMenuItem
    {
        return DB::transaction(function () use ($restaurant, $data) {
            $this->assertOwnedByCurrentUser($restaurant->service);
            $item = $restaurant->menuItems()->create($this->mapMenuItemData($data));
            $this->setPendingAndNotify($restaurant->service, 'menu_item_created');
            return $item;
        });
    }

    public function updateMenuItem(Restaurant $restaurant, RestaurantMenuItem $menuItem, array $data): RestaurantMenuItem
    {
        return DB::transaction(function () use ($restaurant, $menuItem, $data) {
            $this->assertOwnedByCurrentUser($restaurant->service);
            if ($menuItem->restaurant_id !== $restaurant->id) { abort(404); }
            $menuItem->update($this->mapMenuItemData($data, true));
            $this->setPendingAndNotify($restaurant->service, 'menu_item_updated');
            return $menuItem->fresh();
        });
    }

    public function deleteMenuItem(Restaurant $restaurant, RestaurantMenuItem $menuItem): void
    {
        DB::transaction(function () use ($restaurant, $menuItem) {
            $this->assertOwnedByCurrentUser($restaurant->service);
            if ($menuItem->restaurant_id !== $restaurant->id) { abort(404); }
            $menuItem->delete();
            $this->setPendingAndNotify($restaurant->service, 'menu_item_deleted');
        });
    }

    // ===== Tables =====
    public function listTables(Restaurant $restaurant)
    {
        $this->assertOwnedByCurrentUser($restaurant->service);
        return $restaurant->tables()->latest()->get();
    }

    public function createTable(Restaurant $restaurant, array $data): RestaurantTable
    {
        return DB::transaction(function () use ($restaurant, $data) {
            $this->assertOwnedByCurrentUser($restaurant->service);
            $table = $restaurant->tables()->create($this->mapTableData($data));
            $this->setPendingAndNotify($restaurant->service, 'table_created');
            return $table;
        });
    }

    public function updateTable(Restaurant $restaurant, RestaurantTable $table, array $data): RestaurantTable
    {
        return DB::transaction(function () use ($restaurant, $table, $data) {
            $this->assertOwnedByCurrentUser($restaurant->service);
            if ($table->restaurant_id !== $restaurant->id) { abort(404); }
            $table->update($this->mapTableData($data, true));
            $this->setPendingAndNotify($restaurant->service, 'table_updated');
            return $table->fresh();
        });
    }

    public function deleteTable(Restaurant $restaurant, RestaurantTable $table): void
    {
        DB::transaction(function () use ($restaurant, $table) {
            $this->assertOwnedByCurrentUser($restaurant->service);
            if ($table->restaurant_id !== $restaurant->id) { abort(404); }
            $table->delete();
            $this->setPendingAndNotify($restaurant->service, 'table_deleted');
        });
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
        foreach (['address','latitude','longitude','place_id','city_id','district','gender_type','price_currency_id','country_id','price_amount','available_from','available_to'] as $f) {
            if (array_key_exists($f, $payload)) { $serviceData[$f] = $payload[$f]; }
        }
        return $serviceData;
    }

    private function mapMenuItemData(array $data, bool $isUpdate = false): array
    {
        $fields = ['name','description','price','media_url','is_active'];
        $mapped = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) { $mapped[$f] = $data[$f]; }
        }
        return $mapped;
    }

    private function mapTableData(array $data, bool $isUpdate = false): array
    {
        $fields = [
            'name','type','capacity_people','price_per_person','price_per_table','quantity',
            're_availability_type','auto_re_availability_minutes','conditions','amenities','media'
        ];
        $mapped = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) { $mapped[$f] = $data[$f]; }
        }
        return $mapped;
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
            Log::warning('Failed to set pending state: '.$e->getMessage());
        }

        // Notify provider
        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => $service->user_id,
                'action' => 'service_pending',
                'message' => __('تم إرسال خدمتك للمراجعة بعد تحديث: ').$action,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify provider failed: '.$e->getMessage()); }

        // Notify admin (using user_id=1 as admin placeholder)
        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => 1,
                'action' => 'service_change_review',
                'message' => __('تغيير جديد على خدمة يتطلب المراجعة: ').$service->name,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify admin failed: '.$e->getMessage()); }
    }
}
