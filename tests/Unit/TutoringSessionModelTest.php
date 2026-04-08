<?php

namespace Tests\Unit;

use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutoringSessionModelTest extends TestCase
{
    use RefreshDatabase;

    // ── duration_label accessor ───────────────────────────────────────────────

    public function test_duration_label_30_minutes(): void
    {
        $session = new TutoringSession(['duration' => 30]);
        $this->assertSame('30m', $session->duration_label);
    }

    public function test_duration_label_60_minutes(): void
    {
        $session = new TutoringSession(['duration' => 60]);
        $this->assertSame('1h', $session->duration_label);
    }

    public function test_duration_label_90_minutes(): void
    {
        $session = new TutoringSession(['duration' => 90]);
        $this->assertSame('1.5h', $session->duration_label);
    }

    public function test_duration_label_120_minutes(): void
    {
        $session = new TutoringSession(['duration' => 120]);
        $this->assertSame('2h', $session->duration_label);
    }

    public function test_duration_label_150_minutes(): void
    {
        $session = new TutoringSession(['duration' => 150]);
        $this->assertSame('2.5h', $session->duration_label);
    }

    // ── duration cast ─────────────────────────────────────────────────────────

    public function test_duration_is_cast_to_integer(): void
    {
        $tutor   = User::factory()->tutor()->create();
        $student = User::factory()->student()->create();

        $session = TutoringSession::create([
            'tutor_id'   => $tutor->id,
            'student_id' => $student->id,
            'subject'    => 'Math',
            'date'       => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'duration'   => 60,
            'status'     => 'Scheduled',
        ]);

        $this->assertIsInt($session->duration);
        $this->assertSame(60, $session->fresh()->duration);
    }

    // ── pendingLog scope ──────────────────────────────────────────────────────

    public function test_pending_log_scope_returns_past_scheduled_sessions_without_notes(): void
    {
        $tutor   = User::factory()->tutor()->create();
        $student = User::factory()->student()->create();

        // Past + Scheduled + no notes → should appear
        $pending = TutoringSession::create([
            'tutor_id'   => $tutor->id,
            'student_id' => $student->id,
            'subject'    => 'Math',
            'date'       => now()->subDay()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'duration'   => 60,
            'status'     => 'Scheduled',
            'tutor_notes' => null,
        ]);

        // Future → should NOT appear
        TutoringSession::create([
            'tutor_id'   => $tutor->id,
            'student_id' => $student->id,
            'subject'    => 'Science',
            'date'       => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'duration'   => 60,
            'status'     => 'Scheduled',
        ]);

        // Already has notes → should NOT appear
        TutoringSession::create([
            'tutor_id'    => $tutor->id,
            'student_id'  => $student->id,
            'subject'     => 'History',
            'date'        => now()->subDay()->format('Y-m-d'),
            'start_time'  => '11:00:00',
            'duration'    => 60,
            'status'      => 'Scheduled',
            'tutor_notes' => 'Already logged',
        ]);

        $result = TutoringSession::pendingLog()->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($pending));
    }
}
