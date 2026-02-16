<?php

/**
 * Send Notification Job.
 *
 * Queued job that sends a notification via SignalService.
 * This allows handler logic to run synchronously while the
 * actual notification (DB write + broadcast) runs asynchronously.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Signal\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Signal\Services\SignalService;
use Throwable;

/**
 * Class SendNotificationJob
 *
 * Queueable job that delegates notification sending to SignalService.
 * Supports silent, flash, trigger, and broadcast delivery modes.
 *
 * @see \Modules\Signal\Services\SignalService
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  string  $userId  The user ID to notify
     * @param  string  $type  Notification type (info|success|warning|alert)
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string|null  $url  Optional action URL
     * @param  string  $module  Module that triggered the notification
     * @param  string|null  $category  Notification category for preferences
     * @param  string  $delivery  Delivery mode (silent|flash|trigger|broadcast)
     * @param  string|null  $action  Frontend action to trigger (for trigger/broadcast delivery)
     */
    public function __construct(
        public string $userId,
        public string $type,
        public string $title,
        public string $message,
        public ?string $url,
        public string $module,
        public ?string $category,
        public string $delivery = SignalService::DELIVERY_SILENT,
        public ?string $action = null,
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     *
     * @param  SignalService  $signalService
     * @return void
     */
    public function handle(SignalService $signalService): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::debug('SendNotificationJob: User not found', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        if ($this->delivery === SignalService::DELIVERY_TRIGGER && $this->action) {
            $signalService->trigger(
                user: $user,
                action: $this->action,
                title: $this->title,
                message: $this->message,
                type: $this->type,
                url: $this->url,
                moduleSource: $this->module
            );

            return;
        }

        if ($this->delivery === SignalService::DELIVERY_FLASH) {
            $signalService->flash(
                user: $user,
                title: $this->title,
                message: $this->message,
                type: $this->type,
                url: $this->url,
                moduleSource: $this->module
            );

            return;
        }

        if ($this->delivery === SignalService::DELIVERY_BROADCAST && $this->action) {
            $signalService->broadcast(
                user: $user,
                action: $this->action,
                title: $this->title,
                message: $this->message,
                type: $this->type,
                url: $this->url,
                moduleSource: $this->module,
                category: $this->category
            );

            return;
        }

        match ($this->type) {
            'alert' => $signalService->alert($user, $this->title, $this->message, $this->url, $this->module, $this->category),
            'warning' => $signalService->warning($user, $this->title, $this->message, $this->url, $this->module, $this->category),
            'success' => $signalService->success($user, $this->title, $this->message, $this->url, $this->module, $this->category),
            default => $signalService->info($user, $this->title, $this->message, $this->url, $this->module, $this->category),
        };
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
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
     * @param  Throwable|null  $exception
     * @return void
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('SendNotificationJob: Failed to send notification', [
            'user_id' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'module' => $this->module,
            'delivery' => $this->delivery,
            'error' => $exception?->getMessage(),
        ]);
    }
}
