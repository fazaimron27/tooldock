<?php

namespace Modules\AuditLog\Jobs;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AuditLog\Models\AuditLog;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\User;
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
     * Always sets model to null after capturing type/id to prevent
     * ModelNotFoundException when the model is deleted before job runs
     * (e.g., during module uninstall when related models are cascade deleted).
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
        } else {
            $this->auditableType = 'system';
            $this->auditableId = null;
        }

        // Always nullify model after capturing type/id to prevent
        // ModelNotFoundException during unserialization if model
        // is deleted before the job runs (e.g., module uninstall)
        $this->model = null;
    }

    /**
     * Execute the job.
     *
     * Creates an audit log entry using captured type/id values.
     * Skips if model class no longer exists (e.g., module was uninstalled).
     * Invalidates filter cache to ensure dropdowns reflect new model types and events.
     */
    public function handle(): void
    {
        try {
            $auditableType = $this->auditableType;
            $auditableId = $this->auditableId;

            // Skip if model class no longer exists (e.g., module was uninstalled)
            if ($auditableType !== 'system' && ! class_exists($auditableType)) {
                Log::info('AuditLog: Skipping audit - model class no longer exists', [
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
                'auditable_type' => $this->auditableType,
                'auditable_id' => $this->auditableId,
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
     * Also notifies admins about the failure for system monitoring.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('AuditLog: Job failed after all retry attempts', [
            'event' => $this->event,
            'auditable_type' => $this->auditableType ?? 'unknown',
            'auditable_id' => $this->auditableId ?? null,
            'exception' => $exception?->getMessage(),
        ]);

        $this->notifyAdminsAboutFailure($exception);

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

    /**
     * Notify admin users about audit log job failure.
     *
     * Uses Signal facade directly instead of trait to avoid serialization issues in queued jobs.
     */
    private function notifyAdminsAboutFailure(?Throwable $exception): void
    {
        try {
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', Roles::SUPER_ADMIN);
            })->get();

            $errorMessage = $exception?->getMessage() ?? 'Unknown error';
            $shortError = strlen($errorMessage) > 100 ? substr($errorMessage, 0, 100).'...' : $errorMessage;

            foreach ($admins as $admin) {
                app(SignalHandlerRegistry::class)->dispatch('auditlog.job.failed', [
                    'user' => $admin,
                    'event' => $this->event,
                    'auditable_type' => $this->auditableType,
                    'error' => $shortError,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('AuditLog: Failed to send admin notification about job failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
