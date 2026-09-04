<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Rest;

use NeoDashboard\Core\Rest\RestRouteCollection;
use NeoDashboard\Core\Rest\RestRouteDefinition;
use PHPUnit\Framework\TestCase;

final class RestRouteCollectionTest extends TestCase
{
    public function testRoutesAreIndexedByPath(): void
    {
        $collection = new RestRouteCollection();
        $definition = new RestRouteDefinition('/items', static fn(): array => [], 'GET');

        $collection->add($definition);

        self::assertSame(['/items' => $definition], $collection->all());
    }

    public function testLaterRegistrationReplacesTheSamePath(): void
    {
        $collection = new RestRouteCollection();
        $first = new RestRouteDefinition('/items', static fn(): array => [], 'GET');
        $replacement = new RestRouteDefinition('/items', static fn(): array => [], 'POST', [], 'edit_posts');

        $collection->add($first);
        $collection->add($replacement);

        self::assertCount(1, $collection->all());
        self::assertSame($replacement, $collection->all()['/items']);
    }
}
