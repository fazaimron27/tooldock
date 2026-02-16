<?php

namespace Modules\AuditLog\Services\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * AuditLog Cleanup Completed Handler
 *
 * Returns signal data when audit log cleanup command completes.
 * Notifies administrators about the cleanup results.
 */
class AuditLogCleanupCompletedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['auditlog.cleanup.completed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'AuditLog';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'AuditLogCleanupCompletedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['deleted_count']);
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        $deletedCount = $data['deleted_count'];
        $retentionDays = $data['retention_days'] ?? 30;

        return [
            'type' => 'info',
            'title' => 'Audit Log Cleanup Completed',
            'message' => "Cleaned up {$deletedCount} audit log entries older than {$retentionDays} days.",
            'url' => route('auditlog.index'),
            'category' => 'system',
        ];
    }
}
