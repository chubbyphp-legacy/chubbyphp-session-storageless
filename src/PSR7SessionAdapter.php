<?php

declare(strict_types=1);

namespace Chubbyphp\Session\Storageless;

use Mezzio\Session\SessionInterface as MezzioSessionInterface;
use PSR7Sessions\Storageless\Session\SessionInterface as PSR7SessionInterface;

final class PSR7SessionAdapter implements MezzioSessionInterface
{
    public const SESSION_REGENERATED_NAME = '_regenerated';

    /**
     * @var PSR7SessionInterface
     */
    private $session;

    public function __construct(PSR7SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return (array) $this->session->jsonSerialize();
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $this->session->get($name, $default);
    }

    public function has(string $name): bool
    {
        return $this->session->has($name);
    }

    /**
     * @param mixed $value
     */
    public function set(string $name, $value): void
    {
        $this->session->set($name, $value);
    }

    public function unset(string $name): void
    {
        $this->session->remove($name);
    }

    public function clear(): void
    {
        $this->session->clear();
    }

    public function hasChanged(): bool
    {
        return $this->session->hasChanged();
    }

    public function regenerate(): MezzioSessionInterface
    {
        $this->session->set(self::SESSION_REGENERATED_NAME, time());

        return $this;
    }

    public function isRegenerated(): bool
    {
        return $this->session->has(self::SESSION_REGENERATED_NAME);
    }
}
