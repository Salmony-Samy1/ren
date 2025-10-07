<?php

namespace App\Services\ServiceCreation\Strategies;

use App\Models\Service;
use App\Services\ServiceCreation\Contracts\IServiceCreationStrategy;
use App\Services\EventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventServiceStrategy implements IServiceCreationStrategy
{
    private EventService $eventService;
    
    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }
    
    public function createService(array $data): Service
    {
        return DB::transaction(function () use ($data) {
            try {
                $service = $this->eventService->createEvent($data);
                Log::info("Event service created successfully", [
                    'service_id' => $service->id,
                    'user_id' => $service->user_id
                ]);
                return $service;
            } catch (\Exception $e) {
                Log::error("Failed to create event service", [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                throw $e;
            }
        });
    }
    
    public function validateData(array $data): array
    {
        $errors = [];
        
        if (!isset($data['event'])) {
            $errors[] = 'Event data is required';
        }
        
        if (isset($data['event'])) {
            $event = $data['event'];
            
            if (empty($event['event_name'])) {
                $errors[] = 'Event name is required';
            }
            
            if (empty($event['max_individuals'])) {
                $errors[] = 'Maximum individuals is required';
            }
            
            if (empty($event['start_at'])) {
                $errors[] = 'Event start date is required';
            }
            
            if (empty($event['end_at'])) {
                $errors[] = 'Event end date is required';
            }
        }
        
        return $errors;
    }
    
    public function getFormRequest(): string
    {
        return \App\Http\Requests\StoreEventServiceRequest::class;
    }
    
    public function getServiceType(): string
    {
        return 'event';
    }
}
