<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Http;

use NeoDashboard\Core\Http\CurrentRequestTypeProvider;
use NeoDashboard\Core\Http\RequestContext;
use NeoDashboard\Core\Http\RequestContextFactory;
use NeoDashboard\Core\Http\RequestType;
use PHPUnit\Framework\TestCase;

final class CurrentRequestTypeProviderTest extends TestCase
{
    /** @dataProvider requestTypes */
    public function testItReturnsTheContextTypeValue(RequestType $type): void
    {
        $factory = new class($type) implements RequestContextFactory {
            public int $calls = 0;

            public function __construct(private readonly RequestType $type) {}

            public function create(): RequestContext
            {
                $this->calls++;
                return new RequestContext($this->type, '/', false, false, false, false);
            }
        };

        $provider = new CurrentRequestTypeProvider($factory);

        self::assertSame($type->value, $provider->type());
        self::assertSame($type->value, $provider->type());
        self::assertSame(1, $factory->calls);
    }

    /** @return iterable<string, array{RequestType}> */
    public function requestTypes(): iterable
    {
        foreach (RequestType::cases() as $type) {
            yield $type->value => [$type];
        }
    }
}
