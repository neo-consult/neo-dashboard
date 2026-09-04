<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Presentation;

use NeoDashboard\Core\Extension\Registry\NavigationRegistry;
use NeoDashboard\Core\Extension\Registry\NotificationRegistry;
use NeoDashboard\Core\Extension\Registry\SectionRegistry;
use NeoDashboard\Core\Extension\Registry\WidgetRegistry;
use NeoDashboard\Core\Presentation\DashboardViewModelFactory;
use NeoDashboard\Core\Navigation\NavigationTreeBuilder;
use NeoDashboard\Core\Routing\SectionResolver;
use PHPUnit\Framework\TestCase;

final class DashboardViewModelFactoryTest extends TestCase
{
    public function testItFiltersComponentsAndResolvesAnAllowedSection(): void
    {
        [$factory, $navigation, $notifications, $sections, $widgets] = $this->factory();
        $navigation->add('calendar', ['roles' => ['member']]);
        $navigation->add('admin', ['roles' => ['administrator']]);
        $notifications->add('notice', ['roles' => ['member']]);
        $sections->add('calendar', ['slug' => 'calendar', 'roles' => ['member']]);
        $sections->add('admin', ['slug' => 'admin', 'roles' => ['administrator']]);
        $widgets->add('summary', ['roles' => ['member']]);

        $model = $factory->create(
            'calendar',
            static fn(array $item): bool => in_array('member', $item['roles'] ?? [], true),
        );

        self::assertSame(['calendar'], array_keys($model->sidebar()));
        self::assertSame(['notice'], array_keys($model->notifications()));
        self::assertSame(['calendar'], array_keys($model->sections()));
        self::assertSame(['summary'], array_keys($model->widgets()));
        self::assertSame('calendar', $model->activeSection()['slug']);
        self::assertFalse($model->sectionNotFound());
    }

    public function testARegisteredButDeniedSectionIsNotFound(): void
    {
        [$factory, , , $sections] = $this->factory();
        $sections->add('admin', ['slug' => 'admin', 'roles' => ['administrator']]);

        $model = $factory->create('admin', static fn(array $item): bool => false);

        self::assertTrue($model->sectionNotFound());
        self::assertNull($model->activeSection());
    }

    private function factory(): array
    {
        $navigation = new NavigationRegistry(new NavigationTreeBuilder());
        $notifications = new NotificationRegistry();
        $sections = new SectionRegistry();
        $widgets = new WidgetRegistry();

        return [
            new DashboardViewModelFactory(
                $navigation,
                $notifications,
                $sections,
                $widgets,
                new SectionResolver($sections),
            ),
            $navigation,
            $notifications,
            $sections,
            $widgets,
        ];
    }
}
