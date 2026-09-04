<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Language\LanguageCatalog;
use NeoDashboard\Core\Language\LanguageAjaxController;
use NeoDashboard\Core\Language\LanguagePreferenceService;

/** WordPress hook facade for language registration and selection. */
class LanguageManager
{
    public function __construct(
        private LanguageCatalog $catalog,
        private LanguagePreferenceService $preferences,
        private LanguageAjaxController $ajaxController,
    ) {}

    public function registerHooks(): void
    {
        add_action('neo_dashboard_register_languages', [$this, 'registerPluginLanguagesInstance'], 10, 2);
        add_filter('neo_dashboard_get_language_for_plugin', [$this, 'getLanguageForPlugin'], 10, 2);
        add_action('wp_ajax_neo_dashboard_set_language', [$this->ajaxController, 'handle']);
        add_action('plugins_loaded', [$this, 'collectPluginLanguages'], 20);
    }

    public function collectPluginLanguages(): void
    {
        do_action('neo_dashboard_collect_languages');
    }

    /** @param array<string, string> $languages */
    public function registerPluginLanguagesInstance(string $pluginId, array $languages): void
    {
        $this->catalog->registerPlugin($pluginId, $languages);
        $this->preferences->clearPluginCache();
    }

    public function getLanguageForPlugin(string $_defaultLanguage, string $pluginId): string
    {
        return $this->preferences->forPlugin($pluginId);
    }

}
