<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_user_exposes_only_the_expected_mass_assignable_attributes(): void
    {
        $user = new User;

        $this->assertSame([
            'gmail',
            'phone',
            'username',
            'gmail_verified_at',
        ], $user->getFillable());
    }

    public function test_gmail_verified_at_uses_a_datetime_cast(): void
    {
        $user = new User;

        $this->assertSame('datetime', $user->getCasts()['gmail_verified_at']);
    }
}
