<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Extension\Definition;

use InvalidArgumentException;

final class WidgetDefinition
{
    /** @param array<string, mixed> $values */
    private function __construct(private readonly array $values)
    {
    }

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $id = trim((string) ($values['id'] ?? ''));
        if ($id === '' || preg_match('/^[a-z0-9][a-z0-9_-]*$/', $id) !== 1) {
            throw new InvalidArgumentException('Widget id must contain only lowercase letters, numbers, hyphens and underscores.');
        }

        if (isset($values['callback']) && !is_callable($values['callback'])) {
            throw new InvalidArgumentException('Widget callback must be callable.');
        }

        $priority = (int) ($values['priority'] ?? 10);
        $cacheTtl = (int) ($values['cache_ttl'] ?? 0);
        if ($cacheTtl < 0) {
            throw new InvalidArgumentException('Widget cache_ttl cannot be negative.');
        }

        $values['id'] = $id;
        $values['priority'] = $priority;
        $values['cache_ttl'] = $cacheTtl;

        return new self($values);
    }

    public function id(): string
    {
        return $this->values['id'];
    }

    public function priority(): int
    {
        return $this->values['priority'];
    }

    public function cacheTtl(): int
    {
        return $this->values['cache_ttl'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->values;
    }
}
