<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\RuntimeContext;

/** Authentication projection for the canonical users table. */
final class UserRepository extends Repository
{
	private const SAFE_COLUMNS = 'user_id, company_id, username, email, status';

	public function __construct(\Sentinel\Core\Database $db, ?RuntimeContext $context = null)
	{
		parent::__construct($db, $context);
	}

	protected function table(): string
	{
		return 'users';
	}

	/**
	 * Find by either canonical login identifier without applying tenant or
	 * authorization rules. The returned projection is deliberately minimal.
	 *
	 * @return array{user_id: mixed, username: mixed, email: mixed, password_hash: mixed, status: mixed}|null
	 */
	public function findForAuthentication(string $identifier): ?array
	{
		$row = $this->db->fetch(
			'SELECT user_id, username, email, password_hash, status FROM users '
			. 'WHERE email = :email_identifier OR username = :username_identifier LIMIT 1',
			['email_identifier' => $identifier, 'username_identifier' => $identifier],
		);

		return $row;
	}

	/** @return array<string, mixed>|null */
	public function findSecurityState(string $userId): ?array
	{
		try {
			return $this->db->fetch(
				'SELECT user_id, company_id, status, failed_login_attempts, locked_until, password_hash FROM users WHERE user_id = :user_id LIMIT 1',
				['user_id' => $userId],
			);
		} catch (\PDOException) {
			return null;
		}
	}

	/** @return array<string, mixed>|null */
	public function findCredentialsForCompany(string $userId): ?array
	{
		$companyId = $this->requireCompanyId();

		return $this->db->fetch(
			'SELECT user_id, company_id, password_hash, status FROM users WHERE user_id = :user_id AND company_id = :company_id LIMIT 1',
			['user_id' => $userId, 'company_id' => $companyId],
		);
	}

	public function recordFailedLogin(string $userId, string $reason, int $threshold = 5, int $lockMinutes = 15): void
	{
		$state = $this->findSecurityState($userId);
		if ($state === null || !array_key_exists('failed_login_attempts', $state)) {
			return;
		}
		$attempts = (int) $state['failed_login_attempts'] + 1;
		$lockedUntil = $attempts >= $threshold ? date('Y-m-d H:i:s', time() + ($lockMinutes * 60)) : null;
		$this->db->execute(
			'UPDATE users SET failed_login_attempts = :attempts, locked_until = :locked_until, status = CASE WHEN :locked_until_status IS NOT NULL THEN :locked_status ELSE status END WHERE user_id = :user_id',
			['attempts' => $attempts, 'locked_until' => $lockedUntil, 'locked_until_status' => $lockedUntil, 'locked_status' => 'LOCKED', 'user_id' => $userId],
		);
		$this->recordLoginHistory($state, 'FAILED', $reason);
	}

	public function recordSuccessfulLogin(string $userId): void
	{
		$state = $this->findSecurityState($userId);
		if ($state === null || !array_key_exists('failed_login_attempts', $state)) {
			return;
		}
		$this->db->execute(
			'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = CURRENT_TIMESTAMP WHERE user_id = :user_id AND status = :status',
			['user_id' => $userId, 'status' => 'ACTIVE'],
		);
		$this->recordLoginHistory($state, 'SUCCESS', null);
	}

	public function changePasswordForCompany(string $userId, string $passwordHash): bool
	{
		$companyId = $this->requireCompanyId();
		return $this->db->execute(
			'UPDATE users SET password_hash = :password_hash, password_last_changed_at = CURRENT_TIMESTAMP, failed_login_attempts = 0, locked_until = NULL, status = :status WHERE user_id = :user_id AND company_id = :company_id AND status <> :archived',
			['password_hash' => $passwordHash, 'status' => 'ACTIVE', 'user_id' => $userId, 'company_id' => $companyId, 'archived' => 'ARCHIVED'],
		) > 0;
	}

	public function unlockForCompany(string $userId): bool
	{
		$companyId = $this->requireCompanyId();
		return $this->db->execute(
			'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, status = :status WHERE user_id = :user_id AND company_id = :company_id AND status = :locked',
			['status' => 'ACTIVE', 'user_id' => $userId, 'company_id' => $companyId, 'locked' => 'LOCKED'],
		) > 0;
	}

	/** @param array<string, mixed> $state */
	private function recordLoginHistory(array $state, string $status, ?string $reason): void
	{
		try {
			$this->db->execute(
				'INSERT INTO login_history (company_id, user_id, login_status, failure_reason) VALUES (:company_id, :user_id, :login_status, :failure_reason)',
				['company_id' => $state['company_id'] ?? null, 'user_id' => $state['user_id'] ?? null, 'login_status' => $status, 'failure_reason' => $reason],
			);
		} catch (\PDOException) {
		}
	}

	/** Resolve the authenticated user's active company from server-side state. */
	public function findCompanyIdByUserId(string $userId): ?string
	{
		$row = $this->db->fetch(
			'SELECT company_id FROM users WHERE user_id = :user_id AND status = :status LIMIT 1',
			['user_id' => $userId, 'status' => 'ACTIVE'],
		);

		if ($row === null || !isset($row['company_id'])) {
			return null;
		}

		return (string) $row['company_id'];
	}

	/** @return array<string, mixed>|null */
	public function findByIdForCompany(string $userId): ?array
	{
		$companyId = $this->requireCompanyId();

		return $this->db->fetch(
			'SELECT ' . self::SAFE_COLUMNS . ' FROM users WHERE user_id = :user_id AND company_id = :company_id LIMIT 1',
			['user_id' => $userId, 'company_id' => $companyId],
		);
	}

	/** @return array<int, array<string, mixed>> */
	public function listForCompany(): array
	{
		$companyId = $this->requireCompanyId();

		return $this->db->fetchAll(
			'SELECT ' . self::SAFE_COLUMNS . ' FROM users WHERE company_id = :company_id ORDER BY user_id',
			['company_id' => $companyId],
		);
	}

	public function employeeBelongsToCompany(int $employeeId): bool
	{
		$companyId = $this->requireCompanyId();
		$row = $this->db->fetch(
			'SELECT employee_id FROM employees WHERE employee_id = :employee_id AND company_id = :company_id LIMIT 1',
			['employee_id' => $employeeId, 'company_id' => $companyId],
		);

		return $row !== null;
	}

	/** @param array{username: string, email: string, password_hash: string, employee_id: int} $data @return array<string, mixed> */
	public function createForCompany(array $data): array
	{
		$companyId = $this->requireCompanyId();
		$this->db->execute(
			'INSERT INTO users (user_uuid, company_id, employee_id, username, email, password_hash, status) '
			. 'VALUES (:user_uuid, :company_id, :employee_id, :username, :email, :password_hash, :status)',
			[
				'user_uuid' => uuid4(),
				'company_id' => $companyId,
				'employee_id' => $data['employee_id'],
				'username' => $data['username'],
				'email' => $data['email'],
				'password_hash' => $data['password_hash'],
				'status' => 'ACTIVE',
			],
		);

		$userId = (string) $this->db->connection()->lastInsertId();
		$created = $this->findByIdForCompany($userId);
		if ($created === null) {
			throw new \RuntimeException('Created user could not be loaded.');
		}

		return $created;
	}

	/** @param array{username?: string, email?: string, status?: string} $data @return array<string, mixed>|null */
	public function updateForCompany(string $userId, array $data): ?array
	{
		$companyId = $this->requireCompanyId();
		$assignments = [];
		$params = ['user_id' => $userId, 'company_id' => $companyId];

		foreach (['username', 'email', 'status'] as $column) {
			if (array_key_exists($column, $data)) {
				$assignments[] = $column . ' = :' . $column;
				$params[$column] = $data[$column];
			}
		}

		if ($assignments !== []) {
			$this->db->execute(
				'UPDATE users SET ' . implode(', ', $assignments) . ' WHERE user_id = :user_id AND company_id = :company_id',
				$params,
			);
		}

		return $this->findByIdForCompany($userId);
	}
}
