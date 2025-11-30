<?php

namespace Modules\Newsletter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Blog\Models\Post;
use Modules\Newsletter\Http\Requests\StoreCampaignRequest;
use Modules\Newsletter\Http\Requests\UpdateCampaignRequest;
use Modules\Newsletter\Jobs\SendCampaignJob;
use Modules\Newsletter\Models\Campaign;
use Nwidart\Modules\Facades\Module;

class CampaignController extends Controller
{
    /**
     * Display paginated listing of campaigns with server-side search, sorting, and pagination.
     */
    public function index(DatatableQueryService $datatableService): Response
    {
        $this->authorize('viewAny', Campaign::class);

        $query = Campaign::query()->forUser();

        $defaultPerPage = (int) settings('campaigns_per_page', 10);
        $defaultSort = settings('newsletter_default_sort', 'created_at');
        $defaultDirection = settings('newsletter_default_sort_direction', 'desc');

        $campaigns = $datatableService->build($query, [
            'searchFields' => ['subject', 'content'],
            'allowedSorts' => ['subject', 'status', 'created_at', 'updated_at', 'scheduled_at'],
            'defaultSort' => $defaultSort,
            'defaultDirection' => $defaultDirection,
            'allowedPerPage' => [10, 20, 30, 50],
            'defaultPerPage' => $defaultPerPage,
        ]);

        return Inertia::render('Modules::Newsletter/Index', [
            'campaigns' => $campaigns,
            'defaultPerPage' => $defaultPerPage,
        ]);
    }

    /**
     * Check if the Blog module is available and enabled.
     *
     * @return bool
     */
    private function isBlogModuleAvailable(): bool
    {
        return Module::has('Blog')
            && Module::isEnabled('Blog')
            && class_exists(Post::class);
    }

    /**
     * Get published blog posts for the current user.
     *
     * @param  int|null  $limit  Maximum number of posts to retrieve (defaults to setting)
     * @return \Illuminate\Support\Collection<int, Post>
     */
    private function getPublishedPosts(?int $limit = null)
    {
        $limit = $limit ?? (int) settings('max_posts_per_campaign', 20);
        if (! $this->isBlogModuleAvailable()) {
            return collect([]);
        }

        try {
            return Post::query()
                ->select('id', 'title', 'published_at')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->forUser()
                ->latest()
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::warning('Failed to fetch blog posts for newsletter', [
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Display the form for creating a new campaign.
     */
    public function create(): Response
    {
        $this->authorize('create', Campaign::class);

        $posts = $this->getPublishedPosts();

        return Inertia::render('Modules::Newsletter/Create', [
            'posts' => $posts,
        ]);
    }

    /**
     * Store a newly created campaign in storage.
     *
     * Campaigns are created with 'draft' status by default.
     */
    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        Campaign::create([
            'user_id' => $request->user()->id,
            'subject' => $request->subject,
            'status' => 'draft',
            'content' => $request->content,
            'selected_posts' => $request->selected_posts,
            'scheduled_at' => $request->scheduled_at,
        ]);

        return redirect()->route('newsletter.index')
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Display the specified campaign with its selected blog posts.
     */
    public function show(Campaign $newsletter): Response|RedirectResponse
    {
        $this->authorize('view', $newsletter);

        $selectedPosts = collect([]);

        if (! empty($newsletter->selected_posts) && $this->isBlogModuleAvailable()) {
            try {
                $selectedPosts = Post::query()
                    ->whereIn('id', $newsletter->selected_posts)
                    ->select('id', 'title', 'published_at')
                    ->get();
            } catch (\Exception $e) {
                Log::warning('Failed to fetch selected blog posts for campaign', [
                    'campaign_id' => $newsletter->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Inertia::render('Modules::Newsletter/Show', [
            'campaign' => $newsletter,
            'selectedPosts' => $selectedPosts,
        ]);
    }

    /**
     * Display the form for editing the specified campaign.
     *
     * Sent campaigns cannot be edited.
     */
    public function edit(Campaign $newsletter): Response|RedirectResponse
    {
        $this->authorize('update', $newsletter);

        if ($newsletter->status === 'sent') {
            return redirect()->route('newsletter.show', $newsletter)
                ->with('error', 'Cannot edit a campaign that has already been sent.');
        }

        $posts = $this->getPublishedPosts();

        return Inertia::render('Modules::Newsletter/Edit', [
            'campaign' => $newsletter,
            'posts' => $posts,
        ]);
    }

    /**
     * Update the specified campaign in storage.
     *
     * Sent or sending campaigns cannot be updated.
     */
    public function update(UpdateCampaignRequest $request, Campaign $newsletter): RedirectResponse
    {
        $this->authorize('update', $newsletter);

        if ($newsletter->status === 'sent') {
            return redirect()->route('newsletter.show', $newsletter)
                ->with('error', 'Cannot update a campaign that has already been sent.');
        }

        if ($newsletter->status === 'sending') {
            return redirect()->route('newsletter.show', $newsletter)
                ->with('error', 'Cannot update a campaign that is currently being sent.');
        }

        $newsletter->update([
            'subject' => $request->subject,
            'content' => $request->content,
            'selected_posts' => $request->selected_posts,
            'scheduled_at' => $request->scheduled_at,
        ]);

        return redirect()->route('newsletter.index')
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Delete the specified campaign.
     *
     * Sending campaigns cannot be deleted.
     */
    public function destroy(Campaign $newsletter): RedirectResponse
    {
        $this->authorize('delete', $newsletter);

        if ($newsletter->status === 'sending') {
            return redirect()->route('newsletter.index')
                ->with('error', 'Cannot delete a campaign that is currently being sent.');
        }

        $newsletter->delete();

        return redirect()->route('newsletter.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Queue the specified campaign for sending.
     *
     * Only draft campaigns can be sent. The campaign must have a subject and content.
     */
    public function send(Campaign $newsletter): RedirectResponse
    {
        $this->authorize('send', $newsletter);

        if ($newsletter->status !== 'draft') {
            return redirect()->route('newsletter.show', $newsletter)
                ->with('error', 'Only draft campaigns can be sent.');
        }

        if (empty($newsletter->subject) || empty($newsletter->content)) {
            return redirect()->route('newsletter.show', $newsletter)
                ->with('error', 'Campaign must have a subject and content before sending.');
        }

        SendCampaignJob::dispatch($newsletter);

        return redirect()->route('newsletter.show', $newsletter)
            ->with('success', 'Campaign is being sent. Status will update automatically.');
    }
}
