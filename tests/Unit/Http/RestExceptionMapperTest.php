<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Tests\Unit\Http;

use Error;
use Exception;
use NeoDashboard\Core\Http\RestApiException;
use NeoDashboard\Core\Http\RestExceptionMapper;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestExceptionMapperTest extends TestCase
{
    private RestExceptionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new RestExceptionMapper();
    }

    public function testExplicitApiExceptionsExposeOnlyTheirPublicContract(): void
    {
        $failure = $this->mapper->map(new RestApiException(
            'template_not_found',
            'Template not found.',
            404,
            new RuntimeException('SQL table details must stay private.'),
        ));

        self::assertSame('template_not_found', $failure->code);
        self::assertSame('Template not found.', $failure->message);
        self::assertSame(404, $failure->status);
    }

    /** @dataProvider existingClientErrorProvider */
    public function testExistingFourHundredExceptionsRemainCompatible(int $status): void
    {
        $failure = $this->mapper->map(new Exception('Expected request error.', $status));

        self::assertSame('neo_dashboard_request_failed', $failure->code);
        self::assertSame('Expected request error.', $failure->message);
        self::assertSame($status, $failure->status);
    }

    /** @return array<string, array{int}> */
    public function existingClientErrorProvider(): array
    {
        return [
            'bad request' => [400],
            'unauthorized' => [401],
            'forbidden' => [403],
            'not found' => [404],
            'conflict' => [409],
            'unprocessable' => [422],
            'rate limited' => [429],
        ];
    }

    /** @dataProvider internalThrowableProvider */
    public function testInternalThrowablesNeverExposeTheirDetails(\Throwable $throwable): void
    {
        $failure = $this->mapper->map($throwable);

        self::assertSame('neo_dashboard_internal_error', $failure->code);
        self::assertSame('The request could not be processed.', $failure->message);
        self::assertSame(500, $failure->status);
        self::assertStringNotContainsString('secret', $failure->message);
    }

    /** @return array<string, array{\Throwable}> */
    public function internalThrowableProvider(): array
    {
        return [
            'ordinary exception' => [new RuntimeException('secret database details')],
            'server exception' => [new RuntimeException('secret upstream response', 503)],
            'PHP error' => [new Error('secret type information')],
        ];
    }

    public function testInvalidExplicitStatusFallsBackToInternalServerError(): void
    {
        $failure = $this->mapper->map(new RestApiException(
            'invalid_status',
            'Safe public message.',
            200,
        ));

        self::assertSame(500, $failure->status);
        self::assertSame('Safe public message.', $failure->message);
    }
}
