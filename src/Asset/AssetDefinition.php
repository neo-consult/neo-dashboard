<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

use InvalidArgumentException;

final readonly class AssetDefinition
{
    /**
     * @param list<string> $dependencies
     * @param list<string> $contexts
     * @param array<string, mixed>|null $localize
     */
    private function __construct(
        public string $handle,
        public string $type,
        public string $source,
        public array $dependencies,
        public string $version,
        public array $contexts,
        public bool $inFooter,
        public ?array $localize,
    ) {}

    /** @param array<string, mixed> $config */
    public static function fromArray(string $handle, string $type, array $config): self
    {
        if ($handle === '' || !in_array($type, ['css', 'js'], true)) {
            throw new InvalidArgumentException('Asset handle and type are invalid.');
        }
        $source = $config['src'] ?? null;
        if (!is_string($source) || $source === '') {
            throw new InvalidArgumentException("Asset {$handle} requires a source URL.");
        }

        return new self(
            $handle,
            $type,
            $source,
            self::strings($config['deps'] ?? []),
            is_string($config['version'] ?? null) ? $config['version'] : '1.0.0',
            self::contexts($config['contexts'] ?? ['*']),
            (bool) ($config['in_footer'] ?? true),
            is_array($config['localize'] ?? null) ? $config['localize'] : null,
        );
    }

    /** @return list<string> */
    private static function strings(mixed $values): array
    {
        return is_array($values)
            ? array_values(array_filter($values, 'is_string'))
            : [];
    }

    /** @return list<string> */
    private static function contexts(mixed $values): array
    {
        $contexts = self::strings($values);
        return $contexts !== [] ? $contexts : ['*'];
    }
}
