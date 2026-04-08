<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // ── Helper ────────────────────────────────────────────────────────────────

    private function profilePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
            'time_zone'  => 'UTC',
        ], $overrides);
    }

    // ── Display ───────────────────────────────────────────────────────────────

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_profile_first_and_last_name_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user, [
                'first_name' => 'Jane',
                'last_name'  => 'Smith',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Jane', $user->first_name);
        $this->assertSame('Smith', $user->last_name);
    }

    public function test_profile_email_change_clears_email_verified_at(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user, [
                'email' => 'new@example.com',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_profile_email_unchanged_keeps_verified_at(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_profile_time_zone_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user, ['time_zone' => 'America/New_York']))
            ->assertSessionHasNoErrors();

        $this->assertSame('America/New_York', $user->fresh()->time_zone);
    }

    public function test_profile_update_requires_valid_time_zone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profilePayload($user, ['time_zone' => 'Not/ATimezone']))
            ->assertSessionHasErrors('time_zone');
    }

    // ── Delete account ────────────────────────────────────────────────────────

    public function test_account_can_be_deleted_with_correct_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_account_deletion_requires_correct_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'wrong-password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
