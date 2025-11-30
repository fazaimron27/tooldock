<?php

namespace Modules\Newsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Newsletter\Models\Campaign;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign
    ) {}

    /**
     * Execute the job to send the campaign.
     * Updates campaign status from 'sending' to 'sent' after processing.
     *
     * TODO: Implement actual email sending logic:
     * - Fetch subscriber list
     * - Send emails using Laravel Mail
     * - Track delivery status
     * - Handle failures
     */
    public function handle(): void
    {
        $this->campaign->update(['status' => 'sending']);

        $this->campaign->update(['status' => 'sent']);
    }
}
