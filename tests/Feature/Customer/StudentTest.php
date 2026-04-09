<?php

namespace Tests\Feature\Customer;

use App\Models\Credit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    private function customerWithCredit(float $balance = 10.0): User
    {
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => $balance, 'dollar_cost_per_credit' => 15.0]);
        return $customer;
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_customer_can_view_students_page(): void
    {
        $customer = $this->customerWithCredit();

        $this->actingAs($customer)
            ->get('/customer/students')
            ->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_customer_can_create_student(): void
    {
        $customer = $this->customerWithCredit();

        $this->actingAs($customer)
            ->postJson('/customer/students', [
                'first_name'    => 'Alice',
                'last_name'     => 'Smith',
                'address'       => '123 Main St',
                'phone'         => '555-1234',
                'student_grade' => null,
                'blurb'         => null,
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Alice',
            'last_name'  => 'Smith',
            'role'       => 'student',
            'parent_id'  => $customer->id,
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_customer_can_delete_own_student(): void
    {
        $customer = $this->customerWithCredit();
        $student  = User::factory()->student()->create(['parent_id' => $customer->id]);

        $this->actingAs($customer)
            ->delete("/customer/students/{$student->id}")
            ->assertRedirect(route('customer.students.index'));

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_customer_cannot_delete_student_belonging_to_another_customer(): void
    {
        $customer1 = $this->customerWithCredit();
        $customer2 = $this->customerWithCredit();
        $student   = User::factory()->student()->create(['parent_id' => $customer2->id]);

        $this->actingAs($customer1)
            ->delete("/customer/students/{$student->id}")
            ->assertNotFound();
    }

    // ── Toggle self-student ───────────────────────────────────────────────────

    public function test_toggle_to_self_student_sets_flag_without_touching_children(): void
    {
        $customer = $this->customerWithCredit();
        $student1 = User::factory()->student()->create(['parent_id' => $customer->id, 'is_inactive' => false]);
        $student2 = User::factory()->student()->create(['parent_id' => $customer->id, 'is_inactive' => false]);

        $this->actingAs($customer)
            ->post('/customer/students/toggle-self-student')
            ->assertRedirect(route('customer.students.index'));

        $this->assertDatabaseHas('users', ['id' => $customer->id, 'is_self_student' => true]);
        // Child students are not touched
        $this->assertDatabaseHas('users', ['id' => $student1->id, 'is_inactive' => false]);
        $this->assertDatabaseHas('users', ['id' => $student2->id, 'is_inactive' => false]);
    }

    public function test_toggle_back_to_parent_mode_clears_flag_without_touching_children(): void
    {
        $customer = $this->customerWithCredit();
        $customer->update(['is_self_student' => true]);

        $student1 = User::factory()->student()->create(['parent_id' => $customer->id, 'is_inactive' => false]);
        $student2 = User::factory()->student()->create(['parent_id' => $customer->id, 'is_inactive' => false]);

        $this->actingAs($customer)
            ->post('/customer/students/toggle-self-student')
            ->assertRedirect(route('customer.students.index'));

        $this->assertDatabaseHas('users', ['id' => $customer->id, 'is_self_student' => false]);
        // Child students are not touched
        $this->assertDatabaseHas('users', ['id' => $student1->id, 'is_inactive' => false]);
        $this->assertDatabaseHas('users', ['id' => $student2->id, 'is_inactive' => false]);
    }
}
