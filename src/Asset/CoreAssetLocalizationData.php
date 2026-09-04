<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

use NeoDashboard\Core\Http\DashboardRequest;
use NeoDashboard\Core\Language\LanguageCatalog;
use NeoDashboard\Core\Language\LanguagePreferenceService;
use NeoDashboard\Core\Manager\ContextResolver;

final readonly class CoreAssetLocalizationData
{
    public function __construct(
        private ContextResolver $context,
        private LanguageCatalog $languages,
        private LanguagePreferenceService $languagePreferences,
        private DashboardRequest $request,
        private DashboardClientEnvironment $environment,
    ) {}

    /** @return array<string, mixed> */
    public function build(): array
    {
        return [
            'restUrl' => $this->environment->restUrl(),
            'ajaxurl' => $this->environment->ajaxUrl(),
            'nonce' => $this->environment->restNonce(),
            'widgetNonce' => $this->environment->widgetNonce(),
            'context' => $this->context->current(),
            'section' => $this->request->section(),
            'languages' => $this->languages->all(),
            'currentLanguage' => $this->languagePreferences->current(),
            'defaultLanguage' => $this->languagePreferences->defaultLanguage(),
            'strings' => $this->environment->strings(),
        ];
    }
}
