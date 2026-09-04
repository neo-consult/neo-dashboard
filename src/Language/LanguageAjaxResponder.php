<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

interface LanguageAjaxResponder
{
    /** @param array<string, mixed> $data */
    public function success(array $data): void;

    /** @param array<string, mixed> $data */
    public function error(array $data): void;
}
