<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Extension\Registry;

use NeoDashboard\Core\Extension\Registry\NavigationRegistry;
use NeoDashboard\Core\Extension\Registry\SectionRegistry;
use NeoDashboard\Core\Extension\Registry\WidgetRegistry;
use NeoDashboard\Core\Extension\Registry\NotificationRegistry;
use NeoDashboard\Core\Extension\Registry\DashboardRegistries;
use NeoDashboard\Core\Navigation\NavigationTreeBuilder;
use PHPUnit\Framework\TestCase;

final class ComponentRegistriesTest extends TestCase
{
    public function testRegistryContainersShareStateOnlyWithinTheirOwnRuntime(): void
    {
        $first = $this->registries();
        $second = $this->registries();

        $first->sections()->add('calendar', ['slug' => 'calendar']);

        self::assertSame($first->sections(), $first->sections());
        self::assertArrayHasKey('calendar', $first->sections()->all());
        self::assertSame([], $second->sections()->all());
        self::assertNotSame($first->sections(), $second->sections());
    }

    public function testNavigationBuildsAnOrderedTranslatedTree(): void
    {
        $registry = new NavigationRegistry(new NavigationTreeBuilder());
        $registry->add('group', [
            'slug' => 'group',
            'label_callback' => static fn(): string => 'Group',
            'position' => 20,
            'is_group' => true,
        ]);
        $registry->add('home', [
            'slug' => 'home',
            'label_callback' => static fn(): string => 'Home',
            'position' => 0,
        ]);
        $registry->add('child', [
            'slug' => 'child',
            'label_callback' => static fn(): string => 'Child',
            'parent' => 'group',
            'position' => 21,
        ]);

        $tree = $registry->tree();
        self::assertSame(['home', 'group'], array_keys($tree));
        self::assertSame('Group', $tree['group']['label']);
        self::assertSame('Child', $tree['group']['children']['child']['label']);
    }

    public function testSectionRejectsDuplicatesAndCachesResolvedLabels(): void
    {
        $calls = 0;
        $registry = new SectionRegistry();
        self::assertTrue($registry->add('calendar', [
            'slug' => 'calendar',
            'label_callback' => static function () use (&$calls): string {
                ++$calls;
                return 'Calendar';
            },
        ]));
        self::assertFalse($registry->add('calendar', ['slug' => 'calendar']));

        self::assertSame('Calendar', $registry->get('calendar')['label']);
        self::assertSame('Calendar', $registry->get('calendar')['label']);
        self::assertSame(1, $calls);
        self::assertNull($registry->get('missing'));
    }

    public function testWidgetCanBeReplacedByItsStableId(): void
    {
        $registry = new WidgetRegistry();
        $registry->add('calendar', ['priority' => 10]);
        $registry->add('calendar', ['priority' => 20]);

        self::assertSame(['calendar' => ['priority' => 20]], $registry->all());
    }

    public function testNotificationCanBeReplacedByItsStableId(): void
    {
        $registry = new NotificationRegistry();
        $registry->add('deadline', ['message' => 'First']);
        $registry->add('deadline', ['message' => 'Updated']);

        self::assertSame(['deadline' => ['message' => 'Updated']], $registry->all());
    }

    private function registries(): DashboardRegistries
    {
        return new DashboardRegistries(
            new NavigationRegistry(new NavigationTreeBuilder()),
            new SectionRegistry(),
            new WidgetRegistry(),
            new NotificationRegistry(),
        );
    }

}
