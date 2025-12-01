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
     * Create a new job instance.
     */
    public function __construct(
        public string $event,
        public Model $model,
        public ?array $oldValues,
        public ?array $newValues,
        public ?int $userId,
        public ?string $url,
        public ?string $ipAddress,
        public ?string $userAgent
    ) {}

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
            $modelExists = $this->model->exists ?? false;

            if (! $modelExists && $this->event !== 'deleted') {
                Log::warning('AuditLog: Model no longer exists for non-deleted event', [
                    'event' => $this->event,
                    'auditable_type' => get_class($this->model),
                    'auditable_id' => $this->model->getKey(),
                ]);

                return;
            }

            $auditableType = get_class($this->model);
            $auditableId = $this->model->getKey();

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
                'auditable_type' => get_class($this->model),
                'auditable_id' => $this->model->getKey() ?? null,
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
            'auditable_type' => get_class($this->model),
            'auditable_id' => $this->model->getKey() ?? null,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
