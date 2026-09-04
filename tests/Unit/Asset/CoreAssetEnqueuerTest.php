<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Asset;

use NeoDashboard\Core\Asset\CoreAssetEnqueuer;
use NeoDashboard\Core\Asset\CoreAssetLocalizationData;
use NeoDashboard\Core\Asset\CoreAssetManifest;
use NeoDashboard\Core\Asset\CoreAssetPlatform;
use NeoDashboard\Core\Asset\DashboardClientEnvironment;
use NeoDashboard\Core\Http\DashboardRequest;
use NeoDashboard\Core\Http\DashboardRequestEnvironment;
use NeoDashboard\Core\Language\LanguageCatalog;
use NeoDashboard\Core\Language\LanguageChangeNotifier;
use NeoDashboard\Core\Language\LanguagePreferenceService;
use NeoDashboard\Core\Language\PluginLanguageSelector;
use NeoDashboard\Core\Language\UserLanguageStore;
use NeoDashboard\Core\Manager\ContextResolver;
use PHPUnit\Framework\TestCase;

final class CoreAssetEnqueuerTest extends TestCase
{
    public function testItBuildsLocalizationDataWithoutWordPress(): void
    {
        $data = $this->localization()->build();

        self::assertSame('/wp-json/neo-dashboard/v1', $data['restUrl']);
        self::assertSame('/wp-admin/admin-ajax.php', $data['ajaxurl']);
        self::assertSame('rest-nonce', $data['nonce']);
        self::assertSame('widget-nonce', $data['widgetNonce']);
        self::assertSame('calendar/month', $data['context']);
        self::assertSame('calendar/month', $data['section']);
        self::assertSame('de_DE', $data['currentLanguage']);
        self::assertSame('de_DE', $data['defaultLanguage']);
        self::assertSame(['loading' => 'Lädt…'], $data['strings']);
        self::assertArrayHasKey('en_US', $data['languages']);
    }

    public function testItEnqueuesManifestAndLocalizesTheCoreScriptOnlyOnce(): void
    {
        $platform = new RecordingCoreAssetPlatform();
        $enqueuer = new CoreAssetEnqueuer(
            $platform,
            new CoreAssetManifest(),
            $this->localization(),
            '9.9.9',
        );

        $enqueuer->enqueue();
        $enqueuer->enqueue();

        self::assertCount(12, $platform->styles);
        self::assertCount(18, $platform->scripts);
        self::assertSame('9.9.9', $platform->styles[2]['version']);
        self::assertSame('9.9.9', $platform->scripts[1]['version']);
        self::assertCount(1, $platform->localizations);
        self::assertSame('NeoDash', $platform->localizations[0]['object']);
    }

    private function localization(): CoreAssetLocalizationData
    {
        $catalog = new LanguageCatalog();
        $preferences = new LanguagePreferenceService(
            $catalog,
            new PluginLanguageSelector(),
            new FixedUserLanguageStore(),
            new SilentLanguageChangeNotifier(),
        );

        return new CoreAssetLocalizationData(
            new ContextResolver(new AssetRequestEnvironment()),
            $catalog,
            $preferences,
            new AssetDashboardRequest(),
            new FixedDashboardClientEnvironment(),
        );
    }
}

final class RecordingCoreAssetPlatform implements CoreAssetPlatform
{
    /** @var list<array{handle: string, source: string, dependencies: list<string>, version: string}> */
    public array $styles = [];
    /** @var list<array{handle: string, source: string, dependencies: list<string>, version: string}> */
    public array $scripts = [];
    /** @var list<array{handle: string, object: string, data: array<string, mixed>}> */
    public array $localizations = [];

    public function baseUrl(): string { return '/plugins/neo-dashboard/'; }
    public function exists(string $relativePath): bool { return true; }

    public function enqueueStyle(string $handle, string $source, array $dependencies, string $version): void
    {
        $this->styles[] = compact('handle', 'source', 'dependencies', 'version');
    }

    public function enqueueScript(string $handle, string $source, array $dependencies, string $version): void
    {
        $this->scripts[] = compact('handle', 'source', 'dependencies', 'version');
    }

    public function localize(string $handle, string $objectName, array $data): void
    {
        $this->localizations[] = ['handle' => $handle, 'object' => $objectName, 'data' => $data];
    }
}

final class FixedDashboardClientEnvironment implements DashboardClientEnvironment
{
    public function restUrl(): string { return '/wp-json/neo-dashboard/v1'; }
    public function ajaxUrl(): string { return '/wp-admin/admin-ajax.php'; }
    public function restNonce(): string { return 'rest-nonce'; }
    public function widgetNonce(): string { return 'widget-nonce'; }
    public function strings(): array { return ['loading' => 'Lädt…']; }
}

final class AssetRequestEnvironment implements DashboardRequestEnvironment
{
    public function isAdmin(): bool { return false; }
    public function adminPage(): string { return ''; }
    public function requestUri(): string { return '/neo-dashboard/calendar/month'; }
    public function queryVar(string $name): string { return ''; }
}

final class AssetDashboardRequest implements DashboardRequest
{
    public function user(): \WP_User { throw new \LogicException('Not used'); }
    public function section(): string { return 'calendar/month'; }
}

final class FixedUserLanguageStore implements UserLanguageStore
{
    public function load(): ?string { return 'de_DE'; }
    public function save(string $languageCode): void {}
    public function identity(): string { return 'test'; }
}

final class SilentLanguageChangeNotifier implements LanguageChangeNotifier
{
    public function notify(string $newLanguage, string $oldLanguage): void {}
}
