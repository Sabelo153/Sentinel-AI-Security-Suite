<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class PersonRepository extends Repository
{
    private const SAFE_COLUMNS = 'person_id, company_id, first_name, last_name, id_number, passport_number, phone, email, gender, date_of_birth, created_at, updated_at';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'people';
    }

    /** @return array<int, array<string, mixed>> */
    public function listForCompany(): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM people WHERE company_id = :company_id ORDER BY person_id',
            ['company_id' => $companyId],
        );
    }

    /** @return array<string, mixed>|null */
    public function findByIdForCompany(string $personId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::SAFE_COLUMNS . ' FROM people WHERE person_id = :person_id AND company_id = :company_id LIMIT 1',
            ['person_id' => $personId, 'company_id' => $companyId],
        );
    }

    /** @param array{first_name: string, last_name: string, id_number?: string|null, passport_number?: string|null, phone?: string|null, email?: string|null, gender?: string|null, date_of_birth?: string|null} $data @return array<string, mixed> */
    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO people (company_id, first_name, last_name, id_number, passport_number, phone, email, gender, date_of_birth) '
            . 'VALUES (:company_id, :first_name, :last_name, :id_number, :passport_number, :phone, :email, :gender, :date_of_birth)',
            [
                'company_id' => $companyId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'id_number' => $data['id_number'] ?? null,
                'passport_number' => $data['passport_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
            ],
        );

        $personId = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($personId);
        if ($created === null) {
            throw new \RuntimeException('Created person could not be loaded.');
        }

        return $created;
    }

    /** @param array{first_name?: string, last_name?: string, id_number?: string|null, passport_number?: string|null, phone?: string|null, email?: string|null, gender?: string|null, date_of_birth?: string|null} $data @return array<string, mixed>|null */
    public function updateForCompany(string $personId, array $data): ?array
    {
        $companyId = $this->requireCompanyId();
        $assignments = [];
        $params = ['person_id' => $personId, 'company_id' => $companyId];

        foreach (['first_name', 'last_name', 'id_number', 'passport_number', 'phone', 'email', 'gender', 'date_of_birth'] as $column) {
            if (array_key_exists($column, $data)) {
                $assignments[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($assignments !== []) {
            $this->db->execute(
                'UPDATE people SET ' . implode(', ', $assignments) . ' WHERE person_id = :person_id AND company_id = :company_id',
                $params,
            );
        }

        return $this->findByIdForCompany($personId);
    }
}