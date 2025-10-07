<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCollection;
use App\Services\WishService;

class WishlistController extends Controller
{
    public function __construct(private readonly WishService $service)
    {
    }

    public function getAllServices()
    {
        $user = auth('api')->user();
        $services = $this->service->getWishedServices($user);
        return new ServiceCollection($services);
    }

    public function getAllActivities()
    {
        $user = auth('api')->user();
        $activities = $this->service->getWishedActivities($user);
        return new ServiceCollection($activities);
    }

    public function addService(int $service)
    {
        $user = auth('api')->user();
        $this->service->addService($user, $service);
        return format_response(true, __('Service added to wishlist successfully'));
    }

    public function removeService(int $service)
    {
        $user = auth('api')->user();
        $this->service->removeService($user, $service);
        return format_response(true, __('Service removed from wishlist successfully'));
    }

    public function addActivity(int $activity)
    {
        $user = auth('api')->user();
        $this->service->addActivity($user, $activity);
        return format_response(true, __('Activity added to wishlist successfully'));
    }

    public function removeActivity(int $activity)
    {
        $user = auth('api')->user();
        $this->service->removeActivity($user, $activity);
        return format_response(true, __('Activity removed from wishlist successfully'));
    }


}
