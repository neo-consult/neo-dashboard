<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Presentation;

final class UserMenuFormatter
{
    public function initials(string $displayName, string $login): string
    {
        $name = trim($displayName);
        if ($name === '') {
            $name = trim($login);
        }

        $parts = preg_split('/\s+/u', $name) ?: [];
        $initials = count($parts) >= 2
            ? $this->firstCharacter($parts[0]) . $this->firstCharacter($parts[array_key_last($parts)])
            : $this->firstCharacters($name, 2);

        return $this->upper($initials);
    }

    public function roleLabel(string $role): ?string
    {
        return match ($role) {
            'administrator' => 'Administrator',
            'neo_editor' => 'Editor',
            'neo_mitarbeiter' => 'Mitarbeiter',
            default => null,
        };
    }

    private function firstCharacter(string $value): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, 1) : substr($value, 0, 1);
    }

    private function firstCharacters(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
    }
}
