<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Language;

use NeoDashboard\Core\Language\LanguageAjaxController;
use NeoDashboard\Core\Language\LanguageAjaxRequest;
use NeoDashboard\Core\Language\LanguageAjaxResponder;
use NeoDashboard\Core\Language\LanguageCatalog;
use NeoDashboard\Core\Language\LanguageChangeNotifier;
use NeoDashboard\Core\Language\LanguagePreferenceService;
use NeoDashboard\Core\Language\PluginLanguageSelector;
use NeoDashboard\Core\Language\UserLanguageStore;
use PHPUnit\Framework\TestCase;

final class LanguageAjaxControllerTest extends TestCase
{
    public function testItRejectsAnInvalidNonceBeforeAuthentication(): void
    {
        $request = new AjaxRequest(false, false, 'en_US');
        $responder = new AjaxResponder();

        $this->controller($request, $responder)->handle();

        self::assertSame([['error', ['message' => 'Ungültige Anfrage']]], $responder->responses);
    }

    public function testItRejectsAnonymousUsers(): void
    {
        $responder = new AjaxResponder();

        $this->controller(new AjaxRequest(true, false, 'en_US'), $responder)->handle();

        self::assertSame([['error', ['message' => 'Nicht angemeldet']]], $responder->responses);
    }

    /** @dataProvider invalidLanguages */
    public function testItRejectsMissingOrUnavailableLanguages(string $languageCode): void
    {
        $responder = new AjaxResponder();

        $this->controller(new AjaxRequest(true, true, $languageCode), $responder)->handle();

        self::assertSame([['error', ['message' => 'Sprache nicht verfügbar']]], $responder->responses);
    }

    public function invalidLanguages(): iterable
    {
        yield 'missing' => [''];
        yield 'unknown' => ['xx_XX'];
    }

    public function testItPersistsAValidLanguageAndReturnsSuccess(): void
    {
        $responder = new AjaxResponder();
        $store = new AjaxLanguageStore();

        $this->controller(new AjaxRequest(true, true, 'en_US'), $responder, $store)->handle();

        self::assertSame('en_US', $store->language);
        self::assertSame([[
            'success',
            ['language' => 'en_US', 'message' => 'Sprache geändert'],
        ]], $responder->responses);
    }

    private function controller(
        AjaxRequest $request,
        AjaxResponder $responder,
        ?AjaxLanguageStore $store = null,
    ): LanguageAjaxController {
        $catalog = new LanguageCatalog();
        $preferences = new LanguagePreferenceService(
            $catalog,
            new PluginLanguageSelector(),
            $store ?? new AjaxLanguageStore(),
            new AjaxLanguageNotifier(),
        );

        return new LanguageAjaxController($catalog, $preferences, $request, $responder);
    }
}

final readonly class AjaxRequest implements LanguageAjaxRequest
{
    public function __construct(
        private bool $validNonce,
        private bool $authenticated,
        private string $language,
    ) {}

    public function hasValidNonce(): bool { return $this->validNonce; }
    public function isAuthenticated(): bool { return $this->authenticated; }
    public function languageCode(): string { return $this->language; }
}

final class AjaxResponder implements LanguageAjaxResponder
{
    /** @var list<array{string, array<string, mixed>}> */
    public array $responses = [];

    public function success(array $data): void { $this->responses[] = ['success', $data]; }
    public function error(array $data): void { $this->responses[] = ['error', $data]; }
}

final class AjaxLanguageStore implements UserLanguageStore
{
    public ?string $language = null;

    public function load(): ?string { return $this->language; }
    public function save(string $languageCode): void { $this->language = $languageCode; }
    public function identity(): string { return 'ajax-user'; }
}

final class AjaxLanguageNotifier implements LanguageChangeNotifier
{
    public function notify(string $newLanguage, string $oldLanguage): void {}
}
