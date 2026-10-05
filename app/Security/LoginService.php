<?php

declare(strict_types=1);

namespace Sentinel\Security;

final class LoginService
{
    public function __construct(
        private readonly PasswordAuthenticator $authenticator,
        private readonly JwtService $jwt,
    ) {
    }

    public function login(string $identifier, string $password): string
    {
        $user = $this->authenticator->authenticate($identifier, $password);

        return $this->jwt->createToken($user['user_id']);
    }
}