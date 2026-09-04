<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

interface NotificationRestRequest
{
    public function notificationId(): string;

    public function userId(): int;
}
