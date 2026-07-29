<?php

namespace Modules\Sandbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Sandbox\Models\SandboxIntake;

class ReviewInventoryAdjustmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public string $intakeId)
    {
        $this->onQueue((string) config('sandbox.review_queue', 'sandbox-review'));
    }

    public function handle(): void
    {
        $intake = SandboxIntake::find($this->intakeId);

        if (! $intake) {
            return;
        }

        $intake->update([
            'status' => 'review_required',
            'reviewed_at' => null,
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }
}
