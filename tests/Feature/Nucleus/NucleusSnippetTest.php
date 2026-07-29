<?php

namespace Tests\Feature\Nucleus;

use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\AuditLog\Jobs\CreateAuditLogJob;
use Modules\Core\Constants\Roles as RoleConstants;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Nucleus\Models\NucleusSnippet;
use Modules\Nucleus\Providers\AuthServiceProvider;
use Modules\Nucleus\Services\NucleusDashboardService;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NucleusSnippetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['permission' => require module_path('Core', 'config/permission.php')]);
        config(['permission.cache.store' => 'array']);
        $this->app->forgetInstance(PermissionRegistrar::class);

        if (! Schema::hasTable('nucleus_snippets')) {
            $migration = require module_path('Nucleus', 'database/migrations/2026_04_10_211338_create_nucleus_snippets_table.php');
            $migration->up();
        }

        $this->app->register(AuthServiceProvider::class);
        Route::middleware('web')
            ->prefix('tooldock')
            ->group(module_path('Nucleus', 'routes/web.php'));

        Queue::fake();
        $this->withoutVite();
    }

    public function test_authorized_user_can_open_the_editor_with_configured_settings(): void
    {
        $user = $this->createAuthorizedUser(['nucleus.snippet.view']);

        config([
            'nucleus.editor_theme' => 'light',
            'nucleus.word_wrap' => 'off',
            'nucleus.font_size' => 16,
        ]);

        $this->actingAs($user)
            ->get('/tooldock/nucleus')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Modules::Nucleus/Index', false)
                ->where('editorSettings.theme', 'light')
                ->where('editorSettings.wordWrap', 'off')
                ->where('editorSettings.fontSize', 16));
    }

    public function test_authorized_user_can_store_valid_json_without_copying_it_to_the_audit_log(): void
    {
        $user = $this->createAuthorizedUser(['nucleus.snippet.create']);
        $rawJson = '{"token":"sensitive-value"}';

        Queue::fake();

        $this->actingAs($user)
            ->postJson('/tooldock/nucleus/snippets', [
                'title' => 'Private payload',
                'raw_json' => $rawJson,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Snippet saved successfully.');

        $snippet = NucleusSnippet::sole();

        $this->assertSame($user->id, $snippet->user_id);
        $this->assertSame($rawJson, $snippet->raw_json);
        Queue::assertPushed(CreateAuditLogJob::class, fn (CreateAuditLogJob $job): bool => $job->newValues['raw_json'] === '***REDACTED***'
            && ! str_contains(json_encode($job->newValues), 'sensitive-value'));
    }

    public function test_invalid_json_is_rejected(): void
    {
        $user = $this->createAuthorizedUser(['nucleus.snippet.create']);

        $this->actingAs($user)
            ->postJson('/tooldock/nucleus/snippets', [
                'title' => 'Invalid payload',
                'raw_json' => '{"missing":"brace"',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'Validation Failed');

        $this->assertSame(0, NucleusSnippet::count());
    }

    public function test_json_larger_than_the_payload_limit_is_rejected(): void
    {
        $user = $this->createAuthorizedUser(['nucleus.snippet.create']);
        $oversizedJson = json_encode(['payload' => str_repeat('a', 64_000)]);

        $this->actingAs($user)
            ->postJson('/tooldock/nucleus/snippets', [
                'title' => 'Oversized payload',
                'raw_json' => $oversizedJson,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'Validation Failed');

        $this->assertSame(0, NucleusSnippet::count());
    }

    public function test_user_cannot_view_or_delete_another_users_snippet(): void
    {
        $owner = User::factory()->create();
        $user = $this->createAuthorizedUser([
            'nucleus.snippet.view',
            'nucleus.snippet.delete',
        ]);
        $snippet = NucleusSnippet::factory()->for($owner)->create();

        $this->actingAs($user)
            ->getJson("/tooldock/nucleus/snippets/{$snippet->id}")
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson("/tooldock/nucleus/snippets/{$snippet->id}")
            ->assertForbidden();

        $this->assertModelExists($snippet);
    }

    public function test_dashboard_widgets_only_include_the_authenticated_users_snippets(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $snippet = NucleusSnippet::factory()->for($user)->create();
        NucleusSnippet::factory()->for($otherUser)->count(2)->create();

        $this->actingAs($user);

        $widgetRegistry = app(DashboardWidgetRegistry::class);
        app(NucleusDashboardService::class)->registerWidgets($widgetRegistry, 'Nucleus');

        $totalWidget = collect($widgetRegistry->getWidgetsForModule('Nucleus', 'overview'))
            ->firstWhere('title', 'Total Snippets');
        $recentWidget = collect($widgetRegistry->getWidgetsForModule('Nucleus', 'detail'))
            ->firstWhere('title', 'Recent Snippets');

        $this->assertSame(1, $totalWidget['value']);
        $this->assertSame([$snippet->id], array_column($recentWidget['data'], 'id'));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function createAuthorizedUser(array $permissions): User
    {
        $role = Role::firstOrCreate([
            'name' => RoleConstants::MANAGER,
            'guard_name' => 'web',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
