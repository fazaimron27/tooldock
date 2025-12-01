<?php

namespace Modules\AuditLog\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AuditLog\App\Models\AuditLog;
use Throwable;

class CreateAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The model's class name (captured before serialization).
     */
    public string $auditableType;

    /**
     * The model's ID (captured before serialization).
     */
    public ?int $auditableId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $event,
        public ?Model $model,
        public ?array $oldValues,
        public ?array $newValues,
        public ?int $userId,
        public ?string $url,
        public ?string $ipAddress,
        public ?string $userAgent
    ) {
        /**
         * Capture model information before serialization.
         * This is critical for deleted models which may not be accessible after deserialization.
         */
        if ($model !== null) {
            $this->auditableType = get_class($model);
            $this->auditableId = $model->getKey();

            /**
             * For deleted events, set model to null to prevent serialization issues.
             * We've already captured all necessary information above.
             */
            if ($this->event === 'deleted') {
                $this->model = null;
            }
        } else {
            /**
             * Fallback if model is already null (shouldn't happen, but be safe).
             */
            $this->auditableType = 'unknown';
            $this->auditableId = null;
        }
    }

    /**
     * Execute the job.
     *
     * Creates an audit log entry for the model change.
     * Handles cases where the model may have been deleted
     * or the model class no longer exists.
     */
    public function handle(): void
    {
        try {
            /**
             * Use captured values instead of accessing the model.
             * The model may not exist or be accessible after deletion.
             */
            $auditableType = $this->auditableType;
            $auditableId = $this->auditableId;

            /**
             * For deleted events, we don't need to check if model exists.
             * For other events, verify the model still exists.
             */
            if ($this->event !== 'deleted' && $this->model !== null) {
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

            if (! class_exists($auditableType)) {
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
            ]);
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
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('AuditLog: Job failed after all retry attempts', [
            'event' => $this->event,
            'auditable_type' => $this->auditableType ?? 'unknown',
            'auditable_id' => $this->auditableId ?? null,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
