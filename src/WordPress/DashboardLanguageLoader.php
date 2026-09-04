<?php
declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

use NeoDashboard\Core\Http\RequestTypeProvider;

final class DashboardLanguageLoader
{
    private const DOMAIN = 'neo-dashboard-core';
    private ?string $loadedLanguage = null;

    public function __construct(
        private RequestTypeProvider $requestTypeProvider,
    ) {}

    private function requestType(): string
    {
        return $this->requestTypeProvider->type();
    }

    public function registerHooks(): void
    {
        add_action('neo_dashboard_collect_languages', [$this, 'registerLanguages']);
        add_action('init', [$this, 'load'], 20);
        add_action('neo_dashboard_language_changed', [$this, 'languageChanged'], 10, 1);
    }

    public function registerLanguages(): void
    {
        do_action('neo_dashboard_register_languages', self::DOMAIN, [
            'de_DE' => 'Deutsch',
            'en_US' => 'English',
            'uk_UA' => 'Українська',
        ]);
    }

    public function load(): void
    {
        $language = (string) apply_filters(
            'neo_dashboard_get_language_for_plugin',
            'de_DE',
            self::DOMAIN,
        );
        if ($this->loadedLanguage === $language) {
            return;
        }
        if ($this->loadedLanguage !== null) {
            unload_textdomain(self::DOMAIN);
        }

        $mofile = plugin_dir_path(NEO_DASHBOARD_PLUGIN_FILE)
            . 'languages/' . self::DOMAIN . '-' . $language . '.mo';
        if (is_file($mofile)) {
            $loaded = load_textdomain(self::DOMAIN, $mofile);
            if (defined('WP_DEBUG') && WP_DEBUG && !$loaded) {
                error_log(sprintf(
                    '[Neo Dashboard Language] [%s] Failed to load: %s',
                    $this->requestType(),
                    $mofile,
                ));
            }
        } else {
            load_plugin_textdomain(
                self::DOMAIN,
                false,
                dirname(plugin_basename(NEO_DASHBOARD_PLUGIN_FILE)) . '/languages',
            );
        }
        $this->loadedLanguage = $language;
    }

    public function languageChanged(string $languageCode): void
    {
        $this->loadedLanguage = null;
        $this->load();
    }
}
