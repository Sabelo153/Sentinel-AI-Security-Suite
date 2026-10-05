<?php

declare(strict_types=1);

namespace Sentinel\Repositories;

use Sentinel\Core\Database;
use Sentinel\Core\RuntimeContext;

final class PatrolCheckpointEventRepository extends Repository
{
    private const COLUMNS = 'pce.patrol_checkpoint_event_id, pce.patrol_checkpoint_event_uuid, pce.company_id, pce.patrol_session_id, pce.patrol_checkpoint_id, pce.event_type, pce.recorded_at, pce.event_notes, pce.created_at, pc.checkpoint_name, pc.checkpoint_order';

    public function __construct(Database $db, ?RuntimeContext $context = null)
    {
        parent::__construct($db, $context);
    }

    protected function table(): string
    {
        return 'patrol_checkpoint_events';
    }

    public function listForSession(string $sessionId): array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM patrol_checkpoint_events pce '
            . 'INNER JOIN patrol_sessions ps ON ps.patrol_session_id = pce.patrol_session_id AND ps.company_id = pce.company_id AND ps.deleted_at IS NULL '
            . 'INNER JOIN patrol_checkpoints pc ON pc.patrol_checkpoint_id = pce.patrol_checkpoint_id AND pc.company_id = pce.company_id AND pc.deleted_at IS NULL '
            . 'WHERE pce.patrol_session_id = :session_id AND pce.company_id = :company_id ORDER BY pce.recorded_at, pce.patrol_checkpoint_event_id',
            ['session_id' => $sessionId, 'company_id' => $companyId],
        );
    }

    public function findByIdForCompany(string $eventId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT ' . self::COLUMNS . ' FROM patrol_checkpoint_events pce '
            . 'INNER JOIN patrol_sessions ps ON ps.patrol_session_id = pce.patrol_session_id AND ps.company_id = pce.company_id AND ps.deleted_at IS NULL '
            . 'INNER JOIN patrol_checkpoints pc ON pc.patrol_checkpoint_id = pce.patrol_checkpoint_id AND pc.company_id = pce.company_id AND pc.deleted_at IS NULL '
            . 'WHERE pce.patrol_checkpoint_event_id = :event_id AND pce.company_id = :company_id LIMIT 1',
            ['event_id' => $eventId, 'company_id' => $companyId],
        );
    }

    public function sessionDetails(string $sessionId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT patrol_session_id, patrol_route_id, status, started_at FROM patrol_sessions WHERE patrol_session_id = :session_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['session_id' => $sessionId, 'company_id' => $companyId],
        );
    }

    public function checkpointDetails(string $checkpointId): ?array
    {
        $companyId = $this->requireCompanyId();

        return $this->db->fetch(
            'SELECT patrol_checkpoint_id, company_id, patrol_route_id FROM patrol_checkpoints WHERE patrol_checkpoint_id = :checkpoint_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1',
            ['checkpoint_id' => $checkpointId, 'company_id' => $companyId],
        );
    }

    public function createForCompany(array $data): array
    {
        $companyId = $this->requireCompanyId();
        $this->db->execute(
            'INSERT INTO patrol_checkpoint_events (patrol_checkpoint_event_uuid, company_id, patrol_session_id, patrol_checkpoint_id, event_type, recorded_at, event_notes) '
            . 'VALUES (:uuid, :company_id, :session_id, :checkpoint_id, :event_type, :recorded_at, :event_notes)',
            [
                'uuid' => uuid4(),
                'company_id' => $companyId,
                'session_id' => $data['patrol_session_id'],
                'checkpoint_id' => $data['patrol_checkpoint_id'],
                'event_type' => $data['event_type'] ?? 'VISITED',
                'recorded_at' => $data['recorded_at'],
                'event_notes' => $data['event_notes'] ?? null,
            ],
        );

        $id = (string) $this->db->connection()->lastInsertId();
        $created = $this->findByIdForCompany($id);
        if ($created === null) {
            throw new \RuntimeException('Created patrol checkpoint event could not be loaded.');
        }

        return $created;
    }
}
