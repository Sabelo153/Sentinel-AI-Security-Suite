<?php
declare(strict_types=1);
namespace Sentinel\Services;

use Sentinel\Core\{Database, HttpException, Request, Response, RuntimeContext};

/** Runs after authentication, tenant resolution and authorization. */
final class AuditedMutationService
{
    public function __construct(private readonly Database $db, private readonly RuntimeContext $context, private readonly AuditService $audit) {}

    public function handle(Request $request, string $permission, callable $handler): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return $handler($request);
        if (!$this->context->isAuthenticated()) throw HttpException::unauthorized();
        $this->context->requireTenant();
        try {
            $this->db->beginTransaction();
            $response = $handler($request);
            if ($response->status() >= 400) {
                $this->db->rollBack();
                return $response;
            }
            // No body, token, query string or domain text is copied into the audit trail.
            $this->audit->record('protected_route', 'mutation_completed', null, [], [], [
                'permission' => $permission,
                'method' => $request->method(),
                'path' => $request->path(),
                'request_id' => $this->context->requestId(),
            ]);
            $this->db->commit();
            return $response;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ($error instanceof \PDOException) throw HttpException::serviceUnavailable('The operation could not be safely recorded.', 'mutation_unavailable');
            throw $error;
        }
    }
}
