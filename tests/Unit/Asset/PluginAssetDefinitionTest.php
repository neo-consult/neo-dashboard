<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Asset;

use InvalidArgumentException;
use NeoDashboard\Core\Asset\PluginAssetDefinition;
use PHPUnit\Framework\TestCase;

final class PluginAssetDefinitionTest extends TestCase
{
    public function testItExposesAValidatedPluginAssetCollection(): void
    {
        $definition = PluginAssetDefinition::fromArray('neo-calendar', [
            'css' => ['calendar' => ['src' => '/calendar.css']],
            'js' => ['calendar' => ['src' => '/calendar.js']],
        ]);

        self::assertSame('neo-calendar', $definition->pluginId());
        self::assertSame('/calendar.js', $definition->assets()[1]->source);
        self::assertSame(1, $definition->count('css'));
        self::assertSame(1, $definition->count('js'));
    }

    /** @dataProvider invalidDefinitions */
    public function testItRejectsInvalidDefinitions(string $pluginId, array $assets): void
    {
        $this->expectException(InvalidArgumentException::class);
        PluginAssetDefinition::fromArray($pluginId, $assets);
    }

    public function invalidDefinitions(): iterable
    {
        yield 'invalid plugin id' => ['Neo Calendar', []];
        yield 'non-array type' => ['neo-calendar', ['css' => 'calendar.css']];
        yield 'missing source' => ['neo-calendar', ['js' => ['calendar' => ['deps' => []]]]];
    }
}
