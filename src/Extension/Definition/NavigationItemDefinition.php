<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Extension\Definition;

use InvalidArgumentException;

final class NavigationItemDefinition
{
    /** @param array<string, mixed> $values */
    private function __construct(private readonly array $values)
    {
    }

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $slug = trim((string) ($values['slug'] ?? ''));
        if ($slug === '' || preg_match('#^[a-z0-9][a-z0-9_-]*(?:/[a-z0-9][a-z0-9_-]*)*$#', $slug) !== 1) {
            throw new InvalidArgumentException('Navigation slug must contain normalized path segments.');
        }

        if (isset($values['label_callback']) && !is_callable($values['label_callback'])) {
            throw new InvalidArgumentException('Navigation label_callback must be callable.');
        }

        if (isset($values['tooltip']) && !is_string($values['tooltip']) && !is_callable($values['tooltip'])) {
            throw new InvalidArgumentException('Navigation tooltip must be a string or callable.');
        }

        if (isset($values['parent'])) {
            $parent = trim((string) $values['parent']);
            if ($parent === '' || preg_match('#^[a-z0-9][a-z0-9_-]*$#', $parent) !== 1) {
                throw new InvalidArgumentException('Navigation parent must be a normalized slug.');
            }
            $values['parent'] = $parent;
        }

        $values['slug'] = $slug;
        $values['position'] = (int) ($values['position'] ?? 10);
        $values['is_group'] = (bool) ($values['is_group'] ?? false);

        return new self($values);
    }

    public function slug(): string
    {
        return $this->values['slug'];
    }

    public function position(): int
    {
        return $this->values['position'];
    }

    public function isGroup(): bool
    {
        return $this->values['is_group'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->values;
    }
}
