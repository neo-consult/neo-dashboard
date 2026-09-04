<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

final class WordPressLanguageChangeNotifier implements LanguageChangeNotifier
{
    public function notify(string $newLanguage, string $oldLanguage): void
    {
        do_action('neo_dashboard_language_changed', $newLanguage, $oldLanguage);
    }
}
