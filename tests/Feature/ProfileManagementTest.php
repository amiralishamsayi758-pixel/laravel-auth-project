<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_every_profile_endpoint(): void
    {
        $requests = [
            fn () => $this->get(route('profile.edit')),
            fn () => $this->patch(route('profile.update')),
            fn () => $this->put(route('profile.password.update')),
            fn () => $this->post(route('profile.avatar.store')),
            fn () => $this->delete(route('profile.avatar.destroy')),
            fn () => $this->delete(route('profile.destroy')),
        ];

        foreach ($requests as $request) {
            $request()->assertRedirectToRoute('login');
        }
    }

    public function test_unverified_user_is_redirected_from_every_profile_endpoint(): void
    {
        $user = User::factory()->unverified()->create();
        $requests = [
            fn () => $this->actingAs($user)->get(route('profile.edit')),
            fn () => $this->actingAs($user)->patch(route('profile.update')),
            fn () => $this->actingAs($user)->put(route('profile.password.update')),
            fn () => $this->actingAs($user)->post(route('profile.avatar.store')),
            fn () => $this->actingAs($user)->delete(route('profile.avatar.destroy')),
            fn () => $this->actingAs($user)->delete(route('profile.destroy')),
        ];

        foreach ($requests as $request) {
            $request()->assertRedirectToRoute('verification.notice');
        }
    }

    public function test_verified_user_can_view_own_profile_data(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee($user->username)
            ->assertSee($user->gmail)
            ->assertSee($user->phone);
    }

    public function test_username_and_phone_can_be_updated_while_unchanged_gmail_stays_verified_without_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $verifiedAt = $user->gmail_verified_at;

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload($user, [
            'username' => 'updated_user',
            'phone' => '09987654321',
            'gmail_verified_at' => null,
            'password' => 'InjectedPass123',
            'remember_token' => 'injected',
            'avatar_path' => 'avatars/injected.jpg',
        ]))->assertRedirect()->assertSessionHas('status', 'profile-updated');

        $freshUser = $user->fresh();
        $this->assertSame('updated_user', $freshUser->username);
        $this->assertSame('09987654321', $freshUser->phone);
        $this->assertTrue($freshUser->gmail_verified_at->equalTo($verifiedAt));
        $this->assertNull($freshUser->avatar_path);
        $this->assertNotSame('injected', $freshUser->remember_token);
        $this->assertTrue(Hash::check('Password123', $freshUser->password));
        Notification::assertNothingSent();
    }

    public function test_changing_gmail_clears_verification_sends_exactly_one_notification_and_redirects(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload($user, [
            'gmail' => 'changed@gmail.com',
        ]))->assertRedirectToRoute('verification.notice');

        $freshUser = $user->fresh();
        $this->assertSame('changed@gmail.com', $freshUser->gmail);
        $this->assertFalse($freshUser->hasVerifiedEmail());
        Notification::assertSentToTimes($freshUser, VerifyEmail::class, 1);
    }

    public function test_duplicate_profile_identifiers_are_rejected_in_named_error_bag(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        foreach (['gmail', 'username', 'phone'] as $field) {
            $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload($user, [
                $field => $other->{$field},
            ]))->assertSessionHasErrors([$field], null, 'profileUpdate');
        }
    }

    public function test_profile_update_only_changes_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherSnapshot = $other->only(['username', 'gmail', 'phone']);

        $this->actingAs($user)->patch(route('profile.update'), $this->profilePayload($user, [
            'username' => 'owner_updated',
            'user_id' => $other->getKey(),
        ]));

        $this->assertSame('owner_updated', $user->fresh()->username);
        $this->assertSame($otherSnapshot, $other->fresh()->only(['username', 'gmail', 'phone']));
    }

    public function test_password_update_validates_current_password_strength_and_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass123']);

        $this->actingAs($user)->put(route('profile.password.update'), [
            'password' => 'NewSecurePass456',
            'password_confirmation' => 'NewSecurePass456',
        ])->assertSessionHasErrors(['current_password'], null, 'passwordUpdate');

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'WrongPass123',
            'password' => 'NewSecurePass456',
            'password_confirmation' => 'NewSecurePass456',
        ])->assertSessionHasErrors(['current_password'], null, 'passwordUpdate');

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'CurrentPass123',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['password'], null, 'passwordUpdate');

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'CurrentPass123',
            'password' => 'NewSecurePass456',
            'password_confirmation' => 'DifferentPass456',
        ])->assertSessionHasErrors(['password'], null, 'passwordUpdate');
    }

    public function test_valid_password_update_hashes_new_password_rotates_token_and_keeps_session(): void
    {
        $user = User::factory()->create([
            'password' => 'CurrentPass123',
            'remember_token' => 'original-token',
        ]);

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'CurrentPass123',
            'password' => 'NewSecurePass456',
            'password_confirmation' => 'NewSecurePass456',
        ])->assertRedirect()->assertSessionHas('status', 'password-updated');

        $freshUser = $user->fresh();
        $this->assertAuthenticatedAs($freshUser);
        $this->assertFalse(Hash::check('CurrentPass123', $freshUser->password));
        $this->assertTrue(Hash::check('NewSecurePass456', $freshUser->password));
        $this->assertNotSame('original-token', $freshUser->remember_token);
    }

    public function test_valid_jpg_png_and_webp_avatars_can_be_uploaded(): void
    {
        foreach (['jpg', 'png', 'webp'] as $extension) {
            Storage::fake('public');
            $user = User::factory()->create();

            $this->actingAs($user)->post(route('profile.avatar.store'), [
                'avatar' => $this->imageUpload($extension),
            ])->assertRedirect()->assertSessionHas('status', 'avatar-updated');

            $path = $user->fresh()->avatar_path;
            $this->assertIsString($path);
            $this->assertStringStartsWith('avatars/', $path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_invalid_oversized_and_svg_avatars_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $files = [
            UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            UploadedFile::fake()->create('large.jpg', 2049, 'image/jpeg'),
            UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        ];

        foreach ($files as $file) {
            $this->actingAs($user)->post(route('profile.avatar.store'), ['avatar' => $file])
                ->assertSessionHasErrors(['avatar'], null, 'avatarUpdate');
        }

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_replacing_avatar_stores_new_file_then_deletes_old_file(): void
    {
        Storage::fake('public');
        $oldPath = 'avatars/old-avatar.jpg';
        Storage::disk('public')->put($oldPath, 'old');
        $user = User::factory()->create(['avatar_path' => $oldPath]);

        $this->actingAs($user)->post(route('profile.avatar.store'), [
            'avatar' => $this->imageUpload('png'),
        ])->assertRedirect();

        $newPath = $user->fresh()->avatar_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_avatar_removal_is_owned_and_idempotent(): void
    {
        Storage::fake('public');
        $path = 'avatars/owned.jpg';
        Storage::disk('public')->put($path, 'owned');
        $user = User::factory()->create(['avatar_path' => $path]);
        $other = User::factory()->create(['avatar_path' => 'avatars/other.jpg']);
        Storage::disk('public')->put($other->avatar_path, 'other');

        $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertRedirect();
        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($other->avatar_path);

        $this->actingAs($user)->delete(route('profile.avatar.destroy'))
            ->assertRedirect()
            ->assertSessionHas('status', 'avatar-removed');
    }

    public function test_account_deletion_requires_correct_password(): void
    {
        $user = User::factory()->create(['password' => 'DeletePass123']);

        $this->actingAs($user)->delete(route('profile.destroy'))
            ->assertSessionHasErrors(['password'], null, 'accountDeletion');
        $this->assertDatabaseHas('users', ['id' => $user->getKey()]);

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'WrongPass123'])
            ->assertSessionHasErrors(['password'], null, 'accountDeletion');
        $this->assertDatabaseHas('users', ['id' => $user->getKey()]);
    }

    public function test_account_deletion_removes_user_avatar_and_authentication(): void
    {
        Storage::fake('public');
        $path = 'avatars/delete-me.jpg';
        Storage::disk('public')->put($path, 'avatar');
        $user = User::factory()->create([
            'password' => 'DeletePass123',
            'avatar_path' => $path,
        ]);

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'DeletePass123'])
            ->assertRedirectToRoute('home')
            ->assertSessionHas('status', 'account-deleted');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->getKey()]);
        Storage::disk('public')->assertMissing($path);

        $this->post(route('login.store'), [
            'login' => $user->gmail,
            'password' => 'DeletePass123',
        ])->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(User $user, array $overrides = []): array
    {
        return [
            'username' => $user->username,
            'gmail' => $user->gmail,
            'phone' => $user->phone,
            ...$overrides,
        ];
    }

    private function imageUpload(string $extension): UploadedFile
    {
        $images = [
            'jpg' => '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAEf/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9k=',
            'png' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            'webp' => 'UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v89WAAAAA==',
        ];

        return UploadedFile::fake()->createWithContent("avatar.{$extension}", base64_decode($images[$extension], true));
    }
}
