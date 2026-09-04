<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Navigation;

final class NavigationTreeBuilder
{
    /**
     * @param array<string, array<string, mixed>> $items
     * @return array<string, array<string, mixed>>
     */
    public function build(array $items): array
    {
        uasort(
            $items,
            static fn(array $left, array $right): int =>
                (int) ($left['position'] ?? 10) <=> (int) ($right['position'] ?? 10),
        );

        $tree = [];
        foreach ($items as $slug => $item) {
            $item = $this->resolvePresentation($item);
            if (empty($item['parent'])) {
                $tree[$slug] = $item;
                continue;
            }

            $parent = (string) $item['parent'];
            if (!isset($tree[$parent]) && isset($items[$parent]) && !empty($items[$parent]['is_group'])) {
                $tree[$parent] = $this->resolvePresentation($items[$parent]);
            }

            $tree[$parent]['children'][$slug] = $item;
        }

        return $tree;
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function resolvePresentation(array $item): array
    {
        $item['label'] = isset($item['label_callback']) && is_callable($item['label_callback'])
            ? (string) ($item['label_callback'])()
            : '';

        if (isset($item['tooltip']) && is_callable($item['tooltip'])) {
            $item['tooltip'] = (string) ($item['tooltip'])($item['label']);
        }

        return $item;
    }
}
