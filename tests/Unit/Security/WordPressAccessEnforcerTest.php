<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Security;

use NeoDashboard\Core\Http\RequestContext;
use NeoDashboard\Core\Http\RequestContextFactory;
use NeoDashboard\Core\Http\RequestType;
use NeoDashboard\Core\Security\AccessCapabilityResolver;
use NeoDashboard\Core\Security\AccessDecision;
use NeoDashboard\Core\Security\AccessDecisionHandler;
use NeoDashboard\Core\Security\DashboardAccessPolicy;
use NeoDashboard\Core\Security\PublicRouteLoader;
use NeoDashboard\Core\Security\PublicRouteRegistry;
use NeoDashboard\Core\Security\WordPressAccessEnforcer;
use PHPUnit\Framework\TestCase;

final class WordPressAccessEnforcerTest extends TestCase
{
    public function testItCoordinatesContextRoutesCapabilitiesAndDecisionHandler(): void
    {
        $context = new RequestContext(
            RequestType::Web,
            '/neo-dashboard/calendar',
            true,
            false,
            true,
        );
        $contextFactory = new FakeRequestContextFactory($context);
        $registry = new PublicRouteRegistry();
        $policy = new DashboardAccessPolicy($registry);
        $capabilities = new FakeAccessCapabilityResolver(true, false);
        $routeLoader = new FakePublicRouteLoader();
        $handler = new RecordingAccessDecisionHandler();
        $enforcer = new WordPressAccessEnforcer(
            $contextFactory,
            $policy,
            $capabilities,
            $routeLoader,
            $handler,
        );

        $enforcer->enforce();

        self::assertSame($context, $routeLoader->loadedContext);
        self::assertSame(AccessDecision::Allow, $handler->decision);
        self::assertSame($context, $handler->context);
        self::assertSame(1, $contextFactory->calls);
        self::assertSame(1, $capabilities->dashboardCalls);
        self::assertSame(1, $capabilities->adminCalls);
    }

    public function testRoutesAreLoadedBeforeThePolicyMakesItsDecision(): void
    {
        $context = new RequestContext(RequestType::Web, '/public/form');
        $registry = new PublicRouteRegistry();
        $handler = new RecordingAccessDecisionHandler();
        $routeLoader = new RegisteringPublicRouteLoader($registry, '/public');
        $enforcer = new WordPressAccessEnforcer(
            new FakeRequestContextFactory($context),
            new DashboardAccessPolicy($registry),
            new FakeAccessCapabilityResolver(false, false),
            $routeLoader,
            $handler,
        );

        $enforcer->enforce();

        self::assertSame(AccessDecision::Allow, $handler->decision);
    }
}

final class FakeRequestContextFactory implements RequestContextFactory
{
    public int $calls = 0;

    public function __construct(
        private readonly RequestContext $context,
    ) {}

    public function create(): RequestContext
    {
        ++$this->calls;

        return $this->context;
    }
}

final class FakeAccessCapabilityResolver implements AccessCapabilityResolver
{
    public int $dashboardCalls = 0;
    public int $adminCalls = 0;

    public function __construct(
        private readonly bool $dashboard,
        private readonly bool $admin,
    ) {}

    public function canAccessDashboard(): bool
    {
        ++$this->dashboardCalls;

        return $this->dashboard;
    }

    public function canAccessAdmin(): bool
    {
        ++$this->adminCalls;

        return $this->admin;
    }
}

final class FakePublicRouteLoader implements PublicRouteLoader
{
    public ?RequestContext $loadedContext = null;

    public function load(RequestContext $context): void
    {
        $this->loadedContext = $context;
    }
}

final class RegisteringPublicRouteLoader implements PublicRouteLoader
{
    public function __construct(
        private readonly PublicRouteRegistry $registry,
        private readonly string $prefix,
    ) {}

    public function load(RequestContext $context): void
    {
        $this->registry->registerPrefix($this->prefix);
    }
}

final class RecordingAccessDecisionHandler implements AccessDecisionHandler
{
    public ?AccessDecision $decision = null;
    public ?RequestContext $context = null;

    public function handle(AccessDecision $decision, RequestContext $context): void
    {
        $this->decision = $decision;
        $this->context = $context;
    }
}
