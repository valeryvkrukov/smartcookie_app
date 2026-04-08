<?php

namespace Tests\Feature\Admin;

use App\Models\Credit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_non_admin_cannot_access_user_list(): void
    {
        $tutor = User::factory()->tutor()->create();

        $this->actingAs($tutor)
            ->get('/admin/users')
            ->assertRedirect('/dashboard');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_user(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->customer()->create();
        Credit::create(['user_id' => $target->id, 'credit_balance' => 0, 'dollar_cost_per_credit' => 15]);

        $this->actingAs($admin)
            ->put("/admin/users/{$target->id}", [
                'first_name' => 'Updated',
                'last_name'  => 'Name',
                'email'      => $target->email,
                'role'       => 'customer',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id'         => $target->id,
            'first_name' => 'Updated',
            'last_name'  => 'Name',
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_user(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->customer()->create();

        $this->actingAs($admin)
            ->delete("/admin/users/{$target->id}")
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    // ── Apply payment ─────────────────────────────────────────────────────────

    public function test_admin_can_apply_manual_payment(): void
    {
        Notification::fake();

        $admin    = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 5.0, 'dollar_cost_per_credit' => 15.0]);

        $this->actingAs($admin)
            ->post("/admin/users/{$customer->id}/apply-payment", [
                'total_paid'     => 150.00,
                'credits'        => 10.0,
                'payment_method' => 'venmo',
                'note'           => 'Test payment',
            ])
            ->assertRedirect(route('admin.users.edit', $customer->id));

        $this->assertDatabaseHas('credits', [
            'user_id'        => $customer->id,
            'credit_balance' => 15.0,
        ]);

        $this->assertDatabaseHas('credit_purchases', [
            'user_id' => $customer->id,
            'credits_purchased' => 10.0,
        ]);
    }

    public function test_apply_payment_returns_403_for_non_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $tutor = User::factory()->tutor()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$tutor->id}/apply-payment", [
                'total_paid'     => 100.00,
                'credits'        => 5.0,
                'payment_method' => 'cash',
            ])
            ->assertForbidden();
    }
}
