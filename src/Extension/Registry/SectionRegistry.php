<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Extension\Registry;

final class SectionRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $sections = [];

    /** @var array<string, array<string, mixed>> */
    private array $resolved = [];

    /** @param array<string, mixed> $definition */
    public function add(string $slug, array $definition): bool
    {
        if (isset($this->sections[$slug])) {
            return false;
        }

        $this->sections[$slug] = $definition;
        unset($this->resolved[$slug]);
        return true;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return $this->sections;
    }

    /** @return array<string, mixed>|null */
    public function get(string $slug): ?array
    {
        if (isset($this->resolved[$slug])) {
            return $this->resolved[$slug];
        }
        if (!isset($this->sections[$slug])) {
            return null;
        }

        $section = $this->sections[$slug];
        $section['label'] = isset($section['label_callback']) && is_callable($section['label_callback'])
            ? (string) call_user_func($section['label_callback'])
            : '';

        $cached = $section;
        unset($cached['label_callback']);
        $this->resolved[$slug] = $cached;
        return $cached;
    }
}
