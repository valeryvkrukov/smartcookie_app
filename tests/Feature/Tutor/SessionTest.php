<?php

namespace Tests\Feature\Tutor;

use App\Models\Credit;
use App\Models\SessionSeries;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    private function assignStudent(User $tutor, User $student, float $payout = 25.0): void
    {
        DB::table('tutor_student_assignments')->insert([
            'tutor_id'      => $tutor->id,
            'student_id'    => $student->id,
            'hourly_payout' => $payout,
        ]);
    }

    private function makeSession(User $tutor, User $student, array $overrides = []): TutoringSession
    {
        return TutoringSession::create(array_merge([
            'tutor_id'   => $tutor->id,
            'student_id' => $student->id,
            'subject'    => 'Math',
            'date'       => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'duration'   => 60,
            'status'     => 'Scheduled',
        ], $overrides));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_tutor_can_create_session(): void
    {
        Notification::fake();

        $tutor    = User::factory()->tutor()->create();
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 50, 'dollar_cost_per_credit' => 15]);
        $student  = User::factory()->student()->create(['parent_id' => $customer->id]);
        $this->assignStudent($tutor, $student);

        $this->actingAs($tutor)
            ->postJson('/tutor/sessions', [
                'student_id'  => $student->id,
                'subject'     => 'Math',
                'date'        => now()->addDays(2)->format('Y-m-d'),
                'time_h'      => '10',
                'time_m'      => '00',
                'time_ampm'   => 'AM',
                'duration'    => 60,
                'recurs_weekly' => false,
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tutoring_sessions', [
            'tutor_id'   => $tutor->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_tutor_cannot_create_session_in_the_past(): void
    {
        $tutor    = User::factory()->tutor()->create();
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 50, 'dollar_cost_per_credit' => 15]);
        $student  = User::factory()->student()->create(['parent_id' => $customer->id]);
        $this->assignStudent($tutor, $student);

        $this->actingAs($tutor)
            ->postJson('/tutor/sessions', [
                'student_id'  => $student->id,
                'subject'     => 'Math',
                'date'        => now()->subDay()->format('Y-m-d'),
                'time_h'      => '10',
                'time_m'      => '00',
                'time_ampm'   => 'AM',
                'duration'    => 60,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_tutor_can_delete_own_session(): void
    {
        $tutor   = User::factory()->tutor()->create();
        $student = User::factory()->student()->create();

        $session = $this->makeSession($tutor, $student);

        $this->actingAs($tutor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->deleteJson("/tutor/sessions/{$session->id}")
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('tutoring_sessions', ['id' => $session->id]);
    }

    public function test_tutor_cannot_delete_another_tutors_session(): void
    {
        $tutor1  = User::factory()->tutor()->create();
        $tutor2  = User::factory()->tutor()->create();
        $student = User::factory()->student()->create();

        $session = $this->makeSession($tutor1, $student);

        $this->actingAs($tutor2)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->deleteJson("/tutor/sessions/{$session->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('tutoring_sessions', ['id' => $session->id]);
    }

    public function test_tutor_can_delete_own_recurring_series(): void
    {
        $tutor       = User::factory()->tutor()->create();
        $student     = User::factory()->student()->create();

        $series = SessionSeries::create([
            'tutor_id'   => $tutor->id,
            'student_id' => $student->id,
            'subject'    => 'Math',
            'duration'   => 60,
        ]);

        $first  = $this->makeSession($tutor, $student, ['series_id' => $series->id, 'date' => now()->addDay()->format('Y-m-d')]);
        $second = $this->makeSession($tutor, $student, ['series_id' => $series->id, 'date' => now()->addDays(8)->format('Y-m-d')]);

        $this->actingAs($tutor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->deleteJson("/tutor/sessions/{$first->id}", ['delete_series' => true])
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('tutoring_sessions', ['id' => $first->id]);
        $this->assertDatabaseMissing('tutoring_sessions', ['id' => $second->id]);
    }
}
