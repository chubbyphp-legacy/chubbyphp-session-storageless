<?php

declare(strict_types=1);

namespace Chubbyphp\Session\Storageless;

use Mezzio\Session\SessionInterface as MezzioSessionInterface;
use Mezzio\Session\SessionPersistenceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware as PSR7SessionMiddleware;
use PSR7Sessions\Storageless\Session\SessionInterface as PSR7SessionInterface;

final class PSR7StoragelessSessionPersistence implements SessionPersistenceInterface
{
    public function initializeSessionFromRequest(ServerRequestInterface $request): MezzioSessionInterface
    {
        /** @var PSR7SessionInterface|null $psr7StoragelessSession */
        $psr7StoragelessSession = $request->getAttribute(PSR7SessionMiddleware::SESSION_ATTRIBUTE);
        if (!$psr7StoragelessSession instanceof PSR7SessionInterface) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Please add the following middleware "%s" before execute this method "%s"',
                    PSR7SessionMiddleware::class,
                    __METHOD__
                )
            );
        }

        return new PSR7SessionAdapter($psr7StoragelessSession);
    }

    public function persistSession(MezzioSessionInterface $session, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
