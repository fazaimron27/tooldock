<?php

namespace Modules\Newsletter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
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

        $query = Campaign::query();

        $defaultPerPage = 10;

        $campaigns = $datatableService->build($query, [
            'searchFields' => ['subject', 'content'],
            'allowedSorts' => ['subject', 'status', 'created_at', 'updated_at', 'scheduled_at'],
            'defaultSort' => 'created_at',
            'defaultDirection' => 'desc',
            'allowedPerPage' => [10, 20, 30, 50],
            'defaultPerPage' => $defaultPerPage,
        ]);

        return Inertia::render('Modules::Newsletter/Index', [
            'campaigns' => $campaigns,
            'defaultPerPage' => $defaultPerPage,
        ]);
    }

    private function isBlogModuleAvailable(): bool
    {
        return Module::has('Blog')
            && Module::isEnabled('Blog')
            && class_exists(\Modules\Blog\Models\Post::class);
    }

    private function getPublishedPosts(int $limit = 20)
    {
        if (! $this->isBlogModuleAvailable()) {
            return collect([]);
        }

        try {
            return \Modules\Blog\Models\Post::query()
                ->select('id', 'title', 'published_at')
                ->where('user_id', request()->user()->id)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
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

    public function create(): Response
    {
        $this->authorize('create', Campaign::class);

        $posts = $this->getPublishedPosts();

        return Inertia::render('Modules::Newsletter/Create', [
            'posts' => $posts,
        ]);
    }

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

    public function show(Campaign $newsletter): Response|RedirectResponse
    {
        $this->authorize('view', $newsletter);

        $selectedPosts = collect([]);

        if (! empty($newsletter->selected_posts) && $this->isBlogModuleAvailable()) {
            try {
                $selectedPosts = \Modules\Blog\Models\Post::query()
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
