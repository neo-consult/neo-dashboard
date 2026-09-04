<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Extension\Definition;

use InvalidArgumentException;

final class SectionDefinition
{
    /** @param array<string, mixed> $values */
    private function __construct(private readonly array $values)
    {
    }

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $slug = trim((string) ($values['slug'] ?? ''), '/ ');
        if ($slug === '' || preg_match('#^[a-z0-9][a-z0-9_-]*(?:/[a-z0-9][a-z0-9_-]*)*$#', $slug) !== 1) {
            throw new InvalidArgumentException('Section slug must be a normalized relative path.');
        }

        foreach (['label_callback', 'callback'] as $callbackKey) {
            if (isset($values[$callbackKey]) && !is_callable($values[$callbackKey])) {
                throw new InvalidArgumentException("Section {$callbackKey} must be callable.");
            }
        }

        $values['slug'] = $slug;
        return new self($values);
    }

    public function slug(): string
    {
        return $this->values['slug'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->values;
    }
}
