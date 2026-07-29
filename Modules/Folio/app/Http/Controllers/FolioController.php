<?php

/**
 * Folio Controller
 *
 * Handles CRUD operations and PDF export for resume documents.
 * Integrates with Spatie Laravel PDF for server-side PDF generation.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Folio\Http\Requests\StoreFolioRequest;
use Modules\Folio\Http\Requests\UpdateFolioRequest;
use Modules\Folio\Models\Folio;
use Modules\Folio\Policies\FolioPolicy;

/**
 * Class FolioController
 *
 * Provides endpoints for listing resumes, editing the builder,
 * auto-saving content, and downloading PDF exports.
 *
 * @see Folio
 * @see FolioPolicy
 */
class FolioController extends Controller
{
    /**
     * Display a listing of all resumes.
     *
     * @return Response Inertia response rendering the resume index page
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Folio::class);

        $folios = Folio::forUser()
            ->latest()
            ->get(['id', 'name', 'slug', 'created_at', 'updated_at']);

        return Inertia::render('Modules::Folio/Index', [
            'folios' => $folios,
        ]);
    }

    /**
     * Store a newly created resume.
     *
     * @param  StoreFolioRequest  $request  The validated HTTP request
     * @return RedirectResponse Redirect to the builder page
     */
    public function store(StoreFolioRequest $request): RedirectResponse
    {
        $name = $request->validated('name');
        $baseSlug = Str::slug($name);

        $existingSlugs = Folio::where('user_id', $request->user()->id)
            ->where(function ($query) use ($baseSlug) {
                $query->where('slug', $baseSlug)
                    ->orWhere('slug', 'LIKE', $baseSlug.'-%');
            })
            ->pluck('slug')
            ->toArray();

        $slug = $baseSlug;
        if (in_array($baseSlug, $existingSlugs)) {
            $counter = 1;
            while (in_array($baseSlug.'-'.$counter, $existingSlugs)) {
                $counter++;
            }
            $slug = $baseSlug.'-'.$counter;
        }

        $folio = Folio::create([
            'user_id' => $request->user()->id,
            'name' => $name,
            'slug' => $slug,
        ]);

        $folio->data()->create([
            'content' => self::defaultContent(),
        ]);

        return redirect()->route('folio.edit', $folio)
            ->with('success', 'Resume created successfully.');
    }

    /**
     * Show the builder for a specific resume.
     *
     * @param  Folio  $folio  The resume to edit
     * @return Response Inertia response rendering the builder
     */
    public function edit(Folio $folio): Response
    {
        $this->authorize('view', $folio);

        $folio->loadMissing('data');

        return Inertia::render('Modules::Folio/Builder', [
            'folio' => $folio->only('id', 'name', 'slug'),
            'content' => $folio->data?->content ?? self::defaultContent(),
        ]);
    }

    /**
     * Auto-save the resume JSON content.
     *
     * @param  UpdateFolioRequest  $request  The validated HTTP request with content
     * @param  Folio  $folio  The resume to update
     * @return JsonResponse JSON response confirming the save
     */
    public function update(UpdateFolioRequest $request, Folio $folio): JsonResponse
    {
        $folio->data()->updateOrCreate(
            ['folio_id' => $folio->id],
            ['content' => $request->validated('content')]
        );

        return response()->json(['status' => 'saved']);
    }

    /**
     * Remove the specified resume.
     *
     * @param  Folio  $folio  The resume to delete
     * @return RedirectResponse Redirect to index with success message
     */
    public function destroy(Folio $folio): RedirectResponse
    {
        $this->authorize('delete', $folio);

        $folio->delete();

        return redirect()->route('folio.index')
            ->with('success', 'Resume deleted successfully.');
    }

    /**
     * Render the resume as a standalone print page.
     *
     * Opens in a new tab. The user prints/saves as PDF using the
     * browser's native print dialog (Ctrl+P). This renders the exact
     * same React template components — true WYSIWYG.
     *
     * @param  Folio  $folio  The resume to render
     * @return Response Inertia response rendering the print page
     */
    public function print(Folio $folio): Response
    {
        $this->authorize('view', $folio);

        $folio->loadMissing('data');

        return Inertia::render('Modules::Folio/Print', [
            'content' => $folio->data?->content ?? self::defaultContent(),
            'folioName' => $folio->name,
        ]);
    }

    /**
     * Get the default empty resume content structure.
     *
     * @return array<string, mixed>
     */
    private static function defaultContent(): array
    {
        return [
            'template' => 'professional',
            'basics' => [
                'name' => '',
                'headline' => '',
                'email' => '',
                'phone' => '',
                'location' => '',
                'summary' => '',
                'website' => ['url' => '', 'label' => ''],
            ],
            'sections' => [
                'profiles' => ['title' => 'Profiles', 'items' => []],
                'work' => ['title' => 'Work Experience', 'items' => []],
                'education' => ['title' => 'Education', 'items' => []],
                'projects' => ['title' => 'Projects', 'items' => []],
                'skills' => ['title' => 'Skills', 'items' => []],
                'languages' => ['title' => 'Languages', 'items' => []],
                'interests' => ['title' => 'Interests', 'items' => []],
                'awards' => ['title' => 'Awards', 'items' => []],
                'certifications' => ['title' => 'Certifications', 'items' => []],
                'publications' => ['title' => 'Publications', 'items' => []],
                'volunteering' => ['title' => 'Volunteering', 'items' => []],
                'references' => ['title' => 'References', 'items' => []],
                'custom' => [],
            ],
            'settings' => [
                'sectionOrder' => [
                    'summary',
                    'profiles',
                    'skills',
                    'work',
                    'education',
                    'projects',
                    'volunteering',
                    'references',
                    'interests',
                    'certifications',
                    'awards',
                    'publications',
                    'languages',
                ],
                'typography' => [
                    'body' => ['fontFamily' => 'inter', 'fontSize' => 10.5, 'lineHeight' => 1.5],
                    'heading' => ['fontFamily' => 'inter', 'fontSize' => 13.5, 'lineHeight' => 1.5],
                ],
                'design' => [
                    'primaryColor' => '#dc2626',
                    'textColor' => '#000000',
                    'backgroundColor' => '#ffffff',
                ],
                'page' => [
                    'format' => 'a4',
                    'marginHorizontal' => 18,
                    'marginVertical' => 18,
                    'spacingHorizontal' => 4,
                    'spacingVertical' => 6,
                    'hideIcons' => false,
                ],
            ],
        ];
    }
}
