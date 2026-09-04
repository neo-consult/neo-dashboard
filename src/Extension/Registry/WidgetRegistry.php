<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Extension\Registry;

final class WidgetRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $widgets = [];

    /** @param array<string, mixed> $definition */
    public function add(string $id, array $definition): void
    {
        $this->widgets[$id] = $definition;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return $this->widgets;
    }
}
