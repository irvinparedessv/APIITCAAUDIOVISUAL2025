<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('notifications.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
