<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendNotificationRequest;
use App\Models\User;
use App\Services\NotificationService;

class NotificationsController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function send(SendNotificationRequest $request)
    {
        $this->authorize('sendNotification', User::class);

        $data = $request->validated();
        $result = $this->notifications->created($data);
        if ($result) {
            return format_response(true, __('Notification sent'), $result);
        }
        return format_response(false, __('Something Went Wrong'), code: 500);
    }
}

