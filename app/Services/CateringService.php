<?php

namespace App\Services;

use App\Models\Catering;
use App\Models\Service;
use App\Services\Contracts\ICateringService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CateringService implements ICateringService
{
    public function createCatering(array $payload): Service
    {
        return DB::transaction(function () use ($payload) {
            // Log the incoming payload structure for debugging
            Log::info('CateringService::createCatering payload analysis', [
                'payload_keys' => array_keys($payload),
                'has_catering_item' => isset($payload['catering_item']),
                'has_catering_items' => isset($payload['catering_items']),
                'catering_item_structure' => $payload['catering_item'] ?? 'N/A',
                'catering_items_structure' => $payload['catering_items'] ?? 'N/A',
                'full_payload' => $payload
            ]);
            
            // Record legal acceptance first if provided
            $userId = $payload['user_id'] ?? Auth::id();
            $legalTermsService = new \App\Services\CateringLegalTermsService();
            
            if (isset($payload['legal_page_ids']) && is_array($payload['legal_page_ids'])) {
                $legalAcceptance = $legalTermsService->recordLegalAcceptance($userId, $payload['legal_page_ids']);
                Log::info('Legal acceptance recorded for catering service creation', $legalAcceptance);
            }
            
            // Validate legal compliance after recording acceptance
            $legalValidation = $legalTermsService->validateBeforeCreatingCateringService($userId);
            
            if (!$legalValidation['can_proceed']) {
                throw new \InvalidArgumentException($legalValidation['message']);
            }
            
            $serviceData = $this->mapServiceData($payload);
            
            // Validate required fields before creating service
            if (empty($serviceData['name'])) {
                throw new \InvalidArgumentException('Service name is required');
            }
            if (empty($serviceData['category_id'])) {
                throw new \InvalidArgumentException('Category ID is required');
            }
            if (empty($serviceData['user_id'])) {
                throw new \InvalidArgumentException('User ID is required');
            }
            
            $service = Service::create($serviceData);
            
            if (!$service) {
                throw new \Exception('Failed to create service - Service::create returned null');
            }
            // Build catering payload: allow top-level multilingual description to live under catering.description
            $cateringPayload = $payload['catering'] ?? [];
            if (array_key_exists('description', $payload)) {
                $cateringPayload['description'] = $payload['description'];
            }
            // available_stock for main catering
            if (!isset($cateringPayload['available_stock'])) {
                $cateringPayload['available_stock'] = 0;
            }
            
            // Handle catering-specific fields
            if (isset($payload['catering_name'])) {
                $cateringPayload['catering_name'] = $payload['catering_name'];
            }
            if (isset($payload['cuisine_type'])) {
                $cateringPayload['cuisine_type'] = $payload['cuisine_type'];
            }
            if (isset($payload['min_order_amount'])) {
                $cateringPayload['min_order_amount'] = $payload['min_order_amount'];
            }
            if (isset($payload['max_order_amount'])) {
                $cateringPayload['max_order_amount'] = $payload['max_order_amount'];
            }
            if (isset($payload['preparation_time'])) {
                $cateringPayload['preparation_time'] = $payload['preparation_time'];
            }
            if (isset($payload['delivery_available'])) {
                $cateringPayload['delivery_available'] = $payload['delivery_available'];
            }
            if (isset($payload['delivery_radius_km'])) {
                $cateringPayload['delivery_radius_km'] = $payload['delivery_radius_km'];
            }
            if (isset($payload['catering']['fulfillment_methods'])) {
                $cateringPayload['fulfillment_methods'] = $payload['catering']['fulfillment_methods'];
            }
            $catering = $service->catering()->create($cateringPayload);

            // Attach uploaded media for catering (support both root-level and nested under catering.)
            try {
                // Top-level images[] / videos[]
                if (!empty(request()->file('images'))) {
                    foreach (request()->file('images') as $image) {
                        $catering->addMedia($image)->toMediaCollection('catering_images');
                    }
                }
                if (!empty(request()->file('videos'))) {
                    foreach (request()->file('videos') as $video) {
                        $catering->addMedia($video)->toMediaCollection('catering_videos');
                    }
                }
                // Nested catering[images][] / catering[videos][]
                if (!empty(request()->file('catering.images'))) {
                    foreach (request()->file('catering.images') as $image) {
                        $catering->addMedia($image)->toMediaCollection('catering_images');
                    }
                }
                if (!empty(request()->file('catering.videos'))) {
                    foreach (request()->file('catering.videos') as $video) {
                        $catering->addMedia($video)->toMediaCollection('catering_videos');
                    }
                }
            } catch (\Throwable $e) { /* ignore media errors here; validation already checked */ }

            // Handle catering items from form-data structure (catering_items[0][meal_name], etc.)
            if (!empty($payload['catering_items']) && is_array($payload['catering_items'])) {
                Log::info('Processing catering_items from direct form-data structure');
                
                foreach ($payload['catering_items'] as $idx => $item) {
                    $mealName = $item['meal_name'] ?? null;
                    $price = isset($item['price']) ? (float) $item['price'] : null;
                    $servingsCount = isset($item['servings_count']) ? (int) $item['servings_count'] : 1;
                    $description = $item['description'] ?? '';
                    $availableStock = isset($item['available_stock']) ? (int) $item['available_stock'] : (isset($item['availability_stock']) ? (int) $item['availability_stock'] : 0);
                    
                    // Handle additional fields
                    $availabilitySchedule = $item['availability_schedule'] ?? null;
                    if (is_string($availabilitySchedule) && $availabilitySchedule) {
                        // Try to parse as JSON if it's a string
                        try {
                            $availabilitySchedule = json_decode($availabilitySchedule, true);
                        } catch (\Exception $e) {
                            // If not JSON, keep as string
                            $availabilitySchedule = $availabilitySchedule;
                        }
                    }
                    
                    // Handle other optional fields
                    $deliveryIncluded = isset($item['delivery_included']) ? (bool) $item['delivery_included'] : false;
                    $offerDuration = $item['offer_duration'] ?? null;
                    
                    Log::info("Processing catering item fields", [
                        'meal_name' => $mealName,
                        'description' => $description,
                        'available_stock' => $availableStock,
                        'availability_schedule' => $availabilitySchedule,
                        'delivery_included' => $deliveryIncluded,
                        'offer_duration' => $offerDuration
                    ]);
                    
                    if ($mealName && $price !== null) {
                        // Create the catering item
                        $categoryId = $item['category_id'] ?? null;
                        $cateringItemData = [
                            'service_id' => $service->id,
                            'meal_name' => $mealName,
                            'price' => $price,
                            'servings_count' => $servingsCount,
                            'availability_schedule' => $availabilitySchedule,
                            'delivery_included' => $deliveryIncluded,
                            'offer_duration' => $offerDuration,
                            'available_stock' => $availableStock,
                            'description' => $description,
                            'packages' => null,
                            'category_id' => $categoryId,
                        ];
                        
                        // Log the data being saved
                        Log::info("Creating catering item with data", [
                            'meal_name' => $mealName,
                            'category_id_from_item' => $item['category_id'] ?? 'NOT_PROVIDED',
                            'category_id_value' => $categoryId,
                            'data' => $cateringItemData
                        ]);
                        
                        $cateringItem = $catering->items()->create($cateringItemData);
                        
                        // Handle photos for this catering item
                        if (!empty($item['photos']) && is_array($item['photos'])) {
                            Log::info("Processing photos for catering item: {$mealName}", [
                                'photos_count' => count($item['photos']),
                                'photos_structure' => $item['photos']
                            ]);
                            
                            foreach ($item['photos'] as $photoIndex => $photoFile) {
                                Log::info("Processing photo {$photoIndex}", [
                                    'photo_file_type' => gettype($photoFile),
                                    'is_uploaded_file' => $photoFile instanceof \Illuminate\Http\UploadedFile,
                                    'file_name' => $photoFile instanceof \Illuminate\Http\UploadedFile ? $photoFile->getClientOriginalName() : 'N/A'
                                ]);
                                
                                if ($photoFile instanceof \Illuminate\Http\UploadedFile) {
                                    try {
                                        // Add photo to catering item media collection
                                        $mediaItem = $cateringItem->addMedia($photoFile)
                                            ->toMediaCollection('catering_item_photos');
                                        Log::info("Successfully added photo {$photoIndex} to catering item: {$mealName}", [
                                            'media_id' => $mediaItem->id,
                                            'file_name' => $mediaItem->file_name
                                        ]);
                                    } catch (\Exception $e) {
                                        Log::error("Failed to add photo to catering item: {$mealName}", [
                                            'photo_index' => $photoIndex,
                                            'error' => $e->getMessage(),
                                            'trace' => $e->getTraceAsString()
                                        ]);
                                    }
                                } else {
                                    Log::warning("Photo {$photoIndex} is not an UploadedFile", [
                                        'photo_type' => gettype($photoFile),
                                        'photo_value' => $photoFile
                                    ]);
                                }
                            }
                        } else {
                            Log::info("No photos found for catering item: {$mealName}", [
                                'has_photos_key' => isset($item['photos']),
                                'photos_value' => $item['photos'] ?? 'N/A'
                            ]);
                        }
                        
                        Log::info("Created catering item: {$mealName} - {$price}");
                    }
                }
            }
            // Also handle catering_item.packages as add-ons list (legacy support)
            elseif (!empty($payload['catering_item']) && is_array($payload['catering_item'])) {
                $ci = $payload['catering_item'];
                $availability = $ci['availability_schedule'] ?? null;
                if (!empty($ci['packages']) && is_array($ci['packages'])) {
                    foreach ($ci['packages'] as $idx => $pkg) {
                        $name = $pkg['package_name'] ?? ($pkg['name'] ?? null);
                        $price = isset($pkg['price']) ? (float) $pkg['price'] : null;
                        $desc = null;
                        if (!empty($pkg['items']) && is_array($pkg['items'])) {
                            $desc = implode('، ', $pkg['items']);
                        }
                        // available_stock for add-on: prefer per-package, fallback to array map by index, else 0
                        $pkgStock = isset($pkg['available_stock']) ? (int)$pkg['available_stock'] : 0;
                        if ($pkgStock === 0 && !empty($ci['available_stock']) && is_array($ci['available_stock'])) {
                            $pkgStock = (int)($ci['available_stock'][$idx] ?? 0);
                        }
                        
                        // Validate service exists before creating catering item
                        if (!$service || !isset($service->id)) {
                            throw new \Exception('Cannot create catering item - service is null or has no ID');
                        }
                        
                        $categoryId = $pkg['category_id'] ?? null;
                        
                        Log::info("Creating package catering item", [
                            'package_name' => $name,
                            'category_id_from_package' => $pkg['category_id'] ?? 'NOT_PROVIDED',
                            'category_id_value' => $categoryId
                        ]);
                        
                        $cateringItem = $catering->items()->create([
                            'service_id' => $service->id,
                            'meal_name' => $name,
                            'price' => $price,
                            'servings_count' => 1,
                            'availability_schedule' => $availability,
                            'delivery_included' => false,
                            'offer_duration' => null,
                            'available_stock' => $pkgStock,
                            'description' => $desc,
                            'packages' => null,
                            'category_id' => $categoryId,
                        ]);

                        // Handle photos for this package catering item
                        if (!empty($pkg['images']) && is_array($pkg['images'])) {
                            foreach ($pkg['images'] as $photoIndex => $photoFile) {
                                if ($photoFile instanceof \Illuminate\Http\UploadedFile) {
                                    try {
                                        // Add photo to catering item media collection
                                        $mediaItem = $cateringItem->addMedia($photoFile)
                                            ->toMediaCollection('catering_item_photos');
                                        Log::info("Successfully added package photo {$photoIndex} to catering item: {$name}", [
                                            'media_id' => $mediaItem->id,
                                            'file_name' => $mediaItem->file_name
                                        ]);
                                    } catch (\Exception $e) {
                                        Log::error("Failed to add package photo to catering item: {$name}", [
                                            'photo_index' => $photoIndex,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->setPendingAndNotify($service, 'catering_created');
            Log::info("Successfully created service #{$service->id} with catering #{$catering->id}");
            return $service->fresh(['catering' => function($q){ $q->with('items'); }]);
        });
    }

    public function updateCatering(Service $service, array $payload): Service
    {
        return DB::transaction(function () use ($service, $payload) {
            $this->assertOwnedByCurrentUser($service);
            $service->update($this->mapServiceData($payload, true));
            if (isset($payload['catering'])) {
                $cateringPayload = $payload['catering'];
                if (array_key_exists('description', $payload)) {
                    $cateringPayload['description'] = $payload['description'];
                }
                
                // Handle catering-specific fields
                if (isset($payload['catering_name'])) {
                    $cateringPayload['catering_name'] = $payload['catering_name'];
                }
                if (isset($payload['cuisine_type'])) {
                    $cateringPayload['cuisine_type'] = $payload['cuisine_type'];
                }
                if (isset($payload['min_order_amount'])) {
                    $cateringPayload['min_order_amount'] = $payload['min_order_amount'];
                }
                if (isset($payload['max_order_amount'])) {
                    $cateringPayload['max_order_amount'] = $payload['max_order_amount'];
                }
                if (isset($payload['preparation_time'])) {
                    $cateringPayload['preparation_time'] = $payload['preparation_time'];
                }
                if (isset($payload['delivery_available'])) {
                    $cateringPayload['delivery_available'] = $payload['delivery_available'];
                }
                if (isset($payload['delivery_radius_km'])) {
                    $cateringPayload['delivery_radius_km'] = $payload['delivery_radius_km'];
                }
                
                $cateringModel = $service->catering;
                if ($cateringModel) {
                    $cateringModel->update($cateringPayload); // use model instance to trigger JSON casting
                }
            }

            // Sync add-ons: remove all and recreate from provided packages for simplicity (can be optimized later)
            if (!empty($payload['catering_item']) && is_array($payload['catering_item'])) {
                $ci = $payload['catering_item'];
                $availability = $ci['availability_schedule'] ?? null;
                $catering = $service->catering()->firstOrFail();
                // Clear existing
                $catering->items()->delete();
                if (!empty($ci['packages']) && is_array($ci['packages'])) {
                    foreach ($ci['packages'] as $idx => $pkg) {
                        $name = $pkg['package_name'] ?? ($pkg['name'] ?? null);
                        $price = isset($pkg['price']) ? (float) $pkg['price'] : null;
                        $desc = null;
                        if (!empty($pkg['items']) && is_array($pkg['items'])) {
                            $desc = implode('، ', $pkg['items']);
                        }
                        $pkgStock = isset($pkg['available_stock']) ? (int)$pkg['available_stock'] : 0;
                        if ($pkgStock === 0 && !empty($ci['available_stock']) && is_array($ci['available_stock'])) {
                            $pkgStock = (int)($ci['available_stock'][$idx] ?? 0);
                        }
                        $categoryId = $pkg['category_id'] ?? null;
                        $catering->items()->create([
                            'service_id' => $service->id,
                            'meal_name' => $name,
                            'price' => $price,
                            'servings_count' => 1,
                            'availability_schedule' => $availability,
                            'delivery_included' => false,
                            'offer_duration' => null,
                            'available_stock' => $pkgStock,
                            'description' => $desc,
                            'packages' => null,
                            'category_id' => $categoryId,
                        ]);
                    }
                }
            }

            // Attach uploaded media on update (support both root-level and nested under catering.)
            try {
                $catering = $service->catering()->first();
                if ($catering) {
                    if (!empty(request()->file('images'))) {
                        $catering->clearMediaCollection('catering_images');
                        foreach (request()->file('images') as $image) {
                            $catering->addMedia($image)->toMediaCollection('catering_images');
                        }
                    }
                    if (!empty(request()->file('videos'))) {
                        $catering->clearMediaCollection('catering_videos');
                        foreach (request()->file('videos') as $video) {
                            $catering->addMedia($video)->toMediaCollection('catering_videos');
                        }
                    }
                    if (!empty(request()->file('catering.images'))) {
                        $catering->clearMediaCollection('catering_images');
                        foreach (request()->file('catering.images') as $image) {
                            $catering->addMedia($image)->toMediaCollection('catering_images');
                        }
                    }
                    if (!empty(request()->file('catering.videos'))) {
                        $catering->clearMediaCollection('catering_videos');
                        foreach (request()->file('catering.videos') as $video) {
                            $catering->addMedia($video)->toMediaCollection('catering_videos');
                        }
                    }
                }
            } catch (\Throwable $e) { /* ignore; guarded by validation */ }


            $this->setPendingAndNotify($service, 'catering_updated');
            return $service->fresh(['catering' => function($q){ $q->with('items'); }]);
        });
    }

    public function deleteCatering(Service $service): void
    {
        DB::transaction(function () use ($service) {
            $this->assertOwnedByCurrentUser($service);
            optional($service->catering)->delete();
            $service->delete();
            $this->setPendingAndNotify($service, 'catering_deleted');
        });
    }

    private function mapServiceData(array $payload, bool $isUpdate = false): array
    {
        $data = [];
        if (!$isUpdate) { $data['user_id'] = Auth::id(); }
        if (array_key_exists('category_id', $payload)) { $data['category_id'] = $payload['category_id']; }
        if (array_key_exists('name', $payload)) {
            $locale = strtolower(request()->header('Accept-Language', 'ar')) === 'en' ? 'en' : 'ar';
            $name = is_array($payload['name']) ? ($payload['name'][$locale] ?? ($payload['name']['ar'] ?? $payload['name']['en'])) : $payload['name'];
            $data['name'] = $name;
        }
        foreach (['address','latitude','longitude','place_id','city_id','district','gender_type','country_id','price_amount'] as $f) {
            if (array_key_exists($f, $payload)) { $data[$f] = $payload[$f]; }
        }
        
        // Set price_currency_id from country_id automatically
        if (array_key_exists('country_id', $payload)) {
            $country = \App\Models\Country::find($payload['country_id']);
            if ($country && $country->currency_id) {
                $data['price_currency_id'] = $country->currency_id;
            }
        }
        
        return $data;
    }

    private function assertOwnedByCurrentUser(Service $service): void
    {
        if ((int)$service->user_id !== (int)Auth::id()) { abort(403); }
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
            Log::warning('Failed to set pending state (catering service): '.$e->getMessage());
        }

        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => $service->user_id,
                'action' => 'service_pending',
                'message' => __('تم إرسال خدمتك للمراجعة بعد تحديث: ').$action,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify provider failed (catering service): '.$e->getMessage()); }

        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => 1,
                'action' => 'service_change_review',
                'message' => __('تغيير جديد على خدمة يتطلب المراجعة: ').$service->name,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify admin failed (catering service): '.$e->getMessage()); }
    }
}

