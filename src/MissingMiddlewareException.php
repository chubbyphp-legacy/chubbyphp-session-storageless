<?php

declare(strict_types=1);

namespace Chubbyphp\Session\Storageless;

final class MissingMiddlewareException extends \RuntimeException
{
    /**
     * @param mixed ...$args
     */
    private function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    /**
     * @param string $middlewareClass
     * @param string $method
     *
     * @return self
     */
    public static function createForMissingMiddleware(string $middlewareClass, string $method): self
    {
        return new self(
            sprintf('Please add the following middleware "%s" before execute this method "%s"',
                $middlewareClass,
                $method
            )
        );
    }
}
