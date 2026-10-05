<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;

final class HttpExceptionTest extends TestCase
{
    public function testStoresStatusCodeAndSafeMessage(): void
    {
        $exception = new HttpException(400, 'Bad request.');

        self::assertSame(400, $exception->statusCode());
        self::assertSame('Bad request.', $exception->getMessage());
        self::assertNull($exception->errorCode());
    }

    public function testStoresOptionalErrorCode(): void
    {
        $exception = new HttpException(422, 'Validation failed.', 'validation_error');

        self::assertSame('validation_error', $exception->errorCode());
    }

    /** @return array<string, array{0: \Closure(): HttpException, 1: int}> */
    public static function factoryProvider(): array
    {
        return [
            'badRequest' => [static fn () => HttpException::badRequest(), 400],
            'unauthorized' => [static fn () => HttpException::unauthorized(), 401],
            'forbidden' => [static fn () => HttpException::forbidden(), 403],
            'notFound' => [static fn () => HttpException::notFound(), 404],
            'methodNotAllowed' => [static fn () => HttpException::methodNotAllowed(), 405],
            'unprocessable' => [static fn () => HttpException::unprocessable(), 422],
            'tooManyRequests' => [static fn () => HttpException::tooManyRequests(), 429],
            'serverError' => [static fn () => HttpException::serverError(), 500],
            'serviceUnavailable' => [static fn () => HttpException::serviceUnavailable(), 503],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('factoryProvider')]
    public function testNamedConstructorsMapToStatusCodes(\Closure $factory, int $expectedStatus): void
    {
        self::assertSame($expectedStatus, $factory()->statusCode());
    }

    public function testWrapsPreviousExceptionWithoutLeakingItsMessage(): void
    {
        $internal = new \RuntimeException('DB connection failed at 10.0.0.5');
        $exception = new HttpException(500, 'Internal server error.', 'internal_error', $internal);

        self::assertSame('Internal server error.', $exception->getMessage());
        self::assertSame($internal, $exception->getPrevious());
    }
}
