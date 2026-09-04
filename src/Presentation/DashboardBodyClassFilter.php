<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

final class DashboardBodyClassFilter
{
    /** @param list<string> $classes @return list<string> */
    public function filter(array $classes, bool $dashboardRequest): array
    {
        if (!$dashboardRequest) {
            return $classes;
        }

        $removed = [
            'wp-singular', 'page-template', 'page-template-dashboard-blank',
            'page-template-dashboard-blank-php', 'page', 'wp-embed-responsive',
            'wp-theme-twentytwentyfive', 'wp-theme-twentytwentytwo',
        ];
        $classes = array_values(array_filter(
            $classes,
            static fn(string $class): bool => !in_array($class, $removed, true)
                && !self::isPageIdClass($class),
        ));

        if (!in_array('neo-dashboard-standalone', $classes, true)) {
            $classes[] = 'neo-dashboard-standalone';
        }

        return $classes;
    }

    private static function isPageIdClass(string $class): bool
    {
        return str_starts_with($class, 'page-id-')
            && ctype_digit(substr($class, strlen('page-id-')));
    }
}
