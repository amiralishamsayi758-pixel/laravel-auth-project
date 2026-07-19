<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_enum_contains_exactly_user_and_admin_roles(): void
    {
        $this->assertSame(['user', 'admin'], array_column(UserRole::cases(), 'value'));
    }

    public function test_default_factory_user_has_cast_user_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
    }

    public function test_admin_state_and_is_admin_are_role_specific(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isUser());
    }

    public function test_role_is_not_fillable_and_mass_assignment_cannot_promote(): void
    {
        $user = User::factory()->create();

        $this->assertNotContains('role', $user->getFillable());
        $this->assertNotContains('gmail_verified_at', $user->getFillable());
        $this->assertNotContains('avatar_path', $user->getFillable());

        $user->fill(['role' => UserRole::Admin->value])->save();

        $this->assertSame(UserRole::User, $user->fresh()->role);
    }
}
