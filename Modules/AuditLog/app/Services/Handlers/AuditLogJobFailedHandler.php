<?php

namespace Modules\AuditLog\Services\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Core\Models\User;

/**
 * AuditLog Job Failed Handler
 *
 * Returns signal data when an audit log job fails.
 * Notifies administrators about the failure.
 */
class AuditLogJobFailedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['auditlog.job.failed'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'AuditLog';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'AuditLogJobFailedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['user'])
            && $data['user'] instanceof User
            && isset($data['error']);
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $user */
        $user = $data['user'];
        $error = $data['error'] ?? 'Unknown error';
        $event = $data['event'] ?? 'unknown';
        $auditableType = $data['auditable_type'] ?? 'unknown';
        $actionUrl = $user->can('auditlog.dashboard.view') ? route('auditlog.dashboard') : null;

        return [
            'type' => 'alert',
            'title' => 'Audit Log Job Failed',
            'message' => "An audit log entry failed to save after 3 attempts. Event: {$event}, Type: {$auditableType}. Error: {$error}",
            'url' => $actionUrl,
            'category' => 'system',
        ];
    }
}
