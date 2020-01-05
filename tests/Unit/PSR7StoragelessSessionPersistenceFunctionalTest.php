<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Session\Storageless\Unit;

use Chubbyphp\Session\Storageless\PSR7StoragelessSessionPersistence;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Stratigility\MiddlewarePipe;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Mezzio\Session\SessionInterface as MezzioSessionInterface;
use Mezzio\Session\SessionMiddleware as MezzioSessionMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware as PSR7SessionMiddleware;

/**
 * @coversNothing
 *
 * @internal
 */
final class PSR7StoragelessSessionPersistenceFunctionalTest extends TestCase
{
    public function testNewWithoutAccessToTheSession(): void
    {
        $request = new ServerRequest([
            'HTTPS' => 'on',
        ]);

        $response = new Response();

        $requestHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

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

        $middlewarePipe = new MiddlewarePipe();
        $middlewarePipe->pipe(PSR7SessionMiddleware::fromAsymmetricKeyDefaults(
            $signatureKey,
            $verificationKey,
            1200
        ));
        $middlewarePipe->pipe(new MezzioSessionMiddleware(new PSR7StoragelessSessionPersistence()));

        $response = $middlewarePipe->process($request, $requestHandler);

        self::assertSame('', $response->getHeaderLine('Set-Cookie'));
    }

    public function testNewWithAccessToTheSession(): void
    {
        $request = new ServerRequest([
            'HTTPS' => 'on',
        ]);

        $response = new Response();

        $requestHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                /** @var MezzioSessionInterface $session */
                $session = $request->getAttribute(MezzioSessionMiddleware::SESSION_ATTRIBUTE);
                $session->set('key', 'value');

                TestCase::assertInstanceOf(MezzioSessionInterface::class, $session);

                return new Response();
            }
        };

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

        $middlewarePipe = new MiddlewarePipe();
        $middlewarePipe->pipe(PSR7SessionMiddleware::fromAsymmetricKeyDefaults(
            $signatureKey,
            $verificationKey,
            1200
        ));
        $middlewarePipe->pipe(new MezzioSessionMiddleware(new PSR7StoragelessSessionPersistence()));

        $response = $middlewarePipe->process($request, $requestHandler);

        $cookie = $response->getHeaderLine('Set-Cookie');

        $matches = [];

        preg_match('/^slsession=([^;]+);/', $cookie, $matches);

        $parser = new Parser();

        $token = $parser->parse($matches[1]);

        $data = (array) $token->getClaim(PSR7SessionMiddleware::SESSION_CLAIM, new \stdClass());

        self::assertSame(['key' => 'value'], $data);
    }

    public function testExistingWithAccessToTheSession(): void
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

        $signer = new Sha256();

        $timestamp = time();

        $jwt = (new Builder())
            ->setIssuedAt($timestamp)
            ->setExpiration($timestamp + 1200)
            ->set(PSR7SessionMiddleware::SESSION_CLAIM, ['key' => 'value'])
            ->sign($signer, $signatureKey)
            ->getToken()
            ->__toString()
        ;

        $request = new ServerRequest(
            ['HTTPS' => 'on'],
            [],
            null,
            null,
            'php://input',
            [],
            [PSR7SessionMiddleware::DEFAULT_COOKIE => $jwt]
        );

        $response = new Response();

        $requestHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                /** @var MezzioSessionInterface $session */
                $session = $request->getAttribute(MezzioSessionMiddleware::SESSION_ATTRIBUTE);

                TestCase::assertInstanceOf(MezzioSessionInterface::class, $session);

                TestCase::assertSame(['key' => 'value'], $session->toArray());

                $session->set('key', 'value2');

                return new Response();
            }
        };

        $middlewarePipe = new MiddlewarePipe();
        $middlewarePipe->pipe(PSR7SessionMiddleware::fromAsymmetricKeyDefaults(
            $signatureKey,
            $verificationKey,
            1200
        ));
        $middlewarePipe->pipe(new MezzioSessionMiddleware(new PSR7StoragelessSessionPersistence()));

        $response = $middlewarePipe->process($request, $requestHandler);

        $cookie = $response->getHeaderLine('Set-Cookie');

        $matches = [];

        preg_match('/^slsession=([^;]+);/', $cookie, $matches);

        $parser = new Parser();

        $token = $parser->parse($matches[1]);

        $data = (array) $token->getClaim(PSR7SessionMiddleware::SESSION_CLAIM, new \stdClass());

        self::assertSame(['key' => 'value2'], $data);
    }
}
