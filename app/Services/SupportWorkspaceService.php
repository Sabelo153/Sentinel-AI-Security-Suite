<?php

declare(strict_types=1);

namespace Sentinel\Services;

use Sentinel\Core\{Database, HttpException, RuntimeContext};

/** Tenant-scoped support. Never reads mobile payloads, coordinates or credentials. */
final class SupportWorkspaceService
{
    public function __construct(
        private readonly Database $db,
        private readonly RuntimeContext $context,
        private readonly AuditService $audit,
    ) {}

    public function overview(?\DateTimeImmutable $clock = null): array
    {
        $company = $this->context->requireTenant();
        $clock ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $sections = [];
        $queries = [
            'devices' => "SELECT device_id, device_name, device_status, last_seen_at FROM devices WHERE company_id=:company AND deleted_at IS NULL ORDER BY device_id DESC LIMIT 201",
            'sessions' => "SELECT ds.device_session_id, ds.device_id, ds.employee_id, ds.site_id, s.site_name, ds.last_seen_at, ds.started_at FROM device_sessions ds LEFT JOIN sites s ON s.site_id=ds.site_id AND s.company_id=ds.company_id WHERE ds.company_id=:company AND ds.session_status='ACTIVE' ORDER BY ds.device_session_id DESC LIMIT 201",
            'sync' => "SELECT user_id, MAX(created_at) AS last_received_at, COUNT(*) AS receipt_count, SUM(CASE WHEN response_json IS NULL THEN 1 ELSE 0 END) AS incomplete_receipts FROM mobile_operations WHERE company_id=:company GROUP BY user_id ORDER BY last_received_at DESC LIMIT 201",
            'mobile_diagnostics' => "SELECT user_id,installation_id,app_version,pending_count,blocked_count,network_state,received_at FROM mobile_diagnostics WHERE company_id=:company ORDER BY received_at DESC LIMIT 201",
            'timeline' => "SELECT audit_log_id, user_id, entity_name, entity_id, action_name, action_status, occurred_at FROM audit_logs WHERE company_id=:company AND entity_name='support' ORDER BY audit_log_id DESC LIMIT 101",
        ];
        foreach ($queries as $name => $sql) {
            try {
                $rows = $this->db->fetchAll($sql, ['company' => $company]);
                $limit = $name === 'timeline' ? 100 : 200;
                $truncated = count($rows) > $limit;
                $rows = array_slice($rows, 0, $limit);
                if (in_array($name, ['devices', 'sessions'], true)) {
                    foreach ($rows as &$row) {
                        $row['contact_state'] = $this->contactState($row['last_seen_at'], $clock);
                    }
                    unset($row);
                }
                $sections[$name] = ['status' => 'ready', 'rows' => $rows, 'truncated' => $truncated];
            } catch (\PDOException|HttpException) {
                $sections[$name] = ['status' => 'unavailable', 'rows' => [], 'truncated' => false];
            }
        }
        return [
            'generated_at' => $clock->format('c'), 'company_id' => $company,
            'status' => in_array('unavailable', array_column($sections, 'status'), true) ? 'partial' : 'ready',
            'stale_after_minutes' => 30, 'sections' => $sections,
            'limits' => [
                'Last contact is evidence of server contact, not proof of guard safety or current connectivity.',
                'Device-only queued and rejected commands cannot be inspected here. Never clear app data to fix a queue.',
                'Sync receipts confirm server processing only. Uploaded diagnostics are last-known snapshots, not a live connection or battery feed.',
                'This console does not dispatch emergency assistance. Use site radio/phone procedures.',
            ],
        ];
    }

    public function disableAccount(string $id, array $input): array
    {
        if (!preg_match('/^[1-9][0-9]{0,18}$/D', $id)) throw HttpException::unprocessable('Invalid account identifier.');
        if (array_diff(array_keys($input), ['reason', 'confirm_user_id']) !== []) throw HttpException::unprocessable('Unknown recovery fields.');
        $reason = $input['reason'] ?? null;
        if (!is_string($reason) || mb_strlen(trim($reason)) < 10 || mb_strlen($reason) > 500) throw HttpException::unprocessable('Provide a recovery reason between 10 and 500 characters.');
        if (($input['confirm_user_id'] ?? null) !== $id) throw HttpException::unprocessable('Confirm the affected user ID.');
        if (!$this->context->isAuthenticated()) throw HttpException::unauthorized();
        if ($id === (string) $this->context->user()) throw HttpException::conflict('You cannot disable your own support account.');
        $company = $this->context->requireTenant();
        $owns = !$this->db->inTransaction();
        try {
            if ($owns) $this->db->beginTransaction();
            $changed = $this->db->execute("UPDATE users SET status='DISABLED' WHERE company_id=:company AND user_id=:id AND status='ACTIVE'", ['company' => $company, 'id' => $id]);
            if ($changed !== 1) throw HttpException::conflict('Active account not found in your company, or its state changed.');
            $this->audit->record('support', 'account_disabled', $id, ['status' => 'ACTIVE'], ['status' => 'DISABLED'], ['reason' => trim($reason)]);
            if ($owns) $this->db->commit();
            return ['user_id' => $id, 'status' => 'DISABLED', 'detail' => 'Company access is blocked on subsequent requests. Re-enabling requires a separate reviewed account recovery; existing tokens are not individually revoked.'];
        } catch (\Throwable $error) {
            if ($owns && $this->db->inTransaction()) $this->db->rollBack();
            if ($error instanceof \PDOException) throw HttpException::serviceUnavailable('Recovery could not be recorded. No recovery was committed.');
            throw $error;
        }
    }

    public function export(): array
    {
        $report = $this->overview();
        // Shareable bundle contains counts and states only, no tenant/user/site identifiers.
        $summary = [];
        foreach ($report['sections'] as $name => $section) {
            $summary[$name] = ['status' => $section['status'], 'visible_records' => count($section['rows']), 'truncated' => $section['truncated']];
            if (in_array($name, ['devices', 'sessions'], true)) $summary[$name]['contact_states'] = array_count_values(array_column($section['rows'], 'contact_state'));
        }
        $this->audit->record('support', 'bundle_exported');
        return ['generated_at' => $report['generated_at'], 'status' => $report['status'], 'summary' => $summary, 'limits' => $report['limits']];
    }

    private function contactState(?string $value, \DateTimeImmutable $clock): string
    {
        if ($value === null) return 'UNKNOWN';
        try { $at = new \DateTimeImmutable($value, new \DateTimeZone('UTC')); }
        catch (\Throwable) { return 'UNKNOWN'; }
        $age = $clock->getTimestamp() - $at->getTimestamp();
        return $age < 0 ? 'CLOCK_SKEW' : ($age > 1800 ? 'STALE' : 'RECENT');
    }
}
