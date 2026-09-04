<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Language;

use NeoDashboard\Core\Language\LanguageCatalog;
use NeoDashboard\Core\Language\LanguageChangeNotifier;
use NeoDashboard\Core\Language\LanguagePreferenceService;
use NeoDashboard\Core\Language\PluginLanguageSelector;
use NeoDashboard\Core\Language\UserLanguageStore;
use PHPUnit\Framework\TestCase;

final class LanguagePreferenceServiceTest extends TestCase
{
    public function testItLoadsAndValidatesTheSavedPreference(): void
    {
        $store = new PreferenceStore('en_US');
        $service = $this->service(new LanguageCatalog(), $store, new PreferenceNotifier());

        self::assertSame('en_US', $service->current());

        $invalid = $this->service(new LanguageCatalog(), new PreferenceStore('xx_XX'), new PreferenceNotifier());
        self::assertSame('de_DE', $invalid->current());
    }

    public function testItPersistsAndNotifiesOnlyActualChanges(): void
    {
        $store = new PreferenceStore();
        $notifier = new PreferenceNotifier();
        $service = $this->service(new LanguageCatalog(), $store, $notifier);

        self::assertSame('en_US', $service->set('en_US'));
        self::assertSame('en_US', $store->language);
        self::assertSame([['en_US', 'de_DE']], $notifier->changes);

        $service->set('en_US');
        self::assertCount(1, $notifier->changes);
    }

    public function testItSelectsOnlyLanguagesSupportedByThePlugin(): void
    {
        $catalog = new LanguageCatalog();
        $catalog->registerPlugin('example', ['de_DE' => 'Deutsch']);
        $service = $this->service($catalog, new PreferenceStore('en_US'), new PreferenceNotifier());

        self::assertSame('de_DE', $service->forPlugin('example'));
    }

    private function service(
        LanguageCatalog $catalog,
        PreferenceStore $store,
        PreferenceNotifier $notifier,
    ): LanguagePreferenceService {
        return new LanguagePreferenceService(
            $catalog,
            new PluginLanguageSelector(),
            $store,
            $notifier,
        );
    }
}

final class PreferenceStore implements UserLanguageStore
{
    public function __construct(public ?string $language = null) {}

    public function load(): ?string { return $this->language; }
    public function save(string $languageCode): void { $this->language = $languageCode; }
    public function identity(): string { return 'user-7'; }
}

final class PreferenceNotifier implements LanguageChangeNotifier
{
    /** @var list<array{string, string}> */
    public array $changes = [];

    public function notify(string $newLanguage, string $oldLanguage): void
    {
        $this->changes[] = [$newLanguage, $oldLanguage];
    }
}
