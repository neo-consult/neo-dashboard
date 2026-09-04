<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Manager;

use NeoDashboard\Core\Language\LanguageCatalog;
use NeoDashboard\Core\Language\LanguageAjaxController;
use NeoDashboard\Core\Language\LanguageAjaxRequest;
use NeoDashboard\Core\Language\LanguageAjaxResponder;
use NeoDashboard\Core\Language\LanguageChangeNotifier;
use NeoDashboard\Core\Language\LanguagePreferenceService;
use NeoDashboard\Core\Language\PluginLanguageSelector;
use NeoDashboard\Core\Language\UserLanguageStore;
use NeoDashboard\Core\Manager\LanguageManager;
use PHPUnit\Framework\TestCase;

final class LanguageManagerTest extends TestCase
{
    public function testPluginRegistrationUpdatesTheInjectedCatalog(): void
    {
        $catalog = new LanguageCatalog();
        $manager = $this->manager($catalog);

        $manager->registerPluginLanguagesInstance('example', [
            'de_DE' => 'Deutsch',
            'en_US' => 'English',
        ]);

        self::assertTrue($catalog->pluginSupports('example', 'en_US'));
        self::assertSame('English', $catalog->info('en_US')['name']);
    }

    public function testSeparateManagersDoNotSharePluginLanguageState(): void
    {
        $firstCatalog = new LanguageCatalog();
        $secondCatalog = new LanguageCatalog();
        $first = $this->manager($firstCatalog);
        $this->manager($secondCatalog);

        $first->registerPluginLanguagesInstance('example', ['en_US' => 'English']);

        self::assertTrue($firstCatalog->pluginSupports('example', 'en_US'));
        self::assertFalse($secondCatalog->pluginSupports('example', 'en_US'));
    }

    private function manager(LanguageCatalog $catalog): LanguageManager
    {
        $preferences = new LanguagePreferenceService(
            $catalog,
            new PluginLanguageSelector(),
            new InMemoryUserLanguageStore(),
            new RecordingLanguageChangeNotifier(),
        );

        return new LanguageManager(
            $catalog,
            $preferences,
            new LanguageAjaxController(
                $catalog,
                $preferences,
                new ManagerLanguageAjaxRequest(),
                new ManagerLanguageAjaxResponder(),
            ),
        );
    }
}

final class ManagerLanguageAjaxRequest implements LanguageAjaxRequest
{
    public function hasValidNonce(): bool { return true; }
    public function isAuthenticated(): bool { return true; }
    public function languageCode(): string { return 'de_DE'; }
}

final class ManagerLanguageAjaxResponder implements LanguageAjaxResponder
{
    public function success(array $data): void {}
    public function error(array $data): void {}
}

final class InMemoryUserLanguageStore implements UserLanguageStore
{
    public ?string $language = null;

    public function load(): ?string { return $this->language; }
    public function save(string $languageCode): void { $this->language = $languageCode; }
    public function identity(): string { return 'test-user'; }
}

final class RecordingLanguageChangeNotifier implements LanguageChangeNotifier
{
    /** @var list<array{string, string}> */
    public array $changes = [];

    public function notify(string $newLanguage, string $oldLanguage): void
    {
        $this->changes[] = [$newLanguage, $oldLanguage];
    }
}
