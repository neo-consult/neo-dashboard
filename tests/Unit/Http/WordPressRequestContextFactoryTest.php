<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Http;

use NeoDashboard\Core\Http\RequestType;
use NeoDashboard\Core\Http\WordPressRequestContextFactory;
use PHPUnit\Framework\TestCase;

final class WordPressRequestContextFactoryTest extends TestCase
{
    private FakeWordPressRequestEnvironment $environment;
    private WordPressRequestContextFactory $factory;

    protected function setUp(): void
    {
        $this->environment = new FakeWordPressRequestEnvironment();
        $this->factory = new WordPressRequestContextFactory($this->environment);
    }

    /**
     * @dataProvider requestTypeProvider
     */
    public function testItDetectsRequestTypes(string $flag, RequestType $expected): void
    {
        $this->environment->{$flag} = true;

        self::assertSame($expected, $this->factory->create()->type);
    }

    /**
     * @return array<string, array{string, RequestType}>
     */
    public function requestTypeProvider(): array
    {
        return [
            'ajax' => ['ajax', RequestType::Ajax],
            'rest' => ['rest', RequestType::Rest],
            'cli' => ['cli', RequestType::Cli],
            'cron' => ['cron', RequestType::Cron],
            'admin' => ['admin', RequestType::Admin],
        ];
    }

    public function testWebIsTheDefaultRequestType(): void
    {
        self::assertSame(RequestType::Web, $this->factory->create()->type);
    }

    public function testMoreSpecificRequestTypesTakePriorityOverAdmin(): void
    {
        $this->environment->admin = true;
        $this->environment->rest = true;
        $this->environment->ajax = true;

        self::assertSame(RequestType::Ajax, $this->factory->create()->type);
    }

    /**
     * @dataProvider dashboardPathProvider
     */
    public function testItRecognizesDashboardPaths(
        string $requestPath,
        string $dashboardPath,
        bool $expected,
    ): void {
        $this->environment->uri = $requestPath;
        $this->environment->dashboardPath = $dashboardPath;

        self::assertSame($expected, $this->factory->create()->isDashboard);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public function dashboardPathProvider(): array
    {
        return [
            'dashboard root' => ['/neo-dashboard', '/neo-dashboard', true],
            'dashboard section' => ['/neo-dashboard/calendar', '/neo-dashboard', true],
            'trailing slash' => ['/neo-dashboard/', '/neo-dashboard/', true],
            'similar prefix' => ['/neo-dashboard-malicious', '/neo-dashboard', false],
            'unrelated page' => ['/contact', '/neo-dashboard', false],
            'WordPress subdirectory' => [
                '/wordpress/neo-dashboard/calendar',
                '/wordpress/neo-dashboard',
                true,
            ],
        ];
    }

    public function testARegisteredSectionAlsoMarksTheRequestAsDashboard(): void
    {
        $this->environment->uri = '/index.php';
        $this->environment->section = 'calendar';

        self::assertTrue($this->factory->create()->isDashboard);
    }

    /**
     * @dataProvider loginRequestProvider
     */
    public function testItRecognizesLoginRequests(string $path, string $script): void
    {
        $this->environment->uri = $path;
        $this->environment->script = $script;

        self::assertTrue($this->factory->create()->isLogin);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function loginRequestProvider(): array
    {
        return [
            'script name' => ['/custom-login', 'wp-login.php'],
            'root path' => ['/wp-login.php?action=login', 'index.php'],
            'subdirectory path' => ['/wordpress/wp-login.php', 'index.php'],
            'legacy register script' => ['/custom-register', 'wp-register.php'],
            'legacy register path' => ['/wordpress/wp-register.php', 'index.php'],
        ];
    }

    public function testItRecognizesAdminPostAsADelegatedAction(): void
    {
        $this->environment->admin = true;
        $this->environment->authenticated = true;
        $this->environment->uri = '/wp-admin/admin-post.php';
        $this->environment->script = 'admin-post.php';

        $context = $this->factory->create();

        self::assertSame(RequestType::Admin, $context->type);
        self::assertTrue($context->isAdminPost);
    }

    public function testItCopiesAuthenticationStateAndNormalizesThePath(): void
    {
        $this->environment->authenticated = true;
        $this->environment->uri = '/neo-dashboard//calendar/?view=week';

        $context = $this->factory->create();

        self::assertTrue($context->isAuthenticated);
        self::assertSame('/neo-dashboard/calendar', $context->path);
    }
}
