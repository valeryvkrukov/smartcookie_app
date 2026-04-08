<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\AgreementRequest;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests that role-based access control and CheckAgreements middleware work correctly.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    // ── Guest redirects ───────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_tutor_calendar(): void
    {
        $this->get('/tutor/calendar')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_admin_users(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_customer_calendar(): void
    {
        $this->get('/customer/calendar')->assertRedirect('/login');
    }

    // ── Role isolation: customer ──────────────────────────────────────────────

    public function test_customer_cannot_access_tutor_routes(): void
    {
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 10, 'dollar_cost_per_credit' => 15]);

        $this->actingAs($customer)
            ->get('/tutor/calendar')
            ->assertRedirect('/dashboard');
    }

    public function test_customer_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 10, 'dollar_cost_per_credit' => 15]);

        $this->actingAs($customer)
            ->get('/admin/users')
            ->assertRedirect('/dashboard');
    }

    // ── Role isolation: tutor ─────────────────────────────────────────────────

    public function test_tutor_cannot_access_admin_routes(): void
    {
        $tutor = User::factory()->tutor()->create();

        $this->actingAs($tutor)
            ->get('/admin/users')
            ->assertRedirect('/dashboard');
    }

    public function test_tutor_cannot_access_customer_routes(): void
    {
        $tutor = User::factory()->tutor()->create();

        $this->actingAs($tutor)
            ->get('/customer/calendar')
            ->assertRedirect('/dashboard');
    }

    // ── Role isolation: admin ─────────────────────────────────────────────────

    public function test_admin_can_access_user_management(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();
    }

    // ── CheckAgreements middleware ────────────────────────────────────────────

    public function test_customer_with_pending_agreement_is_blocked_from_calendar(): void
    {
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 10, 'dollar_cost_per_credit' => 15]);

        $agreement = Agreement::create(['name' => 'TOS', 'pdf_path' => 'agreements/test.pdf']);
        AgreementRequest::create([
            'agreement_id' => $agreement->id,
            'user_id'      => $customer->id,
            'status'       => 'Awaiting signature',
        ]);

        $this->actingAs($customer)
            ->get('/customer/calendar')
            ->assertRedirect(route('customer.agreements.index'));
    }

    public function test_customer_with_pending_agreement_can_access_agreements_page(): void
    {
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 10, 'dollar_cost_per_credit' => 15]);

        $agreement = Agreement::create(['name' => 'TOS', 'pdf_path' => 'agreements/test.pdf']);
        AgreementRequest::create([
            'agreement_id' => $agreement->id,
            'user_id'      => $customer->id,
            'status'       => 'Awaiting signature',
        ]);

        $this->actingAs($customer)
            ->get('/customer/agreements')
            ->assertOk();
    }

    public function test_customer_without_pending_agreement_can_access_calendar(): void
    {
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 10, 'dollar_cost_per_credit' => 15]);

        $this->actingAs($customer)
            ->get('/customer/calendar')
            ->assertOk();
    }
}
