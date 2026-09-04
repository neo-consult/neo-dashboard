<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Asset;

use NeoDashboard\Core\Asset\AssetCatalog;
use NeoDashboard\Core\Asset\PluginAssetDefinition;
use PHPUnit\Framework\TestCase;

final class AssetCatalogTest extends TestCase
{
    public function testItSelectsContextAndGlobalAssetsByType(): void
    {
        $catalog = new AssetCatalog();
        $catalog->registerPlugin(PluginAssetDefinition::fromArray('calendar', [
            'css' => [
                'global' => ['src' => 'global.css', 'contexts' => ['*']],
                'work-time' => ['src' => 'work.css', 'contexts' => ['neo-calendar/work-time']],
            ],
            'js' => ['calendar' => ['src' => 'calendar.js']],
        ]));
        self::assertSame(['work-time', 'global'], array_keys($catalog->forContext('neo-calendar/work-time', 'css')));
        self::assertSame(['global'], array_keys($catalog->forContext('another', 'css')));
        self::assertSame(['calendar'], array_keys($catalog->forContext('another', 'js')));
    }

    public function testReregisteringAPluginRemovesObsoleteEntries(): void
    {
        $catalog = new AssetCatalog();
        $catalog->registerPlugin(PluginAssetDefinition::fromArray('calendar', ['css' => ['old' => ['src' => 'old.css', 'contexts' => ['old-page']]]]));
        $catalog->registerPlugin(PluginAssetDefinition::fromArray('calendar', ['css' => ['new' => ['src' => 'new.css', 'contexts' => ['new-page']]]]));
        self::assertSame([], $catalog->forContext('old-page', 'css'));
        self::assertSame(['new'], array_keys($catalog->forContext('new-page', 'css')));
    }

}
