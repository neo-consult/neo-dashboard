<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Extension\Definition;

use InvalidArgumentException;
use NeoDashboard\Core\Extension\Definition\NavigationItemDefinition;
use NeoDashboard\Core\Extension\Definition\NotificationDefinition;
use NeoDashboard\Core\Extension\Definition\SectionDefinition;
use NeoDashboard\Core\Extension\Definition\WidgetDefinition;
use PHPUnit\Framework\TestCase;

final class ExtensionDefinitionsTest extends TestCase
{
    public function testWidgetNormalizesDefaultsAndPreservesExtensionFields(): void
    {
        $definition = WidgetDefinition::fromArray([
            'id' => 'neo-calendar-widget',
            'callback' => static fn(): string => 'content',
            'size' => 'sm',
        ]);

        self::assertSame('neo-calendar-widget', $definition->id());
        self::assertSame(10, $definition->priority());
        self::assertSame(0, $definition->cacheTtl());
        self::assertSame('sm', $definition->toArray()['size']);
    }

    public function testWidgetRejectsUnsafeIds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WidgetDefinition::fromArray(['id' => '../calendar']);
    }

    public function testSectionKeepsHierarchicalSlugAndCallable(): void
    {
        $callback = static fn(): string => 'content';
        $definition = SectionDefinition::fromArray([
            'slug' => '/neo-calendar/work-time/',
            'callback' => $callback,
        ]);

        self::assertSame('neo-calendar/work-time', $definition->slug());
        self::assertSame($callback, $definition->toArray()['callback']);
    }

    public function testSectionRejectsNonCallableCallbacks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SectionDefinition::fromArray(['slug' => 'calendar', 'callback' => 'missing_callback']);
    }

    public function testNavigationNormalizesDefaultsAndPreservesParent(): void
    {
        $definition = NavigationItemDefinition::fromArray([
            'slug' => 'neo-calendar/work-time',
            'parent' => 'neo-calendar-group',
        ]);

        self::assertSame('neo-calendar/work-time', $definition->slug());
        self::assertSame(10, $definition->position());
        self::assertFalse($definition->isGroup());
        self::assertSame('neo-calendar-group', $definition->toArray()['parent']);
    }

    public function testNotificationNormalizesDefaults(): void
    {
        $definition = NotificationDefinition::fromArray([
            'id' => 'calendar-reminder',
            'message' => 'Reminder',
        ]);

        self::assertSame('calendar-reminder', $definition->id());
        self::assertSame('info', $definition->toArray()['type']);
        self::assertSame(10, $definition->toArray()['priority']);
    }

    public function testNotificationRejectsInvalidTypes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NotificationDefinition::fromArray([
            'id' => 'calendar-reminder',
            'type' => 'urgent',
        ]);
    }
}
