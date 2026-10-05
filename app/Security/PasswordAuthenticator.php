<?php

declare(strict_types=1);

namespace Sentinel\Security;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\UserRepository;

final class PasswordAuthenticator
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /** @return array{user_id: string} */
    public function authenticate(string $identifier, string $password): array
    {
        if ($identifier === '' || $password === '') {
            throw $this->authenticationFailure();
        }

        try {
            $user = $this->users->findForAuthentication($identifier);
        } catch (PDOException $exception) {
            throw new HttpException(503, 'Authentication service unavailable.', 'authentication_service_unavailable', $exception);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new HttpException(503, 'Authentication service unavailable.', 'authentication_service_unavailable', $exception);
        }

        if ($user === null) {
            throw $this->authenticationFailure();
        }

        $securityState = $this->users->findSecurityState((string) $user['user_id']);
        $lockedUntil = isset($securityState['locked_until']) ? strtotime((string) $securityState['locked_until']) : false;
        $locked = (string) ($user['status'] ?? '') !== 'ACTIVE' || ($lockedUntil !== false && $lockedUntil > time());
        if ($locked || !is_string($user['password_hash'] ?? null) || !password_verify($password, $user['password_hash'])) {
            $this->users->recordFailedLogin((string) $user['user_id'], $locked ? 'locked_account' : 'invalid_credentials');
            throw $this->authenticationFailure();
        }

        $this->users->recordSuccessfulLogin((string) $user['user_id']);

        return ['user_id' => (string) $user['user_id']];
    }

    private function authenticationFailure(): HttpException
    {
        return HttpException::unauthorized('Authentication failed.', 'authentication_failed');
    }
}