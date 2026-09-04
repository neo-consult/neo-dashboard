<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

final readonly class LanguageAjaxController
{
    public function __construct(
        private LanguageCatalog $catalog,
        private LanguagePreferenceService $preferences,
        private LanguageAjaxRequest $request,
        private LanguageAjaxResponder $responder,
    ) {}

    public function handle(): void
    {
        if (!$this->request->hasValidNonce()) {
            $this->responder->error(['message' => 'Ungültige Anfrage']);
            return;
        }
        if (!$this->request->isAuthenticated()) {
            $this->responder->error(['message' => 'Nicht angemeldet']);
            return;
        }

        $languageCode = $this->request->languageCode();
        if ($languageCode === '' || !$this->catalog->has($languageCode)) {
            $this->responder->error(['message' => 'Sprache nicht verfügbar']);
            return;
        }

        $this->responder->success([
            'language' => $this->preferences->set($languageCode),
            'message' => 'Sprache geändert',
        ]);
    }
}
