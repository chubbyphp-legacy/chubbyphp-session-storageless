<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Session\Storageless;

use Chubbyphp\Session\Storageless\NotImplementedException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\Session\Storageless\NotImplementedException
 */
final class NotImplementedExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage(
            sprintf(
                'Call to private %s::__construct() from context \'%s\'',
                NotImplementedException::class,
                NotImplementedExceptionTest::class
            )
        );

        new NotImplementedException('test');
    }

    public function testCreateForMethod(): void
    {
        $exception = NotImplementedException::createForMethod(__METHOD__);

        self::assertSame(
            sprintf('Method "%s" was not implemented', __METHOD__),
            $exception->getMessage()
        );
    }
}
