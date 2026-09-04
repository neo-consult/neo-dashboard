<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Security;

use NeoDashboard\Core\Http\RequestContextFactory;

/**
 * Coordinates request inspection, access policy and WordPress side effects.
 */
final readonly class WordPressAccessEnforcer
{
    public function __construct(
        private RequestContextFactory $contextFactory,
        private DashboardAccessPolicy $policy,
        private AccessCapabilityResolver $capabilities,
        private PublicRouteLoader $publicRoutes,
        private AccessDecisionHandler $handler,
    ) {}

    public function enforce(): void
    {
        $context = $this->contextFactory->create();
        $this->publicRoutes->load($context);

        $decision = $this->policy->decide(
            $context,
            $this->capabilities->canAccessDashboard(),
            $this->capabilities->canAccessAdmin(),
        );

        $this->handler->handle($decision, $context);
    }
}
