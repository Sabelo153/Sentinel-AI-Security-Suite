<?php

declare(strict_types=1);

namespace Sentinel\Security;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Core\RuntimeContext;
use Sentinel\Repositories\UserRepository;

final class TenantContextService
{
	public function __construct(
		private readonly UserRepository $users,
		private readonly RuntimeContext $context,
	) {
	}

	public function resolveForAuthenticatedUser(): string
	{
		$userId = $this->context->user();
		if (!is_string($userId) || $userId === '') {
			throw HttpException::unauthorized('Authentication failed.', 'authentication_failed');
		}

		try {
			$companyId = $this->users->findCompanyIdByUserId($userId);
		} catch (PDOException $exception) {
			throw new HttpException(503, 'Tenant service unavailable.', 'tenant_service_unavailable', $exception);
		} catch (HttpException $exception) {
			if ($exception->statusCode() === 503) {
				throw new HttpException(503, 'Tenant service unavailable.', 'tenant_service_unavailable', $exception);
			}

			throw $exception;
		} catch (\Throwable $exception) {
			throw new HttpException(503, 'Tenant service unavailable.', 'tenant_service_unavailable', $exception);
		}

		if ($companyId === null || filter_var($companyId, FILTER_VALIDATE_INT) === false || (int) $companyId < 1) {
			throw HttpException::forbidden('Access denied.', 'forbidden');
		}

		$this->context->setTenantId((int) $companyId);

		return $companyId;
	}
}