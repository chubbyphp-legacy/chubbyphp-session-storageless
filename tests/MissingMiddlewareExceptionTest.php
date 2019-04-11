<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Session\Storageless;

use Chubbyphp\Session\Storageless\MissingMiddlewareException;
use PHPUnit\Framework\TestCase;
use PSR7Sessions\Storageless\Http\SessionMiddleware as PSR7SessionMiddleware;

/**
 * @covers \Chubbyphp\Session\Storageless\MissingMiddlewareException
 */
final class MissingMiddlewareExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage(
            sprintf(
                'Call to private %s::__construct() from context \'%s\'',
                MissingMiddlewareException::class,
                MissingMiddlewareExceptionTest::class
            )
        );

        new MissingMiddlewareException('test');
    }

    public function testCreateForMissingRoute(): void
    {
        $exception = MissingMiddlewareException::createForMissingMiddleware(PSR7SessionMiddleware::class, __METHOD__);

        self::assertSame(
            sprintf(
                'Please add the following middleware "%s" before execute this method "%s"',
                PSR7SessionMiddleware::class,
                __METHOD__
            ),
            $exception->getMessage()
        );
    }
}
