<?php

namespace Tests\Feature;

use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Folio\Http\Controllers\FolioDashboardController;
use Modules\Folio\Models\Folio;
use Modules\Folio\Models\FolioData;
use Modules\Folio\Observers\FolioDataObserver;
use Modules\Folio\Observers\FolioObserver;
use Modules\Folio\Services\FolioDashboardService;
use Tests\TestCase;

class FolioDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    private DashboardWidgetRegistry $widgetRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        $this->artisan('migrate', [
            '--path' => 'Modules/Folio/database/migrations',
            '--force' => true,
        ])->run();

        $this->widgetRegistry = app(DashboardWidgetRegistry::class);
        app(FolioDashboardService::class)->registerWidgets($this->widgetRegistry, 'Folio');
        Folio::observe(FolioObserver::class);
        FolioData::observe(FolioDataObserver::class);
    }

    public function test_dashboard_requires_its_permission(): void
    {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('folio.dashboard.view')
            ->andThrow(new AuthorizationException);

        $this->expectException(AuthorizationException::class);

        app(FolioDashboardController::class)->index($this->widgetRegistry);
    }

    public function test_widgets_are_owned_and_cached_per_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstFolio = $this->createFolio($firstUser, 'First User Folio');
        $secondFolio = $this->createFolio($secondUser, 'Second User Folio');
        $this->createFolio($secondUser, 'Another Second User Folio');

        $this->actingAs($firstUser);
        $this->assertSame(1, $this->widget('overview', 'Total Folios')['value']);
        $this->assertSame([$firstFolio->id], array_column($this->widget('detail', 'Recent Folios')['data'], 'id'));

        $this->actingAs($secondUser);
        $this->assertSame(2, $this->widget('overview', 'Total Folios')['value']);
        $this->assertContains($secondFolio->id, array_column($this->widget('detail', 'Recent Folios')['data'], 'id'));
        $this->assertNotContains($firstFolio->id, array_column($this->widget('detail', 'Recent Folios')['data'], 'id'));
    }

    public function test_create_update_and_delete_invalidate_widget_cache(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertSame(0, $this->widget('overview', 'Total Folios')['value']);

        $folio = $this->createFolio($user, 'Original Name');
        $this->assertSame(1, $this->widget('overview', 'Total Folios')['value']);
        $this->assertSame('Original Name', $this->widget('detail', 'Recent Folios')['data'][0]['title']);

        $folio->update(['name' => 'Updated Name']);
        $this->assertSame('Updated Name', $this->widget('detail', 'Recent Folios')['data'][0]['title']);

        $folio->delete();
        $this->assertSame(0, $this->widget('overview', 'Total Folios')['value']);
    }

    public function test_autosave_touches_parent_and_refreshes_recent_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $autosavedFolio = $this->createFolio($user, 'Autosaved Folio');
        $recentFolio = $this->createFolio($user, 'Previously Recent Folio');
        $autosavedFolio->data()->create(['content' => ['template' => 'professional']]);

        Folio::whereKey($autosavedFolio)->update(['updated_at' => now()->subDays(2)]);
        Folio::whereKey($recentFolio)->update(['updated_at' => now()->subDay()]);

        $this->assertSame($recentFolio->id, $this->widget('detail', 'Recent Folios')['data'][0]['id']);

        $savedAt = now()->addMinute()->startOfSecond();
        $this->travelTo($savedAt);

        $autosavedFolio->data()->updateOrCreate(
            ['folio_id' => $autosavedFolio->id],
            ['content' => ['template' => 'minimal']]
        );

        $this->assertTrue($autosavedFolio->refresh()->updated_at->equalTo($savedAt));
        $this->assertSame($autosavedFolio->id, $this->widget('detail', 'Recent Folios')['data'][0]['id']);

        $this->travelBack();
    }

    private function createFolio(User $user, string $name): Folio
    {
        return Folio::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function widget(string $scope, string $title): array
    {
        return collect($this->widgetRegistry->getWidgetsForModule('Folio', $scope))
            ->firstWhere('title', $title);
    }
}
