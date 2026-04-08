<?php

namespace Tests\Feature\Admin;

use App\Models\Credit;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_can_create_session(): void
    {
        Notification::fake();

        $admin   = User::factory()->admin()->create();
        $tutor   = User::factory()->tutor()->create();
        $customer = User::factory()->customer()->create();
        Credit::create(['user_id' => $customer->id, 'credit_balance' => 50, 'dollar_cost_per_credit' => 15]);
        $student = User::factory()->student()->create(['parent_id' => $customer->id]);

        $this->actingAs($admin)
            ->postJson('/admin/sessions', [
                'tutor_id'    => $tutor->id,
                'student_id'  => $student->id,
                'subject'     => 'Math',
                'date'        => now()->addDays(2)->format('Y-m-d'),
                'time_h'      => '10',
                'time_m'      => '00',
                'time_ampm'   => 'AM',
                'duration'    => 60,
                'is_initial'  => false,
                'recurs_weekly' => false,
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tutoring_sessions', [
            'tutor_id'   => $tutor->id,
            'student_id' => $student->id,
            'subject'    => 'Math',
        ]);
    }

    // ── Destroy single ────────────────────────────────────────────────────────

    public function test_admin_can_delete_single_session(): void
    {
        $admin   = User::factory()->admin()->create();
        $tutor   = User::factory()->tutor()->create();
        $student = User::factory()->student()->create();

        $session = $this->makeSession($tutor, $student);

        $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->deleteJson("/admin/sessions/{$session->id}")
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('tutoring_sessions', ['id' => $session->id]);
    }

    // ── Destroy series ────────────────────────────────────────────────────────

    public function test_admin_delete_series_removes_future_unbilled_sessions(): void
    {
        $admin       = User::factory()->admin()->create();
        $tutor       = User::factory()->tutor()->create();
        $student     = User::factory()->student()->create();
        $recurringId = 'series-test-001';

        // 3 future scheduled sessions in the series
        $first  = $this->makeSession($tutor, $student, ['recurring_id' => $recurringId, 'date' => now()->addDay()->format('Y-m-d')]);
        $second = $this->makeSession($tutor, $student, ['recurring_id' => $recurringId, 'date' => now()->addDays(8)->format('Y-m-d')]);
        $billed = $this->makeSession($tutor, $student, ['recurring_id' => $recurringId, 'date' => now()->addDays(15)->format('Y-m-d'), 'status' => 'Billed']);

        $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->deleteJson("/admin/sessions/{$first->id}", ['delete_series' => true])
            ->assertJson(['success' => true]);

        // Scheduled sessions gone
        $this->assertDatabaseMissing('tutoring_sessions', ['id' => $first->id]);
        $this->assertDatabaseMissing('tutoring_sessions', ['id' => $second->id]);

        // Billed session preserved
        $this->assertDatabaseHas('tutoring_sessions', ['id' => $billed->id]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_session(): void
    {
        Notification::fake();

        $admin   = User::factory()->admin()->create();
        $tutor   = User::factory()->tutor()->create();
        $student = User::factory()->student()->create();

        $session = $this->makeSession($tutor, $student);

        $this->actingAs($admin)
            ->putJson("/admin/sessions/{$session->id}", [
                'tutor_id'   => $tutor->id,
                'student_id' => $student->id,
                'subject'    => 'Science',
                'date'       => now()->addDays(3)->format('Y-m-d'),
                'time_h'     => '11',
                'time_m'     => '00',
                'time_ampm'  => 'AM',
                'duration'   => 60,
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tutoring_sessions', [
            'id'      => $session->id,
            'subject' => 'Science',
        ]);
    }
}
