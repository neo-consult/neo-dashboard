<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Notification;

use NeoDashboard\Core\Notification\NotificationUserContext;
use NeoDashboard\Core\Notification\NotificationVisibilityFilter;
use PHPUnit\Framework\TestCase;

final class NotificationVisibilityFilterTest extends TestCase
{
    public function testItFiltersExpiredRoleRestrictedAndDismissedNotifications(): void
    {
        $filter = new NotificationVisibilityFilter();
        $context = new NotificationUserContext(7, ['editor'], ['dismissed'], 1_000);

        $active = $filter->active([
            'expired' => $this->notification('expired', 20, expires: 999),
            'wrong-role' => $this->notification('wrong-role', 20, roles: ['administrator']),
            'dismissed' => $this->notification('dismissed', 20),
            'visible' => $this->notification('visible', 20, roles: ['editor']),
        ], $context);

        self::assertSame(['visible'], array_column($active, 'id'));
    }

    public function testItSortsActiveNotificationsByAscendingPriority(): void
    {
        $filter = new NotificationVisibilityFilter();
        $context = new NotificationUserContext(7, [], [], 1_000);

        $active = $filter->active([
            'later' => $this->notification('later', 30),
            'first' => $this->notification('first', 5),
            'middle' => $this->notification('middle', 10),
        ], $context);

        self::assertSame(['first', 'middle', 'later'], array_column($active, 'id'));
    }

    /** @param array<int, string>|null $roles */
    private function notification(
        string $id,
        int $priority,
        ?array $roles = null,
        ?int $expires = null,
    ): array {
        return compact('id', 'priority', 'roles', 'expires');
    }
}
