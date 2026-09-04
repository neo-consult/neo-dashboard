<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

interface LanguageChangeNotifier
{
    public function notify(string $newLanguage, string $oldLanguage): void;
}
