<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Extension\Registry;

use NeoDashboard\Core\Navigation\NavigationTreeBuilder;

final class NavigationRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $items = [];

    public function __construct(private NavigationTreeBuilder $treeBuilder) {}

    /** @param array<string, mixed> $definition */
    public function add(string $slug, array $definition): void
    {
        $this->items[$slug] = $definition;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return $this->items;
    }

    /** @return array<string, array<string, mixed>> */
    public function tree(): array
    {
        return $this->treeBuilder->build($this->items);
    }
}
