<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Notification;

final class NotificationVisibilityFilter
{
    /**
     * @param array<string, array<string, mixed>> $notifications
     * @return array<int, array<string, mixed>>
     */
    public function active(array $notifications, NotificationUserContext $context): array
    {
        $active = array_filter(
            $notifications,
            static function (array $notification) use ($context): bool {
                if (!empty($notification['expires']) && $context->now > (int) $notification['expires']) {
                    return false;
                }

                $targetRoles = $notification['roles'] ?? null;
                if (is_array($targetRoles) && $targetRoles !== []
                    && array_intersect($targetRoles, $context->roles) === []) {
                    return false;
                }

                return !in_array($notification['id'] ?? '', $context->dismissedIds, true);
            },
        );

        uasort(
            $active,
            static fn(array $left, array $right): int =>
                (int) ($left['priority'] ?? 10) <=> (int) ($right['priority'] ?? 10),
        );

        return array_values($active);
    }
}
