<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserNotificationCollection;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationsController extends Controller
{
    public function __construct(private readonly NotificationService $service)
    {
    }

    public function index()
    {
        $user_id = auth('api')->id();
        $result = $this->service->getUserNotifications($user_id);
        if ($result) {
            return new UserNotificationCollection($result);
        }
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function read(UserNotification $notification)
    {
        $result = $this->service->markAsRead($notification->id);
        if ($result) {
            return response()->json([
                'success' => true,
                'data' => new \App\Http\Resources\UserNotificationResource($result)
            ]);
        }
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['unread_count' => 0]);
        }
        
        $unreadCount = UserNotification::where('user_id', $user->id)
                                       ->where('is_read', false)
                                       ->count();
        
        return response()->json(['unread_count' => $unreadCount]);
    }

}
