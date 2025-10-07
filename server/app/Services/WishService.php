<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Service;
use App\Models\User;

class WishService
{
    public function addService(User $user, int $service_id)
    {
        $exists = $user->wishedServices()->where('services.id', $service_id)->exists();
        if ($exists) {
            return;
        }
        $service = Service::findOrFail($service_id);
        $user->wishedServices()->attach($service);
    }

    public function addActivity(User $user, int $activity_id)
    {
        $exists = $user->wishedActivities()->where('activities.id', $activity_id)->exists();
        if ($exists) {
            return;
        }
        $activity = Activity::findOrFail($activity_id);
        $user->wishedActivities()->attach($activity);
    }

    public function removeService(User $user, int $service_id)
    {
        $service = Service::findOrFail($service_id);

        $exists = $user->wishedServices()->where('services.id', $service_id)->exists();
        if (!$exists) {
            return;
        }
        $user->wishedServices()->detach($service);
    }

    public function removeActivity(User $user, int $activity_id)
    {
        $activity = Activity::findOrFail($activity_id);
        $exists = $user->wishedActivities()->where('activities.id', $activity_id)->exists();
        if (!$exists) {
            return;
        }
        $user->wishedActivities()->detach($activity);
    }

    public function getWishedServices(User $user)
    {
        return $user->wishedServices()->get();
    }

    public function getWishedActivities(User $user)
    {
        return $user->wishedActivities()->get();
    }

}
