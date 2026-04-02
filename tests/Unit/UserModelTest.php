<?php

namespace Tests\Unit;

use App\Models\Credit;
use App\Models\SubjectRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_name_accessor_returns_first_and_last_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ivan',
            'last_name' => 'Petrov',
        ]);

        $this->assertSame('Ivan Petrov', $user->full_name);
    }

    public function test_scope_is_tutor_returns_tutors_and_can_tutor_users(): void
    {
        User::factory()->tutor()->create();
        User::factory()->create(['can_tutor' => true]);
        User::factory()->customer()->create();

        $this->assertEquals(2, User::isTutor()->count());
    }

    public function test_credit_relation_returns_credit_record(): void
    {
        $user = User::factory()->customer()->create();

        Credit::create([
            'user_id' => $user->id,
            'credit_balance' => 25.00,
            'dollar_cost_per_credit' => 20.00,
        ]);

        $this->assertSame(25.00, $user->credit->credit_balance);
    }

    public function test_parent_students_relation_works(): void
    {
        $parent = User::factory()->customer()->create();
        $student = User::factory()->student()->create(['parent_id' => $parent->id]);

        $this->assertTrue($student->parent->is($parent));
        $this->assertTrue($parent->students->contains($student));
    }

    public function test_assigned_students_relation_uses_pivot_table(): void
    {
        $tutor = User::factory()->tutor()->create();
        $student = User::factory()->student()->create();

        $tutor->assignedStudents()->attach($student->id, ['hourly_payout' => 27.50]);

        $this->assertTrue($tutor->assignedStudents->contains($student));
    }

    public function test_subject_rates_relation_returns_student_rates(): void
    {
        $student = User::factory()->student()->create();

        SubjectRate::create([
            'student_id' => $student->id,
            'subject' => 'Math',
            'rate' => 65.00,
        ]);

        $this->assertSame(65.00, $student->subjectRates->first()->rate);
    }
}
