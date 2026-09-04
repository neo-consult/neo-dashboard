<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Extension\Definition;

use InvalidArgumentException;

final readonly class NotificationDefinition
{
    /** @param array<string, mixed> $values */
    private function __construct(private array $values) {}

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $id = trim((string) ($values['id'] ?? ''));
        if ($id === '' || preg_match('/^[a-z0-9][a-z0-9_-]*$/', $id) !== 1) {
            throw new InvalidArgumentException('Notification id must be a normalized slug.');
        }

        $type = (string) ($values['type'] ?? 'info');
        if (!in_array($type, ['info', 'success', 'warning', 'error'], true)) {
            throw new InvalidArgumentException('Notification type is invalid.');
        }

        $values['id'] = $id;
        $values['message'] = (string) ($values['message'] ?? '');
        $values['type'] = $type;
        $values['dismissible'] = (bool) ($values['dismissible'] ?? true);
        $values['priority'] = (int) ($values['priority'] ?? 10);
        $values['roles'] = $values['roles'] ?? null;
        $values['expires'] = $values['expires'] ?? null;

        return new self($values);
    }

    public function id(): string
    {
        return $this->values['id'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->values;
    }
}
