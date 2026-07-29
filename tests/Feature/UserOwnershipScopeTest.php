<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Categories\Models\Category;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Routine\Models\Habit;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;
use Tests\TestCase;

class UserOwnershipScopeTest extends TestCase
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
            '--path' => 'Modules/Treasury/database/migrations/2026_01_25_111545_create_budgets_table.php',
            '--force' => true,
        ]);
    }

    public function test_normal_and_super_admin_users_are_restricted_to_their_own_records(): void
    {
        $normalUser = User::factory()->create();
        $superAdmin = User::factory()->create();

        $superAdminRole = Role::findOrCreate(Roles::SUPER_ADMIN, 'web');
        $superAdmin->assignRole($superAdminRole);

        $normalHabit = Habit::factory()->for($normalUser)->create();
        $superAdminHabit = Habit::factory()->for($superAdmin)->create();

        $this->assertSame(
            [$normalHabit->id],
            Habit::query()->forUser($normalUser)->pluck('id')->all()
        );
        $this->assertSame(
            [$superAdminHabit->id],
            Habit::query()->forUser($superAdmin)->pluck('id')->all()
        );
    }

    public function test_budget_period_scope_restricts_normal_and_super_admin_users_to_related_budgets(): void
    {
        $normalUser = User::factory()->create();
        $superAdmin = User::factory()->create();

        $superAdminRole = Role::findOrCreate(Roles::SUPER_ADMIN, 'web');
        $superAdmin->assignRole($superAdminRole);

        $category = Category::factory()->create();
        $normalBudget = Budget::factory()->for($normalUser)->for($category)->create();
        $superAdminBudget = Budget::factory()->for($superAdmin)->for($category)->create();
        $normalPeriod = BudgetPeriod::factory()->for($normalBudget)->create();
        $superAdminPeriod = BudgetPeriod::factory()->for($superAdminBudget)->create();

        request()->setUserResolver(fn (): User => $normalUser);
        $this->assertSame([$normalPeriod->id], BudgetPeriod::query()->forUser()->pluck('id')->all());

        request()->setUserResolver(fn (): User => $superAdmin);
        $this->assertSame([$superAdminPeriod->id], BudgetPeriod::query()->forUser()->pluck('id')->all());
    }
}
