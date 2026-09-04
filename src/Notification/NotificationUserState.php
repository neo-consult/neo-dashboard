<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

interface NotificationUserState
{
    public function current(): NotificationUserContext;

    public function dismiss(string $id, int $userId): bool;
}
