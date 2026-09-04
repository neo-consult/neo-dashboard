<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Manager;

use NeoDashboard\Core\Http\DashboardRequestEnvironment;
use NeoDashboard\Core\Manager\ContextResolver;
use PHPUnit\Framework\TestCase;

final class ContextResolverTest extends TestCase
{
    /** @dataProvider dashboardRequests */
    public function testItRecognizesDashboardRequests(ContextEnvironment $environment, bool $expected): void
    {
        self::assertSame($expected, (new ContextResolver($environment))->isDashboard());
    }

    public function dashboardRequests(): iterable
    {
        yield 'admin page' => [new ContextEnvironment(true, 'neo-dashboard'), true];
        yield 'admin URI' => [new ContextEnvironment(true, '', '/wp-admin/admin.php?page=neo-dashboard'), true];
        yield 'other admin page' => [new ContextEnvironment(true, 'tools.php', '/wp-admin/tools.php'), false];
        yield 'section query var' => [new ContextEnvironment(false, '', '/', ['neo_section' => 'neo-calendar']), true];
        yield 'dashboard page query var' => [new ContextEnvironment(false, '', '/', ['pagename' => 'neo-dashboard']), true];
        yield 'dashboard path' => [new ContextEnvironment(false, '', '/neo-dashboard/neo-calendar'), true];
        yield 'public page' => [new ContextEnvironment(false, '', '/umfrage/'), false];
    }

    /** @dataProvider resolvedContexts */
    public function testItResolvesTheCurrentContext(ContextEnvironment $environment, string $expected): void
    {
        self::assertSame($expected, (new ContextResolver($environment))->current());
    }

    public function resolvedContexts(): iterable
    {
        yield 'dashboard root' => [new ContextEnvironment(false, '', '/neo-dashboard/'), 'dashboard-home'];
        yield 'nested path' => [new ContextEnvironment(false, '', '/neo-dashboard/neo-calendar/events?month=8'), 'neo-calendar/events'];
        yield 'section fallback' => [new ContextEnvironment(false, '', '/', ['neo_section' => 'neo-contacts']), 'neo-contacts'];
        yield 'default' => [new ContextEnvironment(false, '', '/'), 'dashboard-home'];
    }

    public function testItCachesTheResolvedContextForTheRequest(): void
    {
        $environment = new ContextEnvironment(false, '', '/neo-dashboard/neo-calendar');
        $resolver = new ContextResolver($environment);

        self::assertSame('neo-calendar', $resolver->current());
        $environment->uri = '/neo-dashboard/neo-contacts';
        self::assertSame('neo-calendar', $resolver->current());
    }
}

final class ContextEnvironment implements DashboardRequestEnvironment
{
    /** @param array<string, string> $queryVars */
    public function __construct(
        private bool $admin = false,
        private string $page = '',
        public string $uri = '',
        private array $queryVars = [],
    ) {}

    public function isAdmin(): bool { return $this->admin; }
    public function adminPage(): string { return $this->page; }
    public function requestUri(): string { return $this->uri; }
    public function queryVar(string $name): string { return $this->queryVars[$name] ?? ''; }
}
