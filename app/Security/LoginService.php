<?php

declare(strict_types=1);

namespace Sentinel\Security;

final class LoginService
{
    public function __construct(
        private readonly PasswordAuthenticator $authenticator,
        private readonly JwtService $jwt,
        private readonly ?\Sentinel\Services\SessionSecurityService $security = null,
    ) {
    }

    public function login(string $identifier, string $password, string $code = '', string $label = 'API'): string
    {
        $user = $this->authenticator->authenticate($identifier, $password);
        $verified = $this->security?->verifyLogin($user['user_id'], $code) ?? false;

        return $this->jwt->createToken($user['user_id'], $verified, $label);
    }
}
