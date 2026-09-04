<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Language;

final class LanguageCatalog
{
    /** @var array<string, array{code: string, name: string, native_name: string, flag: string, default: bool}> */
    private array $languages;
    /** @var array<string, array<string, string>> */
    private array $plugins = [];

    /** @param array<string, array<string, string>> $pluginLanguages */
    public function __construct(array $pluginLanguages = [])
    {
        $this->languages = [
            'de_DE' => $this->definition('de_DE', 'Deutsch', '🇩🇪', true),
            'en_US' => $this->definition('en_US', 'English', '🇺🇸'),
            'uk_UA' => $this->definition('uk_UA', 'Українська', '🇺🇦'),
        ];
        foreach ($pluginLanguages as $pluginId => $languages) {
            $this->registerPlugin($pluginId, $languages);
        }
    }

    /** @param array<string, string> $languages */
    public function registerPlugin(string $pluginId, array $languages): void
    {
        $this->plugins[$pluginId] = $languages;
        foreach ($languages as $code => $name) {
            if (!isset($this->languages[$code])) {
                $this->languages[$code] = $this->definition($code, $name, $this->flag($code));
            }
        }
    }

    public function has(string $code): bool { return isset($this->languages[$code]); }
    public function info(string $code): ?array { return $this->languages[$code] ?? null; }
    public function all(): array { return $this->languages; }
    public function pluginSupports(string $pluginId, string $code): bool { return isset($this->plugins[$pluginId][$code]); }
    public function pluginLanguages(string $pluginId): array { return $this->plugins[$pluginId] ?? []; }

    private function definition(string $code, string $name, string $flag, bool $default = false): array
    {
        return ['code' => $code, 'name' => $name, 'native_name' => $name, 'flag' => $flag, 'default' => $default];
    }

    private function flag(string $code): string
    {
        return [
            'en_GB' => '🇬🇧', 'fr_FR' => '🇫🇷', 'es_ES' => '🇪🇸', 'it_IT' => '🇮🇹',
            'pt_PT' => '🇵🇹', 'pt_BR' => '🇧🇷', 'nl_NL' => '🇳🇱', 'pl_PL' => '🇵🇱',
            'ru_RU' => '🇷🇺', 'zh_CN' => '🇨🇳', 'ja_JP' => '🇯🇵', 'ko_KR' => '🇰🇷',
            'ar' => '🇸🇦', 'tr_TR' => '🇹🇷',
        ][$code] ?? '🌐';
    }
}
