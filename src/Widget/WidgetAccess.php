<?php
declare(strict_types=1);
namespace NeoDashboard\Core\Widget;

interface WidgetAccess
{
    /** @param array<string, mixed> $widget */
    public function allows(array $widget): bool;
}
