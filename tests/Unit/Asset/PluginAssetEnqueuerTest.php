<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Asset;

use NeoDashboard\Core\Asset\AssetCatalog;
use NeoDashboard\Core\Asset\PluginAssetDefinition;
use NeoDashboard\Core\Asset\PluginAssetEnqueuer;
use NeoDashboard\Core\Asset\PluginAssetPlatform;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\PerformanceTimer;
use PHPUnit\Framework\TestCase;

final class PluginAssetEnqueuerTest extends TestCase
{
    public function testItEnqueuesSelectedAssetsThroughThePlatform(): void
    {
        $platform = new RecordingPluginAssetPlatform();
        $enqueuer = $this->enqueuer($platform, [
            'css' => [
                'calendar-style' => [
                    'src' => '/calendar.css',
                    'deps' => ['dashboard'],
                    'version' => '2.0.0',
                    'contexts' => ['calendar'],
                ],
            ],
            'js' => [
                'calendar-script' => [
                    'src' => '/calendar.js',
                    'deps' => ['jquery'],
                    'version' => '2.1.0',
                    'contexts' => ['calendar'],
                    'in_footer' => false,
                ],
            ],
        ]);

        $enqueuer->enqueue('calendar', 'css');
        $enqueuer->enqueue('calendar', 'js');

        self::assertSame([['calendar-style', '/calendar.css', ['dashboard'], '2.0.0']], $platform->styles);
        self::assertSame([['calendar-script', '/calendar.js', ['jquery'], '2.1.0', false]], $platform->scripts);
    }

    public function testItGenericallyPreservesExistingLocalizationData(): void
    {
        $platform = new RecordingPluginAssetPlatform([
            'calendar-script:calendarConfig' => [
                'page' => 'month',
                'strings' => ['existing'],
            ],
        ]);
        $enqueuer = $this->enqueuer($platform, [
            'js' => [
                'calendar-script' => [
                    'src' => '/calendar.js',
                    'localize' => [
                        'object_name' => 'calendarConfig',
                        'data' => static fn(): array => [
                            'nonce' => 'fresh',
                            'strings' => ['new'],
                        ],
                    ],
                ],
            ],
        ]);

        $enqueuer->enqueue('anywhere', 'js');

        self::assertSame([[
            'calendar-script',
            'calendarConfig',
            ['nonce' => 'fresh', 'strings' => ['existing'], 'page' => 'month'],
        ]], $platform->localizations);
    }

    /** @param array<string, mixed> $assets */
    private function enqueuer(RecordingPluginAssetPlatform $platform, array $assets): PluginAssetEnqueuer
    {
        $catalog = new AssetCatalog();
        $catalog->registerPlugin(PluginAssetDefinition::fromArray('calendar', $assets));

        return new PluginAssetEnqueuer(
            $catalog,
            new PerformanceTimer(new Logger()),
            $platform,
        );
    }
}

final class RecordingPluginAssetPlatform implements PluginAssetPlatform
{
    /** @var list<array{string, string, list<string>, string}> */
    public array $styles = [];
    /** @var list<array{string, string, list<string>, string, bool}> */
    public array $scripts = [];
    /** @var list<array{string, string, array<string, mixed>}> */
    public array $localizations = [];

    /** @param array<string, array<string, mixed>> $existing */
    public function __construct(private array $existing = []) {}

    public function enqueueStyle(string $handle, string $source, array $dependencies, string $version): void
    {
        $this->styles[] = [$handle, $source, $dependencies, $version];
    }

    public function enqueueScript(
        string $handle,
        string $source,
        array $dependencies,
        string $version,
        bool $inFooter,
    ): void {
        $this->scripts[] = [$handle, $source, $dependencies, $version, $inFooter];
    }

    public function existingLocalization(string $handle, string $objectName): array
    {
        return $this->existing[$handle . ':' . $objectName] ?? [];
    }

    public function localize(string $handle, string $objectName, array $data): void
    {
        $this->localizations[] = [$handle, $objectName, $data];
    }
}
