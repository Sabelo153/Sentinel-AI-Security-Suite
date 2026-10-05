<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\PatrolRouteRepository;

final class PatrolRouteManagementService
{
    private const STATUSES = ['ACTIVE', 'INACTIVE', 'ARCHIVED'];
    private const FIELDS = ['site_id', 'post_id', 'route_name', 'route_code', 'expected_duration_minutes', 'status'];

    public function __construct(private readonly PatrolRouteRepository $routes)
    {
    }

    public function listRoutes(): array
    {
        try {
            return $this->routes->listForCompany();
        } catch (PDOException $e) {
            throw new HttpException(503, 'Patrol route service unavailable.', 'patrol_route_service_unavailable', $e);
        }
    }

    public function getRoute(string $routeId): array
    {
        try {
            $route = $this->routes->findByIdForCompany($routeId);
        } catch (PDOException $e) {
            throw new HttpException(503, 'Patrol route service unavailable.', 'patrol_route_service_unavailable', $e);
        }

        if ($route === null) {
            throw HttpException::notFound('Patrol route not found.', 'patrol_route_not_found');
        }

        return $route;
    }

    public function createRoute(array $input): array
    {
        $data = $this->validate($input, false);
        $this->validateReferences($data);

        try {
            if ($this->routes->routeCodeExists((string) $data['site_id'], $data['route_code'] ?? null)) {
                throw HttpException::conflict('Patrol route already exists.', 'patrol_route_conflict');
            }

            return $this->routes->createForCompany($data);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw HttpException::conflict('Patrol route could not be created.', 'patrol_route_conflict');
        }
    }

    public function updateRoute(string $routeId, array $input): array
    {
        $existing = $this->getRoute($routeId);
        $patch = $this->validate($input, true);
        $effective = array_merge($existing, $patch);
        $this->validateReferences($effective);

        try {
            if ($this->routes->routeCodeExists((string) $effective['site_id'], $effective['route_code'] ?? null, $routeId)) {
                throw HttpException::conflict('Patrol route already exists.', 'patrol_route_conflict');
            }

            $route = $this->routes->updateForCompany($routeId, $patch);
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw HttpException::conflict('Patrol route could not be updated.', 'patrol_route_conflict');
        }

        if ($route === null) {
            throw HttpException::notFound('Patrol route not found.', 'patrol_route_not_found');
        }

        return $route;
    }

    private function validate(array $input, bool $update): array
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field];
            }
        }

        foreach (['site_id', 'post_id'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = $this->positive($data[$field], $field);
            }
        }

        if (array_key_exists('route_name', $data) && (!is_string($data['route_name']) || trim($data['route_name']) === '' || strlen($data['route_name']) > 255)) {
            throw HttpException::unprocessable('Route name is invalid.', 'invalid_route_name');
        }
        if (array_key_exists('route_code', $data) && $data['route_code'] !== null && (!is_string($data['route_code']) || strlen($data['route_code']) > 100 || trim($data['route_code']) === '')) {
            throw HttpException::unprocessable('Route code is invalid.', 'invalid_route_code');
        }
        if (array_key_exists('expected_duration_minutes', $data) && $data['expected_duration_minutes'] !== null) {
            $data['expected_duration_minutes'] = $this->nonNegative($data['expected_duration_minutes'], 'expected_duration_minutes');
        }
        if (array_key_exists('status', $data) && (!is_string($data['status']) || !in_array($data['status'], self::STATUSES, true))) {
            throw HttpException::unprocessable('Patrol route status is invalid.', 'invalid_patrol_route_status');
        }
        if (!$update) {
            foreach (['site_id', 'route_name'] as $required) {
                if (!array_key_exists($required, $data)) {
                    throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $required)) . ' is required.', 'invalid_' . $required);
                }
            }
            $data['status'] ??= 'ACTIVE';
        }

        return $data;
    }

    private function validateReferences(array $data): void
    {
        try {
            if (!$this->routes->siteBelongsToCompany((string) $data['site_id'])) {
                throw HttpException::notFound('Site not found.', 'site_not_found');
            }
            if (isset($data['post_id']) && $data['post_id'] !== null && !$this->routes->postBelongsToSite((string) $data['post_id'], (string) $data['site_id'])) {
                throw HttpException::unprocessable('Patrol route relationship is invalid.', 'invalid_patrol_route_relationship');
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (PDOException $e) {
            throw new HttpException(503, 'Patrol route service unavailable.', 'patrol_route_service_unavailable', $e);
        }
    }

    private function positive(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return (int) $value;
    }

    private function nonNegative(mixed $value, string $field): int
    {
        if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 0) {
            throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
        }

        return (int) $value;
    }
}
