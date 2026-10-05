<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\UserRepository;

final class UserManagementService
{
	private const STATUSES = ['ACTIVE', 'LOCKED', 'DISABLED', 'ARCHIVED'];

	public function __construct(private readonly UserRepository $users, private readonly ?SubscriptionService $subscriptions = null)
	{
	}

	/** @return array<int, array<string, mixed>> */
	public function listUsers(): array
	{
		return array_map([$this, 'safeUser'], $this->users->listForCompany());
	}

	/** @return array<string, mixed> */
	public function getUser(string $userId): array
	{
		$user = $this->users->findByIdForCompany($userId);
		if ($user === null) {
			throw HttpException::notFound('User not found.', 'user_not_found');
		}

		return $this->safeUser($user);
	}

	/** @return array<string, mixed> */
	public function createUser(array $input): array
	{
		$this->subscriptions?->assertCanCreate('users');
		$username = $this->requiredString($input, 'username');
		$email = strtolower($this->requiredString($input, 'email'));
		$password = $this->requiredString($input, 'password');
		$employeeId = $input['employee_id'] ?? null;

		if (!is_int($employeeId) && !(is_string($employeeId) && ctype_digit($employeeId))) {
			throw HttpException::unprocessable('employee_id is required.', 'invalid_employee_id');
		}
		if ((int) $employeeId < 1) {
			throw HttpException::unprocessable('employee_id is invalid.', 'invalid_employee_id');
		}
		if (!$this->users->employeeBelongsToCompany((int) $employeeId)) {
			throw HttpException::notFound('Employee not found.', 'employee_not_found');
		}

		$this->validateUsername($username);
		$this->validateEmail($email);

		try {
			return $this->safeUser($this->users->createForCompany([
				'username' => $username,
				'email' => $email,
				'password_hash' => password_hash($password, PASSWORD_DEFAULT),
				'employee_id' => (int) $employeeId,
			]));
		} catch (PDOException $exception) {
			throw HttpException::conflict('User could not be created.', 'user_conflict');
		} catch (HttpException $exception) {
			throw $exception;
		} catch (\Throwable $exception) {
			throw new HttpException(503, 'User service unavailable.', 'user_service_unavailable', $exception);
		}
	}

	/** @return array<string, mixed> */
	public function updateUser(string $userId, array $input): array
	{
		$user = $this->users->findByIdForCompany($userId);
		if ($user === null) {
			throw HttpException::notFound('User not found.', 'user_not_found');
		}

		$allowed = array_intersect_key($input, array_flip(['username', 'email', 'status']));
		if ($allowed === []) {
			throw HttpException::unprocessable('No editable user fields were provided.', 'invalid_user_update');
		}

		if (array_key_exists('username', $allowed)) {
			$allowed['username'] = $this->requiredString($allowed, 'username');
			$this->validateUsername($allowed['username']);
		}
		if (array_key_exists('email', $allowed)) {
			$allowed['email'] = strtolower($this->requiredString($allowed, 'email'));
			$this->validateEmail($allowed['email']);
		}
		if (array_key_exists('status', $allowed)) {
			$status = $this->requiredString($allowed, 'status');
			if (!in_array($status, self::STATUSES, true)) {
				throw HttpException::unprocessable('Status is invalid.', 'invalid_user_status');
			}
			$allowed['status'] = $status;
		}

		try {
			$updated = $this->users->updateForCompany($userId, $allowed);
		} catch (PDOException $exception) {
			throw HttpException::conflict('User could not be updated.', 'user_conflict');
		}

		if ($updated === null) {
			throw HttpException::notFound('User not found.', 'user_not_found');
		}

		return $this->safeUser($updated);
	}

	private function requiredString(array $input, string $field): string
	{
		$value = $input[$field] ?? null;
		if (!is_string($value) || trim($value) === '') {
			throw HttpException::unprocessable(ucfirst($field) . ' is required.', 'invalid_' . $field);
		}

		return trim($value);
	}

	private function validateUsername(string $username): void
	{
		if (mb_strlen($username) > 100) {
			throw HttpException::unprocessable('Username is too long.', 'invalid_username');
		}
	}

	private function validateEmail(string $email): void
	{
		if (mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			throw HttpException::unprocessable('Email is invalid.', 'invalid_email');
		}
	}

	/** @param array<string, mixed> $user @return array<string, mixed> */
	private function safeUser(array $user): array
	{
		return array_intersect_key($user, array_flip(['user_id', 'username', 'email', 'status', 'company_id']));
	}
}
