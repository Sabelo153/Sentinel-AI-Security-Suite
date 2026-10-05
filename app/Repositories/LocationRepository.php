<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class LocationRepository extends Repository
{
    private const COLUMNS = 'g.gps_location_id, g.company_id, g.employee_id, g.user_id, g.business_object_id, g.latitude, g.longitude, g.accuracy, g.heading, g.speed, g.altitude, g.provider, g.battery_level, g.signal_strength, g.device_id, g.mock_location, g.confidence, g.captured_at, g.created_at';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'gps_locations';
    }

    public function listForCompany(array $filters = []): array
    {
        $companyId = $this->requireCompanyId();
        [$where, $parameters] = $this->filterSql($filters, $companyId);
        $limit = isset($filters['limit']) ? ' LIMIT ' . (int) $filters['limit'] : '';

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM gps_locations g WHERE ' . implode(' AND ', $where)
            . ' ORDER BY g.captured_at DESC, g.gps_location_id DESC' . $limit,
            $parameters,
        );
    }

    public function listForEmployee(string $employeeId, array $filters = []): array
    {
        return $this->listForCompany([...$filters, 'employee_id' => $employeeId]);
    }

    public function latestForEmployee(string $employeeId): ?array
    {
        $locations = $this->listForEmployee($employeeId, ['limit' => 1]);

        return $locations[0] ?? null;
    }

    public function employeeBelongsToCompany(string $employeeId): bool
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT employee_id FROM employees WHERE employee_id = :employee_id AND company_id = :company_id LIMIT 1',
            ['employee_id' => $employeeId, 'company_id' => $companyId],
        ) !== null;
    }

    public function userBelongsToCompany(string $userId): bool
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT user_id FROM users WHERE user_id = :user_id AND company_id = :company_id LIMIT 1',
            ['user_id' => $userId, 'company_id' => $companyId],
        ) !== null;
    }

    public function businessObjectBelongsToCompany(string $businessObjectId): bool
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT business_object_id FROM business_objects WHERE business_object_id = :business_object_id AND company_id = :company_id LIMIT 1',
            ['business_object_id' => $businessObjectId, 'company_id' => $companyId],
        ) !== null;
    }

    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $columns = ['company_id', 'employee_id', 'user_id', 'business_object_id', 'latitude', 'longitude', 'accuracy', 'heading', 'speed', 'altitude', 'provider', 'battery_level', 'signal_strength', 'device_id', 'mock_location', 'confidence', 'captured_at'];
        $values = [];
        $parameters = ['company_id' => $companyId];
        foreach ($columns as $column) {
            if ($column === 'company_id') {
                $values[] = ':company_id';
                continue;
            }
            $values[] = ':' . $column;
            $parameters[$column] = $data[$column] ?? null;
        }
        $this->db->execute(
            'INSERT INTO gps_locations (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')',
            $parameters,
        );

        $id = (string) $this->db->connection()->lastInsertId();
        $created = $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM gps_locations g WHERE g.gps_location_id = :id AND g.company_id = :company_id LIMIT 1',
            ['id' => $id, 'company_id' => $companyId],
        );
        if ($created === null) {
            throw new \RuntimeException('Created location could not be loaded.');
        }

        return $created;
    }

    /** @return array{0: list<string>, 1: array<string, mixed>} */
    private function filterSql(array $filters, int $companyId): array
    {
        $where = ['g.company_id = :company_id'];
        $parameters = ['company_id' => $companyId];
        foreach (['employee_id', 'user_id', 'business_object_id'] as $field) {
            if (array_key_exists($field, $filters)) {
                $where[] = 'g.' . $field . ' = :' . $field;
                $parameters[$field] = $filters[$field];
            }
        }
        if (isset($filters['captured_from'])) {
            $where[] = 'g.captured_at >= :captured_from';
            $parameters['captured_from'] = $filters['captured_from'];
        }
        if (isset($filters['captured_to'])) {
            $where[] = 'g.captured_at <= :captured_to';
            $parameters['captured_to'] = $filters['captured_to'];
        }

        return [$where, $parameters];
    }
}