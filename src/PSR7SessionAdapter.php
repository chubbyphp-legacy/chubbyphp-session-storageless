<?php

declare(strict_types=1);

namespace Chubbyphp\Session\Storageless;

use PSR7Sessions\Storageless\Session\SessionInterface as PSR7SessionInterface;
use Zend\Expressive\Session\SessionInterface as ZendSessionInterface;

final class PSR7SessionAdapter implements ZendSessionInterface
{
    public const SESSION_REGENERATED_NAME = '_regenerated';

    /**
     * @var PSR7SessionInterface
     */
    private $session;

    /**
     * @param PSR7SessionInterface $session
     */
    public function __construct(PSR7SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return (array) $this->session->jsonSerialize();
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $this->session->get($name, $default);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->session->has($name);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function set(string $name, $value): void
    {
        $this->session->set($name, $value);
    }

    /**
     * @param string $name
     */
    public function unset(string $name): void
    {
        $this->session->remove($name);
    }

    public function clear(): void
    {
        $this->session->clear();
    }

    /**
     * @return bool
     */
    public function hasChanged(): bool
    {
        return $this->session->hasChanged();
    }

    /**
     * @return ZendSessionInterface
     */
    public function regenerate(): ZendSessionInterface
    {
        $this->session->set(self::SESSION_REGENERATED_NAME, time());

        return $this;
    }

    /**
     * @return bool
     */
    public function isRegenerated(): bool
    {
        return $this->session->has(self::SESSION_REGENERATED_NAME);
    }
}
