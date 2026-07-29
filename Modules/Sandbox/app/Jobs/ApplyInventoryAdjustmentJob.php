<?php

namespace Modules\Sandbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Sandbox\Models\SandboxIntake;
use Modules\Sandbox\Models\SandboxInventoryLevel;
use Throwable;

class ApplyInventoryAdjustmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public string $intakeId)
    {
        $this->onQueue((string) config('sandbox.apply_queue', 'sandbox-apply'));
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $intake = SandboxIntake::query()
                ->lockForUpdate()
                ->find($this->intakeId);

            if (! $intake || $intake->status === 'applied') {
                return;
            }

            $intake->update([
                'status' => 'applying',
                'failure_reason' => null,
            ]);

            foreach ($intake->normalized_items ?? [] as $item) {
                $level = SandboxInventoryLevel::query()
                    ->firstOrCreate([
                        'user_id' => $intake->user_id,
                        'warehouse_code' => (string) $intake->warehouse_code,
                        'sku' => (string) ($item['sku'] ?? ''),
                    ], [
                        'quantity' => 0,
                    ]);

                $level = SandboxInventoryLevel::query()
                    ->lockForUpdate()
                    ->findOrFail($level->id);

                $level->update([
                    'quantity' => (int) $level->quantity + (int) ($item['delta'] ?? 0),
                ]);
            }

            $intake->update([
                'status' => 'applied',
                'applied_at' => now(),
            ]);
        }, attempts: 3);
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function failed(?Throwable $exception): void
    {
        $intake = SandboxIntake::find($this->intakeId);

        if ($intake) {
            $intake->update([
                'status' => 'failed',
                'failure_reason' => $exception?->getMessage(),
            ]);
        }

        Log::error('ApplyInventoryAdjustmentJob failed', [
            'sandbox_intake_id' => $this->intakeId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
