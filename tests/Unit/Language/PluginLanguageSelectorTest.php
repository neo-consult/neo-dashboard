<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Language;

use NeoDashboard\Core\Language\PluginLanguageSelector;
use PHPUnit\Framework\TestCase;

final class PluginLanguageSelectorTest extends TestCase
{
    /** @dataProvider selections */
    public function testItSelectsTheExpectedFallback(string $current, string $default, array $supported, string $expected): void
    {
        self::assertSame($expected, (new PluginLanguageSelector())->select($current, $default, $supported));
    }

    public function selections(): iterable
    {
        yield 'current' => ['uk_UA', 'de_DE', ['de_DE' => 'Deutsch', 'uk_UA' => 'Українська'], 'uk_UA'];
        yield 'default' => ['fr_FR', 'de_DE', ['de_DE' => 'Deutsch'], 'de_DE'];
        yield 'first' => ['fr_FR', 'de_DE', ['en_US' => 'English'], 'en_US'];
        yield 'no plugin languages' => ['fr_FR', 'de_DE', [], 'de_DE'];
    }
}
