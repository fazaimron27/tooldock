<?php

namespace Modules\Settings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\Permission;
use Modules\Core\Models\User;
use Modules\Settings\Http\Requests\UpdateSettingsRequest;
use Modules\Settings\Models\Setting;
use Modules\Settings\Policies\SettingPolicy;
use Tests\TestCase;

class SettingsUpdateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::policy(Setting::class, SettingPolicy::class);
    }

    public function test_update_request_requires_settings_policy_permission(): void
    {
        $permission = Permission::findOrCreate('settings.config.update', 'web');
        $user = User::factory()->create();
        $request = UpdateSettingsRequest::create('/tooldock/settings', 'PATCH');
        $request->setUserResolver(fn (): User => $user);

        $this->assertFalse($request->authorize());

        $user->givePermissionTo($permission);

        $this->assertTrue($request->authorize());
    }
}
