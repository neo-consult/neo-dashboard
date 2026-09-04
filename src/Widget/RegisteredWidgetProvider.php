<?php
declare(strict_types=1);
namespace NeoDashboard\Core\Widget;

use NeoDashboard\Core\Extension\Registry\WidgetRegistry;
use NeoDashboard\Core\Manager\WidgetManager;

final class RegisteredWidgetProvider implements WidgetProvider
{
    public function __construct(
        private readonly WidgetManager $manager,
        private readonly WidgetRegistry $registry,
    ) {}

    public function find(string $widgetId): ?array
    {
        $this->manager->loadDefinitions();
        return $this->registry->all()[$widgetId] ?? null;
    }
}
