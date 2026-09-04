<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Language;

use NeoDashboard\Core\Language\LanguageCatalog;
use PHPUnit\Framework\TestCase;

final class LanguageCatalogTest extends TestCase
{
    public function testItContainsDefaultsAndExtendsLanguagesThroughPlugins(): void
    {
        $catalog = new LanguageCatalog();
        self::assertTrue($catalog->has('de_DE'));
        self::assertSame('Українська', $catalog->info('uk_UA')['native_name']);
        $catalog->registerPlugin('contacts', ['de_DE' => 'Deutsch', 'fr_FR' => 'Français']);
        self::assertTrue($catalog->pluginSupports('contacts', 'fr_FR'));
        self::assertSame('🇫🇷', $catalog->info('fr_FR')['flag']);
    }
}
