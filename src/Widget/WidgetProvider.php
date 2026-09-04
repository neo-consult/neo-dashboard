<?php
declare(strict_types=1);
namespace NeoDashboard\Core\Widget;

interface WidgetProvider
{
    /** @return array<string, mixed>|null */
    public function find(string $widgetId): ?array;
}
