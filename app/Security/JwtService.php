<?php

declare(strict_types=1);

namespace Sentinel\Security;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Sentinel\Core\Config;
use Sentinel\Core\HttpException;

/**
 * JWT primitive service. This project currently has a symmetric secret
 * contract, so HS256 is selected explicitly rather than inventing key files.
 */
final class JwtService
{
    private const ALGORITHM = 'HS256';

    private readonly Configuration $jwt;
    private readonly string $issuer;
    private readonly int $ttl;

    public function __construct(Config $config)
    {
        $algorithm = (string) $config->get('jwt.algorithm', self::ALGORITHM);
        $secret = $config->get('jwt.secret');
        $issuer = (string) $config->get('jwt.issuer', '');
        $ttl = (int) $config->get('jwt.ttl', 0);

        if ($algorithm !== self::ALGORITHM || !is_string($secret) || $secret === '' || $issuer === '' || $ttl <= 0) {
            throw new \LogicException('JWT signing configuration is incomplete or unsupported.');
        }

        $this->issuer = $issuer;
        $this->ttl = $ttl;
        $this->jwt = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($secret));
    }

    public function createToken(string $userId): string
    {
        if ($userId === '' || preg_match('/^[A-Za-z0-9._:-]{1,255}$/', $userId) !== 1) {
            throw new \InvalidArgumentException('User identifier is invalid.');
        }

        $issuedAt = new DateTimeImmutable();
        $expiresAt = $issuedAt->modify("+{$this->ttl} seconds");
        if ($expiresAt <= $issuedAt) {
            throw new \LogicException('JWT expiration must be in the future.');
        }

        return $this->jwt->builder()
            ->issuedBy($this->issuer)
            ->relatedTo($userId)
            ->issuedAt($issuedAt)
            ->expiresAt($expiresAt)
            ->identifiedBy(uuid4())
            ->getToken($this->jwt->signer(), $this->jwt->signingKey())
            ->toString();
    }

    public function validateToken(string $token): UnencryptedToken
    {
        try {
            if ($token === '') {
                throw new \InvalidArgumentException('Token is empty.');
            }

            $parsed = $this->jwt->parser()->parse($token);
            if (!$parsed instanceof UnencryptedToken) {
                throw new \InvalidArgumentException('JWT token type is invalid.');
            }

            $this->jwt->validator()->assert(
                $parsed,
                new \Lcobucci\JWT\Validation\Constraint\SignedWith($this->jwt->signer(), $this->jwt->verificationKey()),
            );

            $claims = $parsed->claims();

            if ($parsed->headers()->get('alg') !== self::ALGORITHM
                || !$claims->has('iss')
                || !$claims->has('sub')
                || !$claims->has('iat')
                || !$claims->has('exp')
                || !$claims->has('jti')
                || !is_string($claims->get('sub'))
                || $claims->get('sub') === ''
                || $claims->get('iss') !== $this->issuer
            ) {
                throw new \InvalidArgumentException('JWT claims are invalid.');
            }

            $now = new DateTimeImmutable();
            $issuedAt = $claims->get('iat');
            $expiresAt = $claims->get('exp');
            if (!$issuedAt instanceof DateTimeImmutable || !$expiresAt instanceof DateTimeImmutable
                || $issuedAt > $now || $expiresAt <= $now
            ) {
                throw new \InvalidArgumentException('JWT time claims are invalid.');
            }

            return $parsed;
        } catch (\Throwable $exception) {
            throw new HttpException(401, 'Authentication failed.', 'authentication_failed', $exception);
        }
    }
}