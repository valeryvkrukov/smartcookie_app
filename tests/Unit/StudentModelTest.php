<?php

namespace Tests\Unit;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentModelTest extends TestCase
{
    use RefreshDatabase;

    // ── active global scope ───────────────────────────────────────────────────

    public function test_active_scope_excludes_inactive_students(): void
    {
        $active   = User::factory()->student()->create(['is_inactive' => false]);
        $inactive = User::factory()->student()->create(['is_inactive' => true]);

        $results = Student::all();

        $this->assertTrue($results->contains($active));
        $this->assertFalse($results->contains($inactive));
    }

    // ── withInactive scope ────────────────────────────────────────────────────

    public function test_with_inactive_scope_includes_inactive_students(): void
    {
        $active   = User::factory()->student()->create(['is_inactive' => false]);
        $inactive = User::factory()->student()->create(['is_inactive' => true]);

        $results = Student::withInactive()->get();

        $this->assertTrue($results->contains($active));
        $this->assertTrue($results->contains($inactive));
    }

    // ── role global scope ─────────────────────────────────────────────────────

    public function test_role_scope_excludes_non_student_users(): void
    {
        $student  = User::factory()->student()->create();
        $tutor    = User::factory()->tutor()->create();
        $customer = User::factory()->customer()->create();
        $admin    = User::factory()->admin()->create();

        $results = Student::withInactive()->get();

        $this->assertTrue($results->contains($student));
        $this->assertFalse($results->contains($tutor));
        $this->assertFalse($results->contains($customer));
        $this->assertFalse($results->contains($admin));
    }
}
