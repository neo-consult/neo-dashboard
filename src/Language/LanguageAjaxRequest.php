<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

interface LanguageAjaxRequest
{
    public function hasValidNonce(): bool;

    public function isAuthenticated(): bool;

    public function languageCode(): string;
}
