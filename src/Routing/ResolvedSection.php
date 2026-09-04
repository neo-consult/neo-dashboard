<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Routing;

final class ResolvedSection
{
    /** @param array<string, mixed> $definition */
    public function __construct(
        private readonly string $slug,
        private readonly array $definition,
    ) {}

    public function slug(): string { return $this->slug; }
    public function label(): string { return (string) ($this->definition['label'] ?? ''); }
    public function callback(): mixed { return $this->definition['callback'] ?? null; }

    /** @return string[]|null */
    public function roles(): ?array
    {
        $roles = $this->definition['roles'] ?? null;
        return is_array($roles) ? array_values(array_map('strval', $roles)) : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array { return $this->definition; }
}
