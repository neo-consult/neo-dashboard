<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

interface NotificationRestRequestFactory
{
    public function create(mixed $request): NotificationRestRequest;
}
