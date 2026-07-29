<?php

namespace Modules\QuickDraw\Tests\Feature;

use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\QuickDraw\Http\Controllers\QuickDrawDashboardController;
use Modules\QuickDraw\Models\QuickDraw;
use Modules\QuickDraw\Models\QuickDrawState;
use Modules\QuickDraw\Observers\QuickDrawObserver;
use Modules\QuickDraw\Observers\QuickDrawStateObserver;
use Modules\QuickDraw\Services\QuickDrawDashboardService;
use Tests\TestCase;

class QuickDrawDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    private DashboardWidgetRegistry $widgetRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        $this->artisan('migrate', [
            '--path' => 'Modules/QuickDraw/database/migrations',
            '--force' => true,
        ])->run();

        $this->widgetRegistry = app(DashboardWidgetRegistry::class);
        app(QuickDrawDashboardService::class)->registerWidgets($this->widgetRegistry, 'QuickDraw');
        QuickDraw::observe(QuickDrawObserver::class);
        QuickDrawState::observe(QuickDrawStateObserver::class);
    }

    public function test_dashboard_requires_its_permission(): void
    {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('quickdraw.dashboard.view')
            ->andThrow(new AuthorizationException);

        $this->expectException(AuthorizationException::class);

        app(QuickDrawDashboardController::class)->index($this->widgetRegistry);
    }

    public function test_widgets_are_owned_and_cached_per_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstDrawing = $this->createDrawing($firstUser, 'First User Drawing');
        $secondDrawing = $this->createDrawing($secondUser, 'Second User Drawing');
        $this->createDrawing($secondUser, 'Another Second User Drawing');

        $this->actingAs($firstUser);
        $this->assertSame(1, $this->widget('overview', 'Total Drawings')['value']);
        $this->assertSame([$firstDrawing->id], array_column($this->widget('detail', 'Recent Drawings')['data'], 'id'));

        $this->actingAs($secondUser);
        $this->assertSame(2, $this->widget('overview', 'Total Drawings')['value']);
        $this->assertContains($secondDrawing->id, array_column($this->widget('detail', 'Recent Drawings')['data'], 'id'));
        $this->assertNotContains($firstDrawing->id, array_column($this->widget('detail', 'Recent Drawings')['data'], 'id'));
    }

    public function test_create_update_and_delete_invalidate_widget_cache(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertSame(0, $this->widget('overview', 'Total Drawings')['value']);

        $drawing = $this->createDrawing($user, 'Original Name');
        $this->assertSame(1, $this->widget('overview', 'Total Drawings')['value']);
        $this->assertSame('Original Name', $this->widget('detail', 'Recent Drawings')['data'][0]['title']);

        $drawing->update(['name' => 'Updated Name']);
        $this->assertSame('Updated Name', $this->widget('detail', 'Recent Drawings')['data'][0]['title']);

        $drawing->delete();
        $this->assertSame(0, $this->widget('overview', 'Total Drawings')['value']);
    }

    public function test_autosave_touches_parent_and_refreshes_recent_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $autosavedDrawing = $this->createDrawing($user, 'Autosaved Drawing');
        $recentDrawing = $this->createDrawing($user, 'Previously Recent Drawing');
        $autosavedDrawing->state()->create(['document_state' => '{"store":{}}']);

        QuickDraw::whereKey($autosavedDrawing)->update(['updated_at' => now()->subDays(2)]);
        QuickDraw::whereKey($recentDrawing)->update(['updated_at' => now()->subDay()]);

        $this->assertSame($recentDrawing->id, $this->widget('detail', 'Recent Drawings')['data'][0]['id']);

        $savedAt = now()->addMinute()->startOfSecond();
        $this->travelTo($savedAt);

        $autosavedDrawing->state()->updateOrCreate(
            ['quickdraw_id' => $autosavedDrawing->id],
            ['document_state' => '{"store":{"page":{}}}']
        );

        $this->assertTrue($autosavedDrawing->refresh()->updated_at->equalTo($savedAt));
        $this->assertSame($autosavedDrawing->id, $this->widget('detail', 'Recent Drawings')['data'][0]['id']);

        $this->travelBack();
    }

    private function createDrawing(User $user, string $name): QuickDraw
    {
        return QuickDraw::create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function widget(string $scope, string $title): array
    {
        return collect($this->widgetRegistry->getWidgetsForModule('QuickDraw', $scope))
            ->firstWhere('title', $title);
    }
}
