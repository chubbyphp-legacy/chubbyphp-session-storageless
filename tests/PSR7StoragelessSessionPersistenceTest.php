<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Session\Storageless;

use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\Session\Storageless\MissingMiddlewareException;
use Chubbyphp\Session\Storageless\PSR7StoragelessSessionPersistence;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware as PSR7SessionMiddleware;
use PSR7Sessions\Storageless\Session\SessionInterface as PSR7SessionInterface;
use Zend\Expressive\Session\SessionInterface as ZendSessionInterface;

/**
 * @covers \Chubbyphp\Session\Storageless\PSR7StoragelessSessionPersistence
 */
class PSR7StoragelessSessionPersistenceTest extends TestCase
{
    use MockByCallsTrait;

    public function testInitializeSessionFromRequestWithMissingPsr7SessionAttribute(): void
    {
        $this->expectException(MissingMiddlewareException::class);
        $this->expectExceptionMessage(sprintf(
            'Please add the following middleware "%s" before execute this method "%s::initializeSessionFromRequest"',
            PSR7SessionMiddleware::class,
            PSR7StoragelessSessionPersistence::class
        ));

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getAttribute')->with(PSR7SessionMiddleware::SESSION_ATTRIBUTE, null)->willReturn(null),
        ]);

        $persistence = new PSR7StoragelessSessionPersistence();
        $persistence->initializeSessionFromRequest($request);
    }

    public function testInitializeSessionFromRequestWithPsr7SessionAttribute(): void
    {
        /** @var PSR7SessionInterface|MockObject $psr7Session */
        $psr7Session = $this->getMockByCalls(PSR7SessionInterface::class);

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getAttribute')
                ->with(PSR7SessionMiddleware::SESSION_ATTRIBUTE, null)
                ->willReturn($psr7Session),
        ]);

        $persistence = new PSR7StoragelessSessionPersistence();
        $persistence->initializeSessionFromRequest($request);
    }

    public function testPersistSession(): void
    {
        /** @var ZendSessionInterface|MockObject $zendSession */
        $zendSession = $this->getMockByCalls(ZendSessionInterface::class, []);

        /** @var ResponseInterface|MockObject $response */
        $response = $this->getMockByCalls(ResponseInterface::class);

        $persistence = new PSR7StoragelessSessionPersistence();
        $persistence->persistSession($zendSession, $response);
    }
}