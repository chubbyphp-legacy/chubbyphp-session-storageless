<?php

declare(strict_types=1);

namespace Chubbyphp\Session\Storageless;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Session\Session;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Session\SessionPersistenceInterface;

final class StoragelessSessionPersistence implements SessionPersistenceInterface
{
    public const SESSION_CLAIM = 'session-data';
    public const DEFAULT_COOKIE = 'slsession';

    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var string
     */
    private $signatureKey;

    /**
     * @var string
     */
    private $verificationKey;

    /**
     * @var int
     */
    private $expirationTime;

    /**
     * @var Parser
     */
    private $tokenParser;

    /**
     * @var SetCookie
     */
    private $defaultCookie;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param Signer    $signer
     * @param string    $signatureKey
     * @param string    $verificationKey
     * @param SetCookie $defaultCookie
     * @param Parser    $tokenParser
     * @param int       $expirationTime
     * @param Clock     $clock
     */
    public function __construct(
        Signer $signer,
        string $signatureKey,
        string $verificationKey,
        SetCookie $defaultCookie,
        Parser $tokenParser,
        int $expirationTime,
        Clock $clock
    ) {
        $this->signer = $signer;
        $this->signatureKey = $signatureKey;
        $this->verificationKey = $verificationKey;
        $this->tokenParser = $tokenParser;
        $this->defaultCookie = clone $defaultCookie;
        $this->expirationTime = $expirationTime;
        $this->clock = $clock;
    }

    /**
     * @param string $symmetricKey
     * @param int    $expirationTime
     *
     * @return self
     */
    public static function fromSymmetricKeyDefaults(string $symmetricKey, int $expirationTime): self
    {
        return new self(
            new Signer\Hmac\Sha256(),
            $symmetricKey,
            $symmetricKey,
            SetCookie::create(self::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            $expirationTime,
            new SystemClock()
        );
    }

    /**
     * @param string $privateRsaKey
     * @param string $publicRsaKey
     * @param int    $expirationTime
     *
     * @return self
     */
    public static function fromAsymmetricKeyDefaults(
        string $privateRsaKey,
        string $publicRsaKey,
        int $expirationTime
    ): self {
        return new self(
            new Signer\Rsa\Sha256(),
            $privateRsaKey,
            $publicRsaKey,
            SetCookie::create(self::DEFAULT_COOKIE)
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::lax())
                ->withPath('/'),
            new Parser(),
            $expirationTime,
            new SystemClock()
        );
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return SessionInterface
     */
    public function initializeSessionFromRequest(ServerRequestInterface $request): SessionInterface
    {
        $token = $this->parseToken($request);

        $session = $this->extractSession($token);

        return $session;
    }

    /**
     * @param SessionInterface  $session
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function persistSession(SessionInterface $session, ResponseInterface $response): ResponseInterface
    {
        return $this->appendToken($session, $response);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return Token|null
     */
    private function parseToken(ServerRequestInterface $request): ?Token
    {
        $cookies = $request->getCookieParams();
        $cookieName = $this->defaultCookie->getName();

        if (!isset($cookies[$cookieName])) {
            return null;
        }

        try {
            $token = $this->tokenParser->parse($cookies[$cookieName]);
        } catch (\InvalidArgumentException $invalidToken) {
            return null;
        }

        if (!$token->validate(new ValidationData())) {
            return null;
        }

        return $token;
    }

    /**
     * @param Token|null $token
     *
     * @return SessionInterface
     *
     * @throws \OutOfBoundsException
     */
    private function extractSession(?Token $token): SessionInterface
    {
        if (!$token) {
            return new Session([]);
        }

        try {
            if (!$token->verify($this->signer, $this->verificationKey)) {
                return new Session([]);
            }

            return new Session((array) $token->getClaim(self::SESSION_CLAIM, new \stdClass()));
        } catch (\BadMethodCallException $invalidToken) {
            return new Session([]);
        }
    }

    /**
     * @param SessionInterface  $session
     * @param ResponseInterface $response
     *
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    private function appendToken(SessionInterface $session, ResponseInterface $response): ResponseInterface
    {
        $isEmpty = [] === $data = $session->toArray();

        if ($isEmpty) {
            return FigResponseCookies::set($response, $this->getExpirationCookie());
        }

        return FigResponseCookies::set($response, $this->getTokenCookie($data));
    }

    /**
     * @param array $data
     *
     * @return SetCookie
     *
     * @throws \BadMethodCallException
     */
    private function getTokenCookie(array $data): SetCookie
    {
        $timestamp = $this->timestamp();

        return $this
            ->defaultCookie
            ->withValue(
                (new Builder())
                    ->setIssuedAt($timestamp)
                    ->setExpiration($timestamp + $this->expirationTime)
                    ->set(self::SESSION_CLAIM, $data)
                    ->sign($this->signer, $this->signatureKey)
                    ->getToken()
                    ->__toString()
            )
            ->withExpires($timestamp + $this->expirationTime);
    }

    /**
     * @return SetCookie
     */
    private function getExpirationCookie(): SetCookie
    {
        $expirationDate = $this->clock->now()->modify('-30 days');

        return $this
            ->defaultCookie
            ->withValue(null)
            ->withExpires($expirationDate->getTimestamp());
    }

    /**
     * @return int
     */
    private function timestamp(): int
    {
        return $this->clock->now()->getTimestamp();
    }
}
