<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Routing;

use NeoDashboard\Core\Extension\Registry\SectionRegistry;

final class SectionResolver
{
    public function __construct(private readonly SectionRegistry $registry) {}

    /** @param array<string, array<string, mixed>>|null $allowedSections */
    public function resolve(string $query, ?array $allowedSections = null): ?ResolvedSection
    {
        $route = SectionRoute::fromQuery($query);
        if ($route === null) {
            return null;
        }

        $slug = $route->slug();
        if ($allowedSections !== null && !array_key_exists($slug, $allowedSections)) {
            return null;
        }

        $definition = $this->registry->get($slug);
        return $definition === null ? null : new ResolvedSection($slug, $definition);
    }

    /** @param array<string, array<string, mixed>>|null $allowedSections */
    public function resolveRequest(string $query, ?array $allowedSections = null): SectionResolution
    {
        if (trim($query) === '') {
            return SectionResolution::root();
        }

        $section = $this->resolve($query, $allowedSections);
        return $section === null
            ? SectionResolution::notFound()
            : SectionResolution::found($section);
    }
}
