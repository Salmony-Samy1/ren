<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Service;
use App\Services\Contracts\IPropertyService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PropertyService implements IPropertyService
{
    /**
     * Relationships with generic structures that can be looped through.
     * Custom-schema relationships like bedrooms are handled in dedicated methods.
     */
    protected array $nestedRelationships = [
        'livingRooms',
    ];

    /**
     * Creates a new Service with its associated Property and all nested relationships.
     *
     * @param array $payload The validated data from the request.
     * @return Service The newly created service instance.
     */
    public function createProperty(array $payload): Service
    {
        return DB::transaction(function () use ($payload) {
            // 1. Create the parent Service record (contains only general info).
            $service = Service::create($this->mapServiceData($payload));

            // 2. Isolate the property-specific and location data from the main payload.
            $propertyPayload = $payload['property'];
            
            // Remove file data from propertyPayload to avoid array issues
            if (isset($propertyPayload['legal_documents'])) {
                foreach ($propertyPayload['legal_documents'] as $index => $doc) {
                    if (isset($doc['file'])) {
                        unset($propertyPayload['legal_documents'][$index]['file']);
                    }
                }
            }
            
            $locationData = Arr::only($payload, ['address', 'latitude', 'longitude', 'place_id']);

            // 3. Merge all property-related data and create the Property record.
            $fullPropertyData = array_merge($this->mapPropertyData($propertyPayload), $locationData);
            // Optionally map region/neighbourhood if provided at service-level payload
            if (isset($payload['region_id'])) { $fullPropertyData['region_id'] = $payload['region_id']; }
            if (isset($payload['neigbourhood_id'])) { $fullPropertyData['neigbourhood_id'] = $payload['neigbourhood_id']; }
            if (isset($payload['city_id'])) { $fullPropertyData['city_id'] = $payload['city_id']; }

            $property = $service->property()->create($fullPropertyData);

            // 4. Create all nested relationship records using their specific sync methods.
            $this->syncBedrooms($property, $propertyPayload);
            $this->syncKitchens($property, $propertyPayload);
            $this->syncPools($property, $propertyPayload);
            $this->syncBathrooms($property, $propertyPayload);
            $this->syncNestedRelationships($property, $propertyPayload); // For generic ones like living rooms
            $this->syncLegalDocuments($property, $propertyPayload);

            // 5. Sync the many-to-many facilities relationship.
            if (isset($propertyPayload['facilities'])) {
                $property->facilities()->sync($propertyPayload['facilities']);
            }

            $this->setPendingAndNotify($service, 'property_created');
            // Attach uploaded media on create from either root or property.* keys
            $req = request();
            if (!empty($req->file('property.images')) || !empty($req->file('images'))) {
                $images = $req->file('property.images') ?? $req->file('images');
                // Handle both single file and array of files
                if (!is_array($images)) {
                    $images = [$images];
                }
                foreach ($images as $image) {
                    if ($image instanceof \Illuminate\Http\UploadedFile) {
                        $property->addMedia($image)->toMediaCollection('property_images');
                    }
                }
            }
            if (!empty($req->file('property.videos')) || !empty($req->file('videos'))) {
                $videos = $req->file('property.videos') ?? $req->file('videos');
                // Handle both single file and array of files
                if (!is_array($videos)) {
                    $videos = [$videos];
                }
                foreach ($videos as $video) {
                    if ($video instanceof \Illuminate\Http\UploadedFile) {
                        $property->addMedia($video)->toMediaCollection('property_videos');
                    }
                }
            }
            
            // Handle section-specific photos
            $photoTypes = ['bedroom_photos', 'kitchen_photos', 'pool_photos', 'bathroom_photos', 'living_room_photos'];
            foreach ($photoTypes as $photoType) {
                $photos = $req->file("property.{$photoType}") ?? $req->file($photoType) ?? [];
                if (!is_array($photos)) {
                    $photos = [$photos];
                }
                foreach ($photos as $photo) {
                    if ($photo instanceof \Illuminate\Http\UploadedFile) {
                        $property->addMedia($photo)->toMediaCollection($photoType);
                    }
                }
            }

            Log::info("Successfully created service #{$service->id} with property #{$property->id}");
            return $service->fresh('property');
        });
    }

    /**
     * Updates an existing Service and its associated Property with partial data.
     *
     * @param Service $service The service to update.
     * @param array $payload The validated data from the request.
     * @return Service The updated service instance.
     */
    public function updateProperty(Service $service, array $payload): Service
    {
        return DB::transaction(function () use ($service, $payload) {
            // 1. Update the parent Service record with any provided data.
            $serviceData = $this->mapServiceData($payload, true);
            if (!empty($serviceData)) {
                $service->update($serviceData);
            }

            // 2. Check if there is property-specific data to update.
            if (isset($payload['property'])) {
                $property = $service->property;
                $propertyPayload = $payload['property'];
                if (isset($payload['region_id'])) { $fullPropertyData['region_id'] = $payload['region_id']; }
                if (isset($payload['neigbourhood_id'])) { $fullPropertyData['neigbourhood_id'] = $payload['neigbourhood_id']; }
                if (isset($payload['city_id'])) { $fullPropertyData['city_id'] = $payload['city_id']; }


                $locationData = Arr::only($payload, ['address', 'latitude', 'longitude', 'place_id']);
                $propertyUpdateData = $this->mapPropertyData($propertyPayload);

                $fullPropertyData = array_merge($propertyUpdateData, $locationData);
                if(!empty($fullPropertyData)){
                    $property->update($fullPropertyData);
                }

                // 3. "Replace-on-write" strategy for nested relationships: delete old and create new.
                if (array_key_exists('bedrooms', $propertyPayload)) {
                    $property->bedrooms()->delete();
                    $this->syncBedrooms($property, $propertyPayload);
                }
                if (array_key_exists('kitchens', $propertyPayload)) {
                    $property->kitchens()->delete();
                    $this->syncKitchens($property, $propertyPayload);
                }
                if (array_key_exists('pools', $propertyPayload)) {
                    $property->pools()->delete();
                    $this->syncPools($property, $propertyPayload);
                }
                if (array_key_exists('bathrooms', $propertyPayload)) {
                    $property->bathrooms()->delete();
                    $this->syncBathrooms($property, $propertyPayload);
                }
                foreach ($this->nestedRelationships as $relation) {
                    if (array_key_exists($relation, $propertyPayload)) {
                        $property->$relation()->delete();
                    }
                }
                $this->syncNestedRelationships($property, $propertyPayload);

                // 4. Sync many-to-many facilities relationship.
                if (array_key_exists('facilities', $propertyPayload)) {
                    $property->facilities()->sync($propertyPayload['facilities'] ?? []);
                }

            }

            // Attach uploaded media on update even if 'property' payload is missing
            $req = request();
            if ($service->property) {
                if (!empty($req->file('property.images')) || !empty($req->file('images'))) {
                    $service->property->clearMediaCollection('property_images');
                    $images = $req->file('property.images') ?? $req->file('images');
                    // Handle both single file and array of files
                    if (!is_array($images)) {
                        $images = [$images];
                    }
                    foreach ($images as $image) {
                        if ($image instanceof \Illuminate\Http\UploadedFile) {
                            $service->property->addMedia($image)->toMediaCollection('property_images');
                        }
                    }
                }
                if (!empty($req->file('property.videos')) || !empty($req->file('videos'))) {
                    $service->property->clearMediaCollection('property_videos');
                    $videos = $req->file('property.videos') ?? $req->file('videos');
                    // Handle both single file and array of files
                    if (!is_array($videos)) {
                        $videos = [$videos];
                    }
                    foreach ($videos as $video) {
                        if ($video instanceof \Illuminate\Http\UploadedFile) {
                            $service->property->addMedia($video)->toMediaCollection('property_videos');
                        }
                    }
                }
            }

            $this->setPendingAndNotify($service, 'property_updated');
            Log::info("Successfully updated service #{$service->id}");
            return $service->fresh(['property.bedrooms', 'property.kitchens', 'property.pools', 'property.bathrooms', 'property.livingRooms', 'property.facilities']);
        });
    }

    /**
     * Deletes a Service and its associated Property.
     *
     * @param Service $service The service to delete.
     * @return void
     */
    public function deleteProperty(Service $service): void
    {
        DB::transaction(function () use ($service) {
            optional($service->property)->delete();
            $service->delete();
            $this->setPendingAndNotify($service, 'property_deleted');
            Log::info("Successfully deleted service #{$service->id}");
        });
    }

    /**
     * Prepares data for the Service model, handling partial updates.
     */
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
        foreach (['address','latitude','longitude','place_id','city_id','district','gender_type','price_currency_id','country_id','price_amount','available_from','available_to'] as $f) {
            if (array_key_exists($f, $payload)) { $serviceData[$f] = $payload[$f]; }
        }
        return $serviceData;
    }

    /**
     * Prepares data for the Property model, handling partial updates.
     */
    private function mapPropertyData(array $propertyPayload): array
    {
        $mappedData = [];
        $fields = [
            'property_name', 'type', 'category', 'unit_code', 'area_sqm',
            'down_payment_percentage', 'is_refundable_insurance', 'cancellation_policy',
            'description', 'allowed_category', 'access_instructions', 'nightly_price',
            'max_adults', 'max_children', 'child_free_until_age', 'checkin_time',
            'checkout_time', 'room_details', 'city_id', 'region_id', 'neigbourhood_id',
            'direction'
        ];
        foreach ($fields as $field) {
            if (array_key_exists($field, $propertyPayload)) {
                $mappedData[$field] = $propertyPayload[$field];
            }
        }
        // Provide minimal sane defaults for required columns if missing
        $mappedData['category'] = $mappedData['category'] ?? 'property';
        $mappedData['unit_code'] = $mappedData['unit_code'] ?? ('UNIT-'.strtoupper(Str::random(6)));
        $mappedData['area_sqm'] = $mappedData['area_sqm'] ?? 100;
        $mappedData['down_payment_percentage'] = $mappedData['down_payment_percentage'] ?? 0;
        $mappedData['is_refundable_insurance'] = $mappedData['is_refundable_insurance'] ?? false;
        $mappedData['cancellation_policy'] = $mappedData['cancellation_policy'] ?? 'flexible';
        $mappedData['description'] = $mappedData['description'] ?? '';
        $mappedData['allowed_category'] = $mappedData['allowed_category'] ?? 'family';
        $mappedData['room_details'] = $mappedData['room_details'] ?? [];
        $mappedData['access_instructions'] = $mappedData['access_instructions'] ?? '';
        $mappedData['checkin_time'] = $mappedData['checkin_time'] ?? '15:00';
        $mappedData['checkout_time'] = $mappedData['checkout_time'] ?? '12:00';
        return $mappedData;
    }

    private function syncBedrooms(Property $property, array $propertyPayload): void
    {
        if (isset($propertyPayload['bedrooms']) && is_array($propertyPayload['bedrooms'])) {
            foreach ($propertyPayload['bedrooms'] as $bedroomData) {
                $property->bedrooms()->create([
                    'beds_count' => $bedroomData['count'] ?? 1,
                    'is_master'  => isset($bedroomData['is_master']) ? (bool)$bedroomData['is_master'] : (bool) (str_contains(strtolower($bedroomData['bed_type'] ?? ''), 'king')),
                ]);
            }
        }
    }

    private function syncKitchens(Property $property, array $propertyPayload): void
    {
        if (isset($propertyPayload['kitchens']) && is_array($propertyPayload['kitchens'])) {
            foreach ($propertyPayload['kitchens'] as $kitchenData) {
                $property->kitchens()->create([
                    'type' => $kitchenData['type'] ?? 'standard',
                    'appliances' => $kitchenData['features'] ?? $kitchenData['appliances'] ?? null,
                    'dining_chairs' => $kitchenData['dining_chairs'] ?? 0,
                ]);
            }
        }
    }

    private function syncPools(Property $property, array $propertyPayload): void
    {
        if (isset($propertyPayload['pools']) && is_array($propertyPayload['pools'])) {
            foreach ($propertyPayload['pools'] as $poolData) {
                $features = (string)($poolData['features'] ?? '');
                $property->pools()->create([
                    'type' => $poolData['type'] ?? null,
                    'length_m' => $poolData['length_m'] ?? 0,
                    'width_m' => $poolData['width_m'] ?? 0,
                    'depth_m' => $poolData['depth_m'] ?? 0,
                    'has_heating' => $poolData['has_heating'] ?? str_contains($features, 'تدفئة'),
                    'has_barrier' => $poolData['has_barrier'] ?? str_contains($features, 'حاجز') || str_contains($features, 'سياج'),
                    'has_water_games' => $poolData['has_water_games'] ?? str_contains($features, 'لعب للأطفال'),
                    'is_graduated' => $poolData['is_graduated'] ?? false,
                ]);
            }
        }
    }

    private function syncBathrooms(Property $property, array $propertyPayload): void
    {
        if (isset($propertyPayload['bathrooms']) && is_array($propertyPayload['bathrooms'])) {
            foreach ($propertyPayload['bathrooms'] as $bathroomData) {
                $amenities = [
                    'type' => $bathroomData['type'] ?? null,
                    'count' => $bathroomData['count'] ?? null,
                    'features' => $bathroomData['features'] ?? null,
                ];
                $property->bathrooms()->create([
                    'count' => $bathroomData['count'] ?? 1,
                    'amenities' => $amenities,
                ]);
            }
        }
    }

    private function syncNestedRelationships(Property $property, array $propertyPayload): void
    {
        foreach ($this->nestedRelationships as $relation) {
            if (isset($propertyPayload[$relation]) && is_array($propertyPayload[$relation])) {
                foreach ($propertyPayload[$relation] as $itemData) {
                    // property_living_rooms schema supports 'type' and 'capacity'
                    if ($relation === 'livingRooms') {
                        $property->$relation()->create([
                            'type' => $itemData['type'] ?? 'main',
                            'capacity' => $itemData['capacity'] ?? 0
                        ]);
                    } else {
                        $property->$relation()->create($itemData);
                    }
                }
            }
        }
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
            Log::warning('Failed to set pending state (property): '.$e->getMessage());
        }

        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => $service->user_id,
                'action' => 'service_pending',
                'message' => __('تم إرسال خدمتك للمراجعة بعد تحديث: ').$action,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify provider failed (property): '.$e->getMessage()); }

        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => 1,
                'action' => 'service_change_review',
                'message' => __('تغيير جديد على خدمة يتطلب المراجعة: ').$service->name,
            ]);
        } catch (\Throwable $e) { Log::warning('Notify admin failed (property): '.$e->getMessage()); }
    }

    private function syncLegalDocuments(Property $property, array $propertyPayload): void
    {
        if (isset($propertyPayload['legal_documents']) && is_array($propertyPayload['legal_documents'])) {
            foreach ($propertyPayload['legal_documents'] as $index => $documentData) {
                // Get file from request directly to avoid array issues
                $file = null;
                
                // Try to get file from request directly
                $request = request();
                $fileKey = "property.legal_documents.{$index}.file";
                $file = $request->file($fileKey);
                
                // If not found, try alternative key format
                if (!$file) {
                    $fileKey = "property[legal_documents][{$index}][file]";
                    $file = $request->file($fileKey);
                }
                
                // If still not found, try from documentData
                if (!$file && isset($documentData['file'])) {
                    $file = $documentData['file'];
                    
                    // Handle array case
                    if (is_array($file)) {
                        $file = reset($file);
                    }
                }
                
                // Debug logging
                Log::info("Processing legal document {$index}:", [
                    'file_key' => $fileKey,
                    'file_from_request' => $file ? 'found' : 'not found',
                    'file_type' => $file ? gettype($file) : 'null',
                    'is_array' => $file ? is_array($file) : false,
                    'is_uploaded_file' => $file instanceof \Illuminate\Http\UploadedFile,
                    'document_type' => $documentData['document_type'] ?? 'missing',
                    'document_name' => $documentData['document_name'] ?? 'missing'
                ]);
                
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('property_legal_documents', $fileName, 'public');
                    
                    $property->legalDocuments()->create([
                        'document_type' => $documentData['document_type'],
                        'document_name' => $documentData['document_name'],
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'description' => $documentData['description'] ?? null,
                    ]);
                    
                    Log::info("Successfully created legal document {$index}:", [
                        'file_name' => $fileName,
                        'file_path' => $filePath
                    ]);
                } else {
                    Log::warning("Skipped legal document {$index} - invalid file:", [
                        'file_type' => $file ? gettype($file) : 'null',
                        'file_value' => $file
                    ]);
                }
            }
        }
    }
}

