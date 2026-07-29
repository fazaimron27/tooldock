<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Media\Http\Controllers\MediaController;
use Modules\Media\Http\Requests\UploadMediaRequest;
use Modules\Media\Models\MediaFile;
use Modules\Media\Policies\MediaFilePolicy;
use Modules\Routine\Models\Habit;
use Tests\TestCase;

class MediaAuthorizationAndListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'Modules/Routine/database/migrations/2026_02_17_095510_create_habits_table.php',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'Modules/Routine/database/migrations/2026_02_17_095511_create_habit_logs_table.php',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'Modules/Media/database/migrations/2025_11_30_165152_create_media_files_table.php',
            '--force' => true,
        ]);

        Gate::policy(MediaFile::class, MediaFilePolicy::class);
    }

    public function test_upload_request_requires_media_create_permission(): void
    {
        $permission = Permission::findOrCreate('media.files.upload', 'web');
        $user = User::factory()->create();
        $request = UploadMediaRequest::create('/api/v1/media/upload', 'POST');
        $request->setUserResolver(fn (): User => $user);

        $this->assertFalse($request->authorize());

        $user->givePermissionTo($permission);

        $this->assertTrue($request->authorize());
    }

    public function test_listing_includes_owned_user_and_model_media_and_excludes_foreign_media_for_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $foreignUser = User::factory()->create();

        $superAdminRole = Role::findOrCreate(Roles::SUPER_ADMIN, 'web');
        $superAdmin->assignRole($superAdminRole);

        $ownedHabit = Habit::factory()->for($superAdmin)->create();
        $foreignHabit = Habit::factory()->for($foreignUser)->create();

        $ownedUserMedia = $this->createMediaFor($superAdmin, 'owned-user.jpg');
        $ownedHabitMedia = $this->createMediaFor($ownedHabit, 'owned-habit.jpg');
        $this->createMediaFor($foreignUser, 'foreign-user.jpg');
        $this->createMediaFor($foreignHabit, 'foreign-habit.jpg');

        $this->actingAs($superAdmin);

        $request = Request::create('/_tests/media', 'GET', server: ['HTTP_X_INERTIA' => 'true']);
        $request->setUserResolver(fn (): User => $superAdmin);
        $this->app->instance('request', $request);

        $response = $this->app->make(MediaController::class)->index()->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());

        $page = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $listedIds = collect($page['props']['mediaFiles']['data'])->pluck('id')->all();

        $this->assertEqualsCanonicalizing(
            [$ownedUserMedia->id, $ownedHabitMedia->id],
            $listedIds
        );
    }

    private function createMediaFor(Model $model, string $filename): MediaFile
    {
        return MediaFile::withoutEvents(fn (): MediaFile => MediaFile::query()->create([
            'disk' => 'public',
            'path' => 'tests/'.$filename,
            'filename' => $filename,
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'is_temporary' => false,
        ]));
    }
}
