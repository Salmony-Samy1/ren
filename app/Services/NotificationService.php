<?php

namespace App\Services;

use App\Http\Resources\UserNotificationResource;
use App\Models\User;
use App\Notifications\SendNotification;
use App\Repositories\UserNotificationRepo\IUserNotificationRepo;
use App\Models\UserNotification;

class NotificationService
{
    public function __construct(private readonly IUserNotificationRepo $notificationRepo)
    {
    }

    public function getUserNotifications(int $user_id)
    {
        return $this->notificationRepo->getAll(paginated: true, filter: ['user_id' => $user_id]);
    }

    public function markAsRead(int $notification_id)
    {
        return $this->notificationRepo->update($notification_id, ['is_read' => true]);
    }

    public function created(array $data)
    {
        // Extract the message and locale from the data for the translation model
        $message = $data['message'] ?? null;
        $locale = $data['locale'] ?? app()->getLocale();
        unset($data['message']);

        // Create the main notification record without the message field
        $result = $this->notificationRepo->create($data);

        if($result){
            // Create the translation record for the message
            $result->translations()->create([
                'locale' => $locale,
                'message' => $message,
            ]);

            $user = $result->user;
            $notificationData = (new UserNotificationResource($result))->response()->getData();
            // Cast the stdClass object to an array before passing it to the notification
            $user->notify(new SendNotification((array) $notificationData));
            // Also push silent payload via FCM topic if enabled (Kreait)
            app(\App\Services\FirebasePushService::class)->sendToUserTopic($user->id, [
                'type' => $result->action,
                'id' => $result->id,
                'unread_count' => (int) \App\Models\UserNotification::where('user_id',$user->id)->where('is_read',false)->count(),
            ]);
        }

        return $result;
    }
}