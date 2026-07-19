<?php

namespace Tests\Unit;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_any_is_admin_only(): void
    {
        $policy = new UserPolicy;

        $this->assertTrue($policy->viewAny(User::factory()->admin()->create()));
        $this->assertFalse($policy->viewAny(User::factory()->create()));
    }

    public function test_view_allows_self_and_admin_but_not_other_normal_user(): void
    {
        $policy = new UserPolicy;
        $user = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertTrue($policy->view($user, $user));
        $this->assertFalse($policy->view($user, $other));
        $this->assertTrue($policy->view($admin, $other));
    }

    public function test_update_allows_self_and_admin_but_not_other_normal_user(): void
    {
        $policy = new UserPolicy;
        $user = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertTrue($policy->update($user, $user));
        $this->assertFalse($policy->update($user, $other));
        $this->assertTrue($policy->update($admin, $other));
    }

    public function test_change_role_is_admin_only_and_forbids_self_change(): void
    {
        $policy = new UserPolicy;
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->assertTrue($policy->changeRole($admin, $other));
        $this->assertFalse($policy->changeRole($admin, $admin));
        $this->assertFalse($policy->changeRole($user, $other));
    }

    public function test_administrative_delete_is_admin_only_and_forbids_self_delete(): void
    {
        $policy = new UserPolicy;
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->assertTrue($policy->delete($admin, $other));
        $this->assertFalse($policy->delete($admin, $admin));
        $this->assertFalse($policy->delete($user, $other));
    }

    public function test_laravel_discovers_user_policy_for_model_abilities(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('changeRole', $user));
        $this->assertFalse($admin->can('changeRole', $admin));
        $this->assertTrue($user->can('view', $user));
    }
}
