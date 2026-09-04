<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Routing;

final class SectionResolution
{
    private function __construct(
        private readonly SectionResolutionStatus $status,
        private readonly ?ResolvedSection $section = null,
    ) {}

    public static function root(): self
    {
        return new self(SectionResolutionStatus::ROOT);
    }

    public static function found(ResolvedSection $section): self
    {
        return new self(SectionResolutionStatus::FOUND, $section);
    }

    public static function notFound(): self
    {
        return new self(SectionResolutionStatus::NOT_FOUND);
    }

    public function status(): SectionResolutionStatus { return $this->status; }
    public function section(): ?ResolvedSection { return $this->section; }
    public function isNotFound(): bool { return $this->status === SectionResolutionStatus::NOT_FOUND; }
}
