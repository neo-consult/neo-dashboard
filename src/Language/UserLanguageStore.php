<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Language;

interface UserLanguageStore
{
    public function load(): ?string;

    public function save(string $languageCode): void;

    public function identity(): string;
}
