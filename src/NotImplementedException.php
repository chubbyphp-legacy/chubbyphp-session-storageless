<?php

declare(strict_types=1);

namespace Chubbyphp\Session\Storageless;

final class NotImplementedException extends \RuntimeException
{
    /**
     * @param mixed ...$args
     */
    private function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    /**
     * @param string $method
     *
     * @return self
     */
    public static function createForMethod(string $method): self
    {
        return new self(sprintf('Method "%s" was not implemented', $method));
    }
}
