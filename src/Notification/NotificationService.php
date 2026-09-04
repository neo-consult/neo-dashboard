<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

use NeoDashboard\Core\Extension\Registry\NotificationRegistry;

final readonly class NotificationService
{
    public function __construct(
        private NotificationRegistry $registry,
        private NotificationUserState $userState,
        private NotificationVisibilityFilter $visibilityFilter,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function activeForCurrentUser(): array
    {
        return $this->visibilityFilter->active($this->registry->all(), $this->userState->current());
    }

    public function dismiss(string $id, int $userId): bool
    {
        return $this->userState->dismiss($id, $userId);
    }
}
