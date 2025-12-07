<?php

namespace Modules\AuditLog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Models\AuditLog;
use Throwable;

class CreateAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The model's class name (captured before serialization).
     */
    public string $auditableType;

    /**
     * The model's ID (captured before serialization).
     */
    public ?string $auditableId;

    /**
     * Create a new job instance.
     *
     * Captures model information before serialization to handle deleted models.
     * For deleted events, sets model to null to prevent serialization issues
     * since model information is already captured in auditableType and auditableId.
     * For events without a model (e.g., export, login), uses 'system' type.
     */
    public function __construct(
        public string $event,
        public ?Model $model,
        public ?array $oldValues,
        public ?array $newValues,
        public ?string $userId,
        public ?string $url,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $tags = null
    ) {
        if ($model !== null) {
            $this->auditableType = get_class($model);
            $this->auditableId = $model->getKey();

            if (in_array($this->event, AuditLogEvent::eventsWithNullModel(), true)) {
                $this->model = null;
            }
        } else {
            $this->auditableType = 'system';
            $this->auditableId = null;
        }
    }

    /**
     * Execute the job.
     *
     * Creates an audit log entry for the model change.
     * Uses captured values instead of accessing the model directly since
     * the model may not exist or be accessible after deletion.
     *
     * Verifies model existence for events that require it, skipping the check
     * for deleted/login/logout/password_reset/export events.
     * Also verifies model class exists to handle cases where model class was removed.
     *
     * Invalidates filter cache to ensure dropdowns reflect new model types and events.
     */
    public function handle(): void
    {
        try {
            $auditableType = $this->auditableType;
            $auditableId = $this->auditableId;

            if (! in_array($this->event, AuditLogEvent::eventsWithoutModelCheck(), true) && $this->model !== null) {
                $modelExists = $this->model->exists ?? false;

                if (! $modelExists) {
                    Log::warning('AuditLog: Model no longer exists for non-deleted event', [
                        'event' => $this->event,
                        'auditable_type' => $auditableType,
                        'auditable_id' => $auditableId,
                    ]);

                    return;
                }
            }

            if ($auditableType !== 'system' && ! class_exists($auditableType)) {
                Log::warning('AuditLog: Model class no longer exists', [
                    'event' => $this->event,
                    'auditable_type' => $auditableType,
                    'auditable_id' => $auditableId,
                ]);

                return;
            }

            AuditLog::create([
                'user_id' => $this->userId,
                'event' => $this->event,
                'auditable_type' => $auditableType,
                'auditable_id' => $auditableId,
                'old_values' => $this->oldValues,
                'new_values' => $this->newValues,
                'url' => $this->url,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'tags' => $this->tags,
            ]);

            AuditLog::invalidateModelTypesCache();
        } catch (Throwable $e) {
            Log::error('AuditLog: Failed to create audit log entry', [
                'event' => $this->event,
                'auditable_type' => $this->auditableType ?? ($this->model ? get_class($this->model) : 'unknown'),
                'auditable_id' => $this->auditableId ?? ($this->model?->getKey() ?? null),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * Implements exponential backoff: 1 second, 5 seconds, 10 seconds.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    /**
     * Handle a job failure.
     *
     * When the database insert fails after all retry attempts,
     * write the audit payload to a physical file to ensure no audit trail is lost.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('AuditLog: Job failed after all retry attempts', [
            'event' => $this->event,
            'auditable_type' => $this->auditableType ?? 'unknown',
            'auditable_id' => $this->auditableId ?? null,
            'exception' => $exception?->getMessage(),
        ]);

        /**
         * Prepare complete audit payload for emergency file logging.
         * Includes all audit log data plus exception information.
         */
        $payload = [
            'timestamp' => now()->toIso8601String(),
            'event' => $this->event,
            'auditable_type' => $this->auditableType ?? 'unknown',
            'auditable_id' => $this->auditableId ?? null,
            'user_id' => $this->userId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'url' => $this->url,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'tags' => $this->tags,
            'exception' => $exception?->getMessage(),
            'exception_trace' => $exception?->getTraceAsString(),
        ];

        /**
         * Write to emergency log file to preserve audit trail when database fails.
         * Uses atomic file write with locking to prevent corruption.
         * Falls back to Laravel log if file write fails.
         */
        $logPath = storage_path('logs/audit-emergency.log');
        $logEntry = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n---\n\n";

        try {
            file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            Log::critical('AuditLog: Failed to write emergency log file', [
                'emergency_log_path' => $logPath,
                'error' => $e->getMessage(),
                'original_payload' => $payload,
            ]);
        }
    }
}
