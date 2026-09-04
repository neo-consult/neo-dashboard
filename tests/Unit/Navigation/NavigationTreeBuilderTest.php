<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Navigation;

use NeoDashboard\Core\Navigation\NavigationTreeBuilder;
use PHPUnit\Framework\TestCase;

final class NavigationTreeBuilderTest extends TestCase
{
    public function testItBuildsAnOrderedTreeAndResolvesPresentationCallbacks(): void
    {
        $tree = (new NavigationTreeBuilder())->build([
            'child' => [
                'label_callback' => static fn(): string => 'Child',
                'tooltip' => static fn(string $label): string => "Open {$label}",
                'parent' => 'group',
                'position' => 20,
            ],
            'home' => ['label_callback' => static fn(): string => 'Home', 'position' => 0],
            'group' => [
                'label_callback' => static fn(): string => 'Group',
                'position' => 10,
                'is_group' => true,
            ],
        ]);

        self::assertSame(['home', 'group'], array_keys($tree));
        self::assertSame('Group', $tree['group']['label']);
        self::assertSame('Child', $tree['group']['children']['child']['label']);
        self::assertSame('Open Child', $tree['group']['children']['child']['tooltip']);
    }
}
