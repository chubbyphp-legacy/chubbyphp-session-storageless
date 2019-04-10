<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Session\Storageless;

use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\Session\Storageless\StoragelessSessionPersistence;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Session\SessionInterface;

/**
 * @covers \Chubbyphp\Session\Storageless\StoragelessSessionPersistence
 */
class StoragelessSessionPersistenceTest extends TestCase
{
    use MockByCallsTrait;

    public function testCreateFromSymmetricKeyDefaults(): void
    {
        $signatureKey = 'JeIn7GmQJRkM4dP3T5ZfVcHk7rxyVoMzR1DptTIquFY=';

        $sessionPersistence = StoragelessSessionPersistence::fromSymmetricKeyDefaults(
            $signatureKey,
            3600
        );

        self::assertInstanceOf(StoragelessSessionPersistence::class, $sessionPersistence);

        self::assertInstanceOf(Signer\Hmac\Sha256::class, $this->getPropertyValue($sessionPersistence, 'signer'));
        self::assertSame($signatureKey, $this->getPropertyValue($sessionPersistence, 'signatureKey'));
        self::assertSame($signatureKey, $this->getPropertyValue($sessionPersistence, 'verificationKey'));
        self::assertSame(3600, $this->getPropertyValue($sessionPersistence, 'expirationTime'));
        self::assertInstanceOf(Parser::class, $this->getPropertyValue($sessionPersistence, 'tokenParser'));

        /** @var SetCookie $defaultCookie */
        $defaultCookie = $this->getPropertyValue($sessionPersistence, 'defaultCookie');

        self::assertInstanceOf(SetCookie::class, $defaultCookie);

        self::assertNull($defaultCookie->getDomain());
        self::assertSame(0, $defaultCookie->getExpires());
        self::assertTrue($defaultCookie->getHttpOnly());
        self::assertSame(0, $defaultCookie->getMaxAge());
        self::assertSame('slsession', $defaultCookie->getName());
        self::assertSame('/', $defaultCookie->getPath());
        self::assertTrue($defaultCookie->getSecure());
        self::assertNull($defaultCookie->getValue());

        /** @var SameSite $sameSite */
        $sameSite = $defaultCookie->getSameSite();

        self::assertInstanceOf(SameSite::class, $sameSite);
        self::assertSame('SameSite=Lax', $sameSite->asString());

        self::assertInstanceOf(Clock::class, $this->getPropertyValue($sessionPersistence, 'clock'));
    }

    public function testCreateFromAsymmetricKeyDefaults(): void
    {
        $signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

        $verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

        $sessionPersistence = StoragelessSessionPersistence::fromAsymmetricKeyDefaults(
            $signatureKey,
            $verificationKey,
            3600
        );

        self::assertInstanceOf(StoragelessSessionPersistence::class, $sessionPersistence);

        self::assertInstanceOf(Signer\Rsa\Sha256::class, $this->getPropertyValue($sessionPersistence, 'signer'));
        self::assertSame($signatureKey, $this->getPropertyValue($sessionPersistence, 'signatureKey'));
        self::assertSame($verificationKey, $this->getPropertyValue($sessionPersistence, 'verificationKey'));
        self::assertSame(3600, $this->getPropertyValue($sessionPersistence, 'expirationTime'));
        self::assertInstanceOf(Parser::class, $this->getPropertyValue($sessionPersistence, 'tokenParser'));

        /** @var SetCookie $defaultCookie */
        $defaultCookie = $this->getPropertyValue($sessionPersistence, 'defaultCookie');

        self::assertInstanceOf(SetCookie::class, $defaultCookie);

        self::assertNull($defaultCookie->getDomain());
        self::assertSame(0, $defaultCookie->getExpires());
        self::assertTrue($defaultCookie->getHttpOnly());
        self::assertSame(0, $defaultCookie->getMaxAge());
        self::assertSame('slsession', $defaultCookie->getName());
        self::assertSame('/', $defaultCookie->getPath());
        self::assertTrue($defaultCookie->getSecure());
        self::assertNull($defaultCookie->getValue());

        /** @var SameSite $sameSite */
        $sameSite = $defaultCookie->getSameSite();

        self::assertInstanceOf(SameSite::class, $sameSite);
        self::assertSame('SameSite=Lax', $sameSite->asString());

        self::assertInstanceOf(Clock::class, $this->getPropertyValue($sessionPersistence, 'clock'));
    }

    public function testInitializeSessionFromRequestWithMissingCookie(): void
    {
        $date = new \DateTimeImmutable('now', new \DateTimeZone('GMT'));

        $signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

        $verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getCookieParams')->with()->willReturn([]),
        ]);

        $sessionPersistence = new StoragelessSessionPersistence(
            new Signer\Rsa\Sha256(),
            $signatureKey,
            $verificationKey,
            SetCookie::create(StoragelessSessionPersistence::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            1200,
            new FrozenClock($date)
        );

        $session = $sessionPersistence->initializeSessionFromRequest($request);

        self::assertSame([], $session->toArray());
    }

    public function testInitializeSessionFromRequestWithNotParsableToken(): void
    {
        $date = new \DateTimeImmutable('now', new \DateTimeZone('GMT'));

        $signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

        $verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getCookieParams')->with()->willReturn([
                'slsession' => 'invalidToken',
            ]),
        ]);

        $sessionPersistence = new StoragelessSessionPersistence(
            new Signer\Rsa\Sha256(),
            $signatureKey,
            $verificationKey,
            SetCookie::create(StoragelessSessionPersistence::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            1200,
            new FrozenClock($date)
        );

        $session = $sessionPersistence->initializeSessionFromRequest($request);

        self::assertSame([], $session->toArray());
    }

    public function testInitializeSessionFromRequestWithInvalidToken(): void
    {
        $date = new \DateTimeImmutable('now', new \DateTimeZone('GMT'));

        $expirationTime = 1200;

        $signer = new Signer\Rsa\Sha256();

        $signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

        $verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

        $token = (new Builder())
            ->setIssuedAt($date->getTimestamp())
            ->setExpiration($date->getTimestamp() + $expirationTime)
            ->set(StoragelessSessionPersistence::SESSION_CLAIM, ['key' => 'value'])
            ->sign($signer, $signatureKey)
            ->getToken()
            ->__toString();

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getCookieParams')->with()->willReturn([
                'slsession' => substr($token, 0, -2),
            ]),
        ]);

        $sessionPersistence = new StoragelessSessionPersistence(
            $signer,
            $signatureKey,
            $verificationKey,
            SetCookie::create(StoragelessSessionPersistence::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            $expirationTime,
            new FrozenClock($date)
        );

        $session = $sessionPersistence->initializeSessionFromRequest($request);

        self::assertSame([], $session->toArray());
    }

    public function testInitializeSessionFromRequestWithNotVerifiableToken(): void
    {
        $date = new \DateTimeImmutable('now', new \DateTimeZone('GMT'));

        $expirationTime = 1200;

        $signer = new Signer\Rsa\Sha256();

        $signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

        $verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

        $token = (new Builder())
            ->setIssuedAt($date->getTimestamp())
            ->setExpiration($date->getTimestamp() - $expirationTime)
            ->set(StoragelessSessionPersistence::SESSION_CLAIM, ['key' => 'value'])
            ->sign($signer, $signatureKey)
            ->getToken()
            ->__toString();

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getCookieParams')->with()->willReturn([
                'slsession' => $token,
            ]),
        ]);

        $sessionPersistence = new StoragelessSessionPersistence(
            $signer,
            $signatureKey,
            $verificationKey,
            SetCookie::create(StoragelessSessionPersistence::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            $expirationTime,
            new FrozenClock($date)
        );

        $session = $sessionPersistence->initializeSessionFromRequest($request);

        self::assertSame([], $session->toArray());
    }

    public function testInitializeSessionFromRequestWithValidToken(): void
    {
        $date = new \DateTimeImmutable('now', new \DateTimeZone('GMT'));

        $expirationTime = 1200;

        $signer = new Signer\Rsa\Sha256();

        $signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

        $verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

        $token = (new Builder())
            ->setIssuedAt($date->getTimestamp())
            ->setExpiration($date->getTimestamp() + $expirationTime)
            ->set(StoragelessSessionPersistence::SESSION_CLAIM, ['key' => 'value'])
            ->sign($signer, $signatureKey)
            ->getToken()
            ->__toString();

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getCookieParams')->with()->willReturn([
                'slsession' => $token,
            ]),
        ]);

        $sessionPersistence = new StoragelessSessionPersistence(
            $signer,
            $signatureKey,
            $verificationKey,
            SetCookie::create(StoragelessSessionPersistence::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            $expirationTime,
            new FrozenClock($date)
        );

        $session = $sessionPersistence->initializeSessionFromRequest($request);

        self::assertSame(['key' => 'value'], $session->toArray());
    }

    public function testInitializeSessionFromRequestWithMissingSignature(): void
    {
        $date = new \DateTimeImmutable('now', new \DateTimeZone('GMT'));

        $expirationTime = 1200;

        $signer = new Signer\Rsa\Sha256();

        $signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

        $verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

        $token = (new Builder())
            ->setIssuedAt($date->getTimestamp())
            ->setExpiration($date->getTimestamp() + $expirationTime)
            ->set(StoragelessSessionPersistence::SESSION_CLAIM, ['key' => 'value'])
            ->getToken()
            ->__toString();

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getCookieParams')->with()->willReturn([
                'slsession' => $token,
            ]),
        ]);

        $sessionPersistence = new StoragelessSessionPersistence(
            $signer,
            $signatureKey,
            $verificationKey,
            SetCookie::create(StoragelessSessionPersistence::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            $expirationTime,
            new FrozenClock($date)
        );

        $session = $sessionPersistence->initializeSessionFromRequest($request);

        self::assertSame([], $session->toArray());
    }

    public function testPersistSessionWithoutData(): void
    {
        $date = new \DateTimeImmutable('2019-04-10 20:00:00', new \DateTimeZone('GMT'));

        $signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

        $verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

        /** @var SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(SessionInterface::class, [
            Call::create('toArray')->with()->willReturn([]),
        ]);

        /** @var ResponseInterface|MockObject $response */
        $response = $this->getMockByCalls(ResponseInterface::class, [
            Call::create('getHeader')->with('Set-Cookie')->willReturn([]),
            Call::create('withoutHeader')->with('Set-Cookie')->willReturnSelf(),
            Call::create('withAddedHeader')
                ->with(
                    'Set-Cookie',
                    'slsession=; Path=/; Expires=Mon, 11 Mar 2019 20:00:00 GMT; Secure; HttpOnly; SameSite=Lax'
                )
                ->willReturnSelf(),
        ]);

        $sessionPersistence = new StoragelessSessionPersistence(
            new Signer\Rsa\Sha256(),
            $signatureKey,
            $verificationKey,
            SetCookie::create(StoragelessSessionPersistence::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            1200,
            new FrozenClock($date)
        );

        $sessionPersistence->persistSession($session, $response);
    }

    public function testPersistSessionWithData(): void
    {
        $date = new \DateTimeImmutable('2019-04-10 20:00:00', new \DateTimeZone('GMT'));

        $signatureKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSFlv+Onm94oWNfx7ghZ+Up
cgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQJBAKKrMf/ndDqv7mcgXMaM
sDgRc+AqEnCybAIdUXHgDLRSolzH36lkg6/jrr8S1G/e7QdK2yvpVgaP/KH0zReo
nMECIQDdXX1vtzxgX+zv8DTNHN3m0StHuJHGC0oaOsDOX06IZQIhAMJ7dGy8XUGy
39INUFBneNc0I4QKxG31jIs6tOe/MiixAiA9GJiORNx9HPygHIP2OIlmM0TmvqI9
LtB8/MpKKzPZoQIgGQfwtSoNSq5uFkf2ZVLb/77LL2x/WbO38heNPyKhnxECIH1T
PbQ839hbekzuV+y8Me+JSUHgybVMg9BDzRXwON7f
-----END RSA PRIVATE KEY-----
EOT;

        $verificationKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKgrmaZQsaEXrlNahrSKzKwWOgEt0SSF
lv+Onm94oWNfx7ghZ+UpcgTwFl+oNMa/AbpO2a6fTuj558/Z0SlWFdUCAwEAAQ==
-----END PUBLIC KEY-----
EOT;

        /** @var SessionInterface|MockObject $session */
        $session = $this->getMockByCalls(SessionInterface::class, [
            Call::create('toArray')->with()->willReturn(['key' => 'value']),
        ]);

        /** @var ResponseInterface|MockObject $response */
        $response = $this->getMockByCalls(ResponseInterface::class, [
            Call::create('getHeader')->with('Set-Cookie')->willReturn([]),
            Call::create('withoutHeader')->with('Set-Cookie')->willReturnSelf(),
            Call::create('withAddedHeader')
                ->with(
                    'Set-Cookie',
                    'slsession=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE1NTQ5MjY0MDAsImV4cCI6MTU1NDkyNzYwMCwic2'
                        .'Vzc2lvbi1kYXRhIjp7ImtleSI6InZhbHVlIn19.c0KJQiS_XvZYLqlO08JVMTA5zsYiZkbcUvc4CY7NPP2eoARaOvkf'
                        .'Wbfnetct-cCHDaOGgzYmBUbemv_0Ef6DRA; Path=/; Expires=Wed, 10 Apr 2019 20:20:00 GMT; Secure; '
                        .'HttpOnly; SameSite=Lax'
                )
                ->willReturnSelf(),
        ]);

        $sessionPersistence = new StoragelessSessionPersistence(
            new Signer\Rsa\Sha256(),
            $signatureKey,
            $verificationKey,
            SetCookie::create(StoragelessSessionPersistence::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            1200,
            new FrozenClock($date)
        );

        $sessionPersistence->persistSession($session, $response);
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    private function getPropertyValue(object $object, string $property)
    {
        $reflectionProperty = new \ReflectionProperty($object, $property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }
}
