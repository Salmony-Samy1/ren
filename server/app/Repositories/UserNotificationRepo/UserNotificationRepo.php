<?php

namespace App\Repositories\UserNotificationRepo;

use App\Models\UserNotification;
use App\Repositories\BaseRepo;

class UserNotificationRepo extends BaseRepo implements IUserNotificationRepo
{
    public function __construct()
    {
        $this->model = UserNotification::class;
    }
}