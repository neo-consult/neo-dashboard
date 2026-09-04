<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\WordPress;

use NeoDashboard\Core\Http\RequestTypeProvider;
use NeoDashboard\Core\LifecycleLogger;
use NeoDashboard\Core\PerformanceTimer;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\WordPress\DashboardDiagnosticsRegistrar;
use PHPUnit\Framework\TestCase;

final class DashboardDiagnosticsRegistrarTest extends TestCase
{
    public function testItRegistersDiagnosticsWhenEnabled(): void
    {
        $logger = new RecordingLifecycleLogger();

        (new DashboardDiagnosticsRegistrar($logger, true))->registerHooks();

        self::assertSame(1, $logger->registrations);
    }

    public function testItSkipsDiagnosticsWhenDisabled(): void
    {
        $logger = new RecordingLifecycleLogger();

        (new DashboardDiagnosticsRegistrar($logger, false))->registerHooks();

        self::assertSame(0, $logger->registrations);
    }
}

final class RecordingLifecycleLogger extends LifecycleLogger
{
    public int $registrations = 0;

    public function __construct()
    {
        $logger = new Logger();
        parent::__construct(
            new DiagnosticsRequestTypeProvider(),
            new PerformanceTimer($logger),
            $logger,
        );
    }

    public function registerHooks(): void
    {
        ++$this->registrations;
    }
}

final class DiagnosticsRequestTypeProvider implements RequestTypeProvider
{
    public function type(): string { return 'WEB'; }
}
