<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

final class LanguagePreferenceService
{
    /** @var array<string, string> */
    private array $pluginLanguageCache = [];
    private ?string $currentLanguage = null;

    public function __construct(
        private readonly LanguageCatalog $catalog,
        private readonly PluginLanguageSelector $selector,
        private readonly UserLanguageStore $userLanguages,
        private readonly LanguageChangeNotifier $notifier,
        private readonly string $defaultLanguage = 'de_DE',
    ) {}

    public function current(): string
    {
        if ($this->currentLanguage === null) {
            $saved = $this->userLanguages->load();
            $this->currentLanguage = $saved !== null && $this->catalog->has($saved)
                ? $saved
                : $this->defaultLanguage;
        }

        return $this->currentLanguage;
    }

    public function set(string $languageCode): string
    {
        $languageCode = $this->catalog->has($languageCode)
            ? $languageCode
            : $this->defaultLanguage;
        $oldLanguage = $this->current();
        $this->currentLanguage = $languageCode;
        $this->userLanguages->save($languageCode);

        if ($oldLanguage !== $languageCode) {
            $this->pluginLanguageCache = [];
            $this->notifier->notify($languageCode, $oldLanguage);
        }

        return $languageCode;
    }

    public function forPlugin(string $pluginId): string
    {
        $cacheKey = $pluginId . ':' . $this->userLanguages->identity();
        return $this->pluginLanguageCache[$cacheKey] ??= $this->selector->select(
            $this->current(),
            $this->defaultLanguage,
            $this->catalog->pluginLanguages($pluginId),
        );
    }

    public function defaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    public function clearPluginCache(): void
    {
        $this->pluginLanguageCache = [];
    }
}
