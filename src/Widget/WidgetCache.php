<?php
declare(strict_types=1);
namespace NeoDashboard\Core\Widget;

interface WidgetCache
{
    public function get(string $widgetId, int $ttl): ?string;
    public function put(string $widgetId, string $html, int $ttl): void;
}
