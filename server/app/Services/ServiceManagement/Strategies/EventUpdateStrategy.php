<?php

namespace App\Services\ServiceManagement\Strategies;

use App\Models\Service;
use App\Services\ServiceManagement\Contracts\ServiceUpdateStrategy;
use App\Services\Contracts\IEventService;
use Illuminate\Support\Facades\Log;

class EventUpdateStrategy implements ServiceUpdateStrategy
{
    public function __construct(
        private readonly IEventService $eventService
    ) {}

    /**
     * Update an event service
     *
     * @param Service $service
     * @param array $data
     * @return Service
     */
    public function update(Service $service, array $data): Service
    {
        Log::info("Updating event service", [
            'service_id' => $service->id,
            'has_event_data' => isset($data['event']),
            'has_media' => $this->hasMediaFiles($data)
        ]);

        return $this->eventService->updateEvent($service, $data);
    }

    /**
     * Validate event-specific data
     *
     * @param array $data
     * @return array
     */
    public function validateData(array $data): array
    {
        $errors = [];

        // Check if this is an event update request
        if (!$this->isEventUpdateRequest($data)) {
            $errors[] = 'Invalid event update request';
            return $errors;
        }

        // Validate event-specific fields if provided
        if (isset($data['event'])) {
            $eventData = $data['event'];
            
            // Validate required fields
            if (isset($eventData['max_individuals']) && $eventData['max_individuals'] < 1) {
                $errors[] = 'Maximum individuals must be at least 1';
            }

            if (isset($eventData['base_price']) && $eventData['base_price'] < 0) {
                $errors[] = 'Base price must be non-negative';
            }

            if (isset($eventData['start_at']) && isset($eventData['end_at'])) {
                if (strtotime($eventData['start_at']) >= strtotime($eventData['end_at'])) {
                    $errors[] = 'Event end time must be after start time';
                }
            }
        }

        return $errors;
    }

    /**
     * Get the service type this strategy handles
     *
     * @return string
     */
    public function getServiceType(): string
    {
        return 'event';
    }

    /**
     * Check if this is an event update request
     *
     * @param array $data
     * @return bool
     */
    private function isEventUpdateRequest(array $data): bool
    {
        return isset($data['event']) || 
               $this->hasMediaFiles($data);
    }

    /**
     * Check if request has media files
     *
     * @param array $data
     * @return bool
     */
    private function hasMediaFiles(array $data): bool
    {
        $request = request();
        return $request->hasFile('images') || 
               $request->hasFile('videos') ||
               $request->hasFile('event.images') ||
               $request->hasFile('event.videos');
    }
}

