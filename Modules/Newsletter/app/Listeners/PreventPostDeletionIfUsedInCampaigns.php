<?php

namespace Modules\Newsletter\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Blog\Events\PostDeleting;
use Modules\Newsletter\Models\Campaign;

class PreventPostDeletionIfUsedInCampaigns
{
    /**
     * Handle the PostDeleting event.
     *
     * Prevents deletion if the post is used in active campaigns (sending or sent).
     *
     * Error Handling Strategy: FAIL OPEN
     * On error, allows deletion to proceed to ensure Blog module remains functional
     * even if Newsletter module has issues. Errors are logged but don't block the operation.
     *
     * @param  PostDeleting  $event  The post deletion event
     */
    public function handle(PostDeleting $event): void
    {
        try {
            $isUsedInSentCampaigns = Campaign::query()
                ->whereIn('status', ['sent', 'sending'])
                ->whereJsonContains('selected_posts', $event->post->id)
                ->exists();

            if ($isUsedInSentCampaigns) {
                $event->preventDeletion = true;
                $event->preventionReason = 'Cannot delete post. It is used in one or more active campaigns (sending or sent).';
            }
        } catch (\Exception $e) {
            Log::error('Failed to check if post is used in campaigns', [
                'post_id' => $event->post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
