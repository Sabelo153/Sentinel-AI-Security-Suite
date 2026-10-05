<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Container;

final class ContainerTest extends TestCase
{
    public function testBindResolvesFreshInstanceEachCall(): void
    {
        $container = new Container();
        $container->bind('service', static fn () => new \stdClass());

        self::assertNotSame($container->make('service'), $container->make('service'));
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton('service', static fn () => new \stdClass());

        self::assertSame($container->make('service'), $container->make('service'));
    }

    public function testInstanceReturnsSuppliedObject(): void
    {
        $container = new Container();
        $object = new \stdClass();
        $container->instance('config', $object);

        self::assertSame($object, $container->make('config'));
    }

    public function testHasReflectsRegisteredServices(): void
    {
        $container = new Container();

        self::assertFalse($container->has('service'));

        $container->bind('service', static fn () => new \stdClass());

        self::assertTrue($container->has('service'));
    }

    public function testMakeThrowsForUnresolvedService(): void
    {
        $container = new Container();

        $this->expectException(\RuntimeException::class);
        $container->make('missing');
    }

    public function testFactoryReceivesTheContainer(): void
    {
        $container = new Container();
        $container->instance('name', 'Sentinel');
        $container->bind('greeting', static fn (Container $c) => 'Hello, ' . $c->make('name'));

        self::assertSame('Hello, Sentinel', $container->make('greeting'));
    }

    public function testSingletonFactoryExecutesOnlyOnce(): void
    {
        $calls = 0;
        $container = new Container();
        $container->singleton('service', static function () use (&$calls) {
            $calls++;

            return new \stdClass();
        });

        $container->make('service');
        $container->make('service');

        self::assertSame(1, $calls);
    }
}
