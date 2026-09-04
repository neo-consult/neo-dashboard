<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Security;

use NeoDashboard\Core\Http\RequestContext;
use NeoDashboard\Core\Http\RequestType;
use NeoDashboard\Core\Security\AccessDecision;
use NeoDashboard\Core\Security\DashboardAccessPolicy;
use NeoDashboard\Core\Security\PublicRouteRegistry;
use PHPUnit\Framework\TestCase;

final class DashboardAccessPolicyTest extends TestCase
{
    private PublicRouteRegistry $publicRoutes;
    private DashboardAccessPolicy $policy;

    protected function setUp(): void
    {
        $this->publicRoutes = new PublicRouteRegistry();
        $this->policy = new DashboardAccessPolicy($this->publicRoutes);
    }

    /**
     * @dataProvider delegatedContextProvider
     */
    public function testDelegatedContextsAreAllowed(RequestContext $context): void
    {
        self::assertSame(
            AccessDecision::Allow,
            $this->policy->decide($context, false, false),
        );
    }

    /**
     * @return array<string, array{RequestContext}>
     */
    public function delegatedContextProvider(): array
    {
        return [
            'AJAX' => [$this->context(RequestType::Ajax, '/wp-admin/admin-ajax.php')],
            'REST' => [$this->context(RequestType::Rest, '/wp-json/neo-dashboard/v1/widget')],
            'cron' => [$this->context(RequestType::Cron, '/wp-cron.php')],
            'CLI' => [$this->context(RequestType::Cli, '/')],
            'login' => [$this->context(RequestType::Web, '/wp-login.php', false, true)],
            'admin post' => [new RequestContext(
                RequestType::Admin,
                '/wp-admin/admin-post.php',
                false,
                false,
                true,
                true,
            )],
        ];
    }

    public function testAnAnonymousPublicRequestIsAllowed(): void
    {
        $this->publicRoutes->registerPrefix('/einwilligung');
        $context = $this->context(RequestType::Web, '/einwilligung/session/123');

        self::assertSame(
            AccessDecision::Allow,
            $this->policy->decide($context, false, false),
        );
    }

    public function testAnAnonymousPrivateWebRequestRedirectsToLogin(): void
    {
        $context = $this->context(RequestType::Web, '/neo-dashboard', true);

        self::assertSame(
            AccessDecision::RedirectToLogin,
            $this->policy->decide($context, false, false),
        );
    }

    public function testWordPressHandlesAnonymousAdminAuthentication(): void
    {
        $context = $this->context(RequestType::Admin, '/wp-admin');

        self::assertSame(
            AccessDecision::Allow,
            $this->policy->decide($context, false, false),
        );
    }

    public function testDashboardUserIsRedirectedAwayFromWordPressAdmin(): void
    {
        $context = $this->context(RequestType::Admin, '/wp-admin/plugins.php', false, false, true);

        self::assertSame(
            AccessDecision::RedirectToDashboard,
            $this->policy->decide($context, true, false),
        );
    }

    public function testAdminCapableUserMayUseWordPressAdmin(): void
    {
        $context = $this->context(RequestType::Admin, '/wp-admin/plugins.php', false, false, true);

        self::assertSame(
            AccessDecision::Allow,
            $this->policy->decide($context, true, true),
        );
    }

    public function testAuthenticatedDashboardUserMayAccessDashboard(): void
    {
        $context = $this->context(RequestType::Web, '/neo-dashboard/calendar', true, false, true);

        self::assertSame(
            AccessDecision::Allow,
            $this->policy->decide($context, true, false),
        );
    }

    public function testAdminCapableUserMayAccessDashboardWithoutDashboardCapability(): void
    {
        $context = $this->context(RequestType::Web, '/neo-dashboard', true, false, true);

        self::assertSame(
            AccessDecision::Allow,
            $this->policy->decide($context, false, true),
        );
    }

    public function testAuthenticatedUserWithoutCapabilitiesIsDeniedDashboardAccess(): void
    {
        $context = $this->context(RequestType::Web, '/neo-dashboard', true, false, true);

        self::assertSame(
            AccessDecision::Deny,
            $this->policy->decide($context, false, false),
        );
    }

    public function testDashboardUserIsRedirectedFromUnrelatedFrontendPage(): void
    {
        $context = $this->context(RequestType::Web, '/contact', false, false, true);

        self::assertSame(
            AccessDecision::RedirectToDashboard,
            $this->policy->decide($context, true, false),
        );
    }

    public function testAdminCapableUserMayUseUnrelatedFrontendPage(): void
    {
        $context = $this->context(RequestType::Web, '/contact', false, false, true);

        self::assertSame(
            AccessDecision::Allow,
            $this->policy->decide($context, true, true),
        );
    }

    public function testRegularAuthenticatedUserMayUseUnrelatedFrontendPage(): void
    {
        $context = $this->context(RequestType::Web, '/contact', false, false, true);

        self::assertSame(
            AccessDecision::Allow,
            $this->policy->decide($context, false, false),
        );
    }

    public function testSimilarPublicPathDoesNotBypassAuthentication(): void
    {
        $this->publicRoutes->registerPrefix('/umfrage');
        $context = $this->context(RequestType::Web, '/umfrage-malicious');

        self::assertSame(
            AccessDecision::RedirectToLogin,
            $this->policy->decide($context, false, false),
        );
    }

    private function context(
        RequestType $type,
        string $path,
        bool $isDashboard = false,
        bool $isLogin = false,
        bool $isAuthenticated = false,
    ): RequestContext {
        return new RequestContext(
            $type,
            $path,
            $isDashboard,
            $isLogin,
            $isAuthenticated,
        );
    }
}
