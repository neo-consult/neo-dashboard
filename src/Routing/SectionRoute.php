<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Routing;

final class SectionRoute
{
    private function __construct(private readonly string $slug) {}

    public static function fromQuery(mixed $value): ?self
    {
        if (!is_string($value)) {
            return null;
        }

        $slug = trim(rawurldecode($value), "/ \t\n\r\0\x0B");
        if ($slug === '' || preg_match('#^[a-z0-9][a-z0-9_-]*(?:/[a-z0-9][a-z0-9_-]*)*$#', $slug) !== 1) {
            return null;
        }

        return new self($slug);
    }

    public function slug(): string
    {
        return $this->slug;
    }
}
