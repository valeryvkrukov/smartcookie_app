<?php

namespace Tests\Feature\Customer;

use App\Models\Agreement;
use App\Models\AgreementRequest;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementTest extends TestCase
{
    use RefreshDatabase;

    private function customerWithCredit(): User
    {
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 10, 'dollar_cost_per_credit' => 15]);
        return $customer;
    }

    private function createPendingAgreement(User $customer): AgreementRequest
    {
        $agreement = Agreement::create(['name' => 'TOS', 'pdf_path' => 'agreements/tos.pdf']);
        return AgreementRequest::create([
            'agreement_id' => $agreement->id,
            'user_id'      => $customer->id,
            'status'       => 'Awaiting signature',
        ]);
    }

    // ── CheckAgreements block ─────────────────────────────────────────────────

    public function test_customer_with_pending_agreement_is_blocked_from_students_page(): void
    {
        $customer = $this->customerWithCredit();
        $this->createPendingAgreement($customer);

        $this->actingAs($customer)
            ->get('/customer/students')
            ->assertRedirect(route('customer.agreements.index'));
    }

    public function test_customer_with_pending_agreement_can_view_agreements_page(): void
    {
        $customer = $this->customerWithCredit();
        $this->createPendingAgreement($customer);

        $this->actingAs($customer)
            ->get('/customer/agreements')
            ->assertOk();
    }

    public function test_customer_without_pending_agreement_is_not_blocked(): void
    {
        $customer = $this->customerWithCredit();

        $this->actingAs($customer)
            ->get('/customer/students')
            ->assertOk();
    }

    // ── Sign agreement ────────────────────────────────────────────────────────

    public function test_customer_can_sign_agreement(): void
    {
        $customer    = $this->customerWithCredit();
        $agreementReq = $this->createPendingAgreement($customer);

        $this->actingAs($customer)
            ->postJson('/customer/agreements/sign', [
                'request_id'         => $agreementReq->id,
                'agree_terms'        => '1',
                'signed_full_name'   => 'John Doe',
                'signed_date_manual' => now()->format('Y-m-d'),
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('agreement_requests', [
            'id'     => $agreementReq->id,
            'status' => 'Signed',
        ]);
    }

    public function test_customer_is_unblocked_after_signing_all_agreements(): void
    {
        $customer    = $this->customerWithCredit();
        $agreementReq = $this->createPendingAgreement($customer);

        // Sign the agreement
        $agreementReq->update(['status' => 'Signed']);

        // Now should pass through CheckAgreements
        $this->actingAs($customer)
            ->get('/customer/students')
            ->assertOk();
    }
}
