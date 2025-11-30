<?php

namespace Modules\Newsletter\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Blog\Events\PostUpdating;
use Modules\Newsletter\Models\Campaign;

class PreventPostUpdateIfUsedInSendingCampaigns
{
    /**
     * Handle the PostUpdating event.
     *
     * Prevents updates if the post is used in campaigns that are currently being sent.
     * Only prevents for 'sending' status, not 'sent', to allow updates after sending is complete.
     *
     * Error Handling Strategy: FAIL OPEN
     * On error, allows update to proceed to ensure Blog module remains functional
     * even if Newsletter module has issues. Errors are logged but don't block the operation.
     *
     * @param  PostUpdating  $event  The post update event
     */
    public function handle(PostUpdating $event): void
    {
        try {
            $isUsedInSendingCampaigns = Campaign::query()
                ->where('status', 'sending')
                ->whereJsonContains('selected_posts', $event->post->id)
                ->exists();

            if ($isUsedInSendingCampaigns) {
                $event->preventUpdate = true;
                $event->preventionReason = 'Cannot update post. It is currently being used in one or more campaigns that are being sent.';
            }
        } catch (\Exception $e) {
            Log::error('Failed to check if post is used in sending campaigns', [
                'post_id' => $event->post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
