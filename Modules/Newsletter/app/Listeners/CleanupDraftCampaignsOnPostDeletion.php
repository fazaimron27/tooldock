<?php

namespace Modules\Newsletter\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Blog\Events\PostDeleted;
use Modules\Newsletter\Models\Campaign;

class CleanupDraftCampaignsOnPostDeletion
{
    /**
     * Handle the PostDeleted event.
     *
     * Removes the deleted post ID from draft campaigns' selected_posts array.
     *
     * Error Handling Strategy: FAIL CLOSED
     * On error, re-throws exception to rollback the transaction, ensuring data consistency.
     * If cleanup fails, post deletion must fail too to prevent orphaned references.
     *
     * @param  PostDeleted  $event  The post deletion event
     *
     * @throws \Exception Re-throws any exception to trigger transaction rollback
     */
    public function handle(PostDeleted $event): void
    {
        try {
            $draftCampaigns = Campaign::query()
                ->where('status', 'draft')
                ->whereJsonContains('selected_posts', $event->postId)
                ->get();

            if ($draftCampaigns->isEmpty()) {
                return;
            }

            foreach ($draftCampaigns as $campaign) {
                $posts = collect($campaign->selected_posts)
                    ->reject(fn (int $id) => $id === $event->postId)
                    ->values()
                    ->toArray();
                $campaign->update(['selected_posts' => $posts]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to cleanup draft campaigns after post deletion', [
                'post_id' => $event->postId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
