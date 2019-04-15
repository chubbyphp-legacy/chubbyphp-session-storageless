<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Session\Storageless;

use Chubbyphp\Mock\Argument\ArgumentCallback;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\Session\Storageless\PSR7SessionAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PSR7Sessions\Storageless\Session\SessionInterface as PSR7SessionInterface;

/**
 * @covers \Chubbyphp\Session\Storageless\PSR7SessionAdapter
 */
final class PSR7SessionAdapterTest extends TestCase
{
    use MockByCallsTrait;

    public function testToArray(): void
    {
        $object = new \stdClass();
        $object->key = 'value';

        /** @var PSR7SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(PSR7SessionInterface::class, [
            Call::create('jsonSerialize')->with()->willReturn($object),
        ]);

        $sessionAdapter = new PSR7SessionAdapter($session);

        self::assertSame(['key' => 'value'], $sessionAdapter->toArray());
    }

    public function testGet(): void
    {
        /** @var PSR7SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(PSR7SessionInterface::class, [
            Call::create('get')->with('key', null)->willReturn('value'),
        ]);

        $sessionAdapter = new PSR7SessionAdapter($session);

        self::assertSame('value', $sessionAdapter->get('key'));
    }

    public function testHas(): void
    {
        /** @var PSR7SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(PSR7SessionInterface::class, [
            Call::create('has')->with('key')->willReturn(true),
        ]);

        $sessionAdapter = new PSR7SessionAdapter($session);

        self::assertTrue($sessionAdapter->has('key'));
    }

    public function testSet(): void
    {
        /** @var PSR7SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(PSR7SessionInterface::class, [
            Call::create('set')->with('key', 'value'),
        ]);

        $sessionAdapter = new PSR7SessionAdapter($session);
        $sessionAdapter->set('key', 'value');
    }

    public function testUnset(): void
    {
        /** @var PSR7SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(PSR7SessionInterface::class, [
            Call::create('remove')->with('key'),
        ]);

        $sessionAdapter = new PSR7SessionAdapter($session);
        $sessionAdapter->unset('key');
    }

    public function testClear(): void
    {
        /** @var PSR7SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(PSR7SessionInterface::class, [
            Call::create('clear')->with(),
        ]);

        $sessionAdapter = new PSR7SessionAdapter($session);
        $sessionAdapter->clear();
    }

    public function testHasChanged(): void
    {
        /** @var PSR7SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(PSR7SessionInterface::class, [
            Call::create('hasChanged')->with()->willReturn(true),
        ]);

        $sessionAdapter = new PSR7SessionAdapter($session);

        self::assertTrue($sessionAdapter->hasChanged());
    }

    public function testRegenerate(): void
    {
        /** @var PSR7SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(PSR7SessionInterface::class, [
            Call::create('set')
                ->with(
                    '_regenerated',
                    new ArgumentCallback(function (int $timestamp) {
                        $now = time();

                        self::assertGreaterThanOrEqual($now, $timestamp + 10);
                        self::assertLessThanOrEqual($now, $timestamp);
                    })
                ),
        ]);

        $sessionAdapter = new PSR7SessionAdapter($session);
        $sessionAdapter->regenerate();
    }

    public function testIsRegenerated(): void
    {
        /** @var PSR7SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(PSR7SessionInterface::class, [
            Call::create('has')->with('_regenerated')->willReturn(true),
        ]);

        $sessionAdapter = new PSR7SessionAdapter($session);

        self::assertTrue($sessionAdapter->isRegenerated());
    }
}
