<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

final readonly class NotificationUserContext
{
    /**
     * @param array<int, string> $roles
     * @param array<int, string> $dismissedIds
     */
    public function __construct(
        public int $userId,
        public array $roles,
        public array $dismissedIds,
        public int $now,
    ) {}
}
