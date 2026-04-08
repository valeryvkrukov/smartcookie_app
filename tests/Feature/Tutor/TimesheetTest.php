<?php

namespace Tests\Feature\Tutor;

use App\Models\Credit;
use App\Models\Timesheet;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TimesheetTest extends TestCase
{
    use RefreshDatabase;

    private function setup_billing(float $creditBalance = 10.0): array
    {
        $tutor    = User::factory()->tutor()->create();
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => $creditBalance, 'dollar_cost_per_credit' => 15.0]);
        $student  = User::factory()->student()->create(['parent_id' => $customer->id]);

        DB::table('tutor_student_assignments')->insert([
            'tutor_id'      => $tutor->id,
            'student_id'    => $student->id,
            'hourly_payout' => 25.0,
        ]);

        $session = TutoringSession::create([
            'tutor_id'   => $tutor->id,
            'student_id' => $student->id,
            'subject'    => 'Math',
            'date'       => now()->subDay()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'duration'   => 60,
            'status'     => 'Scheduled',
        ]);

        return compact('tutor', 'customer', 'student', 'session');
    }

    // ── Session log (POST /tutor/timesheets/session-log) ─────────────────────

    public function test_tutor_can_log_completed_session(): void
    {
        Notification::fake();

        ['tutor' => $tutor, 'customer' => $customer, 'session' => $session] = $this->setup_billing();

        $this->actingAs($tutor)
            ->postJson('/tutor/timesheets/session-log', [
                'session_id'  => $session->id,
                'tutor_notes' => 'Great session, covered quadratic equations.',
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('timesheets', ['tutoring_session_id' => $session->id]);
        $this->assertDatabaseHas('tutoring_sessions', ['id' => $session->id, 'status' => 'Completed']);
        $this->assertDatabaseHas('credits', ['user_id' => $customer->id, 'credit_balance' => 9.0]);
    }

    public function test_tutor_cannot_log_already_billed_session(): void
    {
        ['tutor' => $tutor, 'session' => $session] = $this->setup_billing();

        $session->update(['status' => 'Billed']);

        $this->actingAs($tutor)
            ->postJson('/tutor/timesheets/session-log', [
                'session_id'  => $session->id,
                'tutor_notes' => 'Trying to log a billed session again.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_log_fails_when_insufficient_credits(): void
    {
        Notification::fake();

        // Only 0.4 credits — need 1.0 for a 60-minute session
        ['tutor' => $tutor, 'session' => $session] = $this->setup_billing(creditBalance: 0.4);

        $this->actingAs($tutor)
            ->postJson('/tutor/timesheets/session-log', [
                'session_id'  => $session->id,
                'tutor_notes' => 'Tried to log but balance is too low.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_tutor_cannot_log_another_tutors_session(): void
    {
        ['session' => $session] = $this->setup_billing();
        $otherTutor = User::factory()->tutor()->create();

        $this->actingAs($otherTutor)
            ->postJson('/tutor/timesheets/session-log', [
                'session_id'  => $session->id,
                'tutor_notes' => 'Attempting unauthorized log.',
            ])
            ->assertStatus(403);
    }
}
