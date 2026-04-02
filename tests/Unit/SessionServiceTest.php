<?php

namespace Tests\Unit;

use App\Models\Agreement;
use App\Models\AgreementRequest;
use App\Models\Credit;
use App\Models\SubjectRate;
use App\Models\TutoringSession;
use App\Models\User;
use App\Notifications\SessionScheduled;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SessionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_creates_session_and_notifies_if_subscribed(): void
    {
        Notification::fake();

        $parent = User::factory()->customer()->create(['is_subscribed' => true]);
        Credit::create(['user_id' => $parent->id, 'credit_balance' => 100.00, 'dollar_cost_per_credit' => 15.00]);

        $student = User::factory()->student()->create(['parent_id' => $parent->id]);
        $tutor = User::factory()->tutor()->create(['is_subscribed' => true]);

        $service = new SessionService();

        $sessions = $service->schedule([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'Math',
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'duration' => '1:00',
            'location' => 'Online',
            'is_initial' => true,
            'recurs_weekly' => false,
        ]);

        $this->assertCount(1, $sessions);
        $this->assertSame('Scheduled', $sessions[0]->status);
        $this->assertTrue($sessions[0]->is_initial);

        Notification::assertSentTo([$tutor], SessionScheduled::class);
        Notification::assertSentTo([$parent], SessionScheduled::class);
    }

    public function test_schedule_creates_recurring_sessions(): void
    {
        $parent = User::factory()->customer()->create(['is_subscribed' => true]);
        Credit::create(['user_id' => $parent->id, 'credit_balance' => 1000.00, 'dollar_cost_per_credit' => 15.00]);

        $student = User::factory()->student()->create(['parent_id' => $parent->id]);
        $tutor = User::factory()->tutor()->create(['is_subscribed' => false]);

        $service = new SessionService();

        $sessions = $service->schedule([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'Science',
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'duration' => '1:00',
            'location' => 'Online',
            'is_initial' => false,
            'recurs_weekly' => true,
        ]);

        $this->assertCount(12, $sessions);
        $this->assertNotNull($sessions[0]->recurring_id);
    }

    public function test_schedule_throws_if_student_has_no_parent(): void
    {
        $student = User::factory()->student()->create();
        $tutor = User::factory()->tutor()->create();

        $this->expectExceptionMessage('This student has no parent/client assigned. Check User Directory.');

        $service = new SessionService();
        $service->schedule([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'English',
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'duration' => '1:00',
            'location' => 'Online',
            'recurs_weekly' => false,
        ]);
    }

    public function test_schedule_throws_when_parent_has_no_credit(): void
    {
        $parent = User::factory()->customer()->create();
        $student = User::factory()->student()->create(['parent_id' => $parent->id]);
        $tutor = User::factory()->tutor()->create();

        $this->expectExceptionMessage('Financial record missing for parent:');

        $service = new SessionService();
        $service->schedule([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'English',
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '11:00',
            'duration' => '1:00',
            'location' => 'Online',
            'recurs_weekly' => false,
        ]);
    }

    public function test_schedule_throws_when_parent_has_zero_credits(): void
    {
        $parent = User::factory()->customer()->create();
        Credit::create(['user_id' => $parent->id, 'credit_balance' => 0.00, 'dollar_cost_per_credit' => 10.00]);

        $student = User::factory()->student()->create(['parent_id' => $parent->id]);
        $tutor = User::factory()->tutor()->create();

        $this->expectExceptionMessage('Client has ZERO credits. Please ask parent to refill balance.');

        $service = new SessionService();
        $service->schedule([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'English',
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '11:00',
            'duration' => '1:00',
            'location' => 'Online',
            'recurs_weekly' => false,
        ]);
    }

    public function test_schedule_throws_when_parent_has_pending_agreement(): void
    {
        $parent = User::factory()->customer()->create();
        Credit::create(['user_id' => $parent->id, 'credit_balance' => 100.00, 'dollar_cost_per_credit' => 10.00]);

        $agreement = Agreement::create(['name' => 'Sample', 'pdf_path' => 'agreements/sample.pdf']);
        AgreementRequest::create([
            'agreement_id' => $agreement->id,
            'user_id' => $parent->id,
            'status' => 'Awaiting signature',
        ]);

        $student = User::factory()->student()->create(['parent_id' => $parent->id]);
        $tutor = User::factory()->tutor()->create();

        $this->expectExceptionMessage('Client has unsigned agreements. Cannot schedule.');

        $service = new SessionService();
        $service->schedule([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'History',
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '14:00',
            'duration' => '1:00',
            'location' => 'Online',
            'recurs_weekly' => false,
        ]);
    }

    public function test_bill_session_uses_subject_rate_and_completes_session(): void
    {
        $parent = User::factory()->customer()->create();
        Credit::create(['user_id' => $parent->id, 'credit_balance' => 200.00, 'dollar_cost_per_credit' => 20.00]);

        $student = User::factory()->student()->create(['parent_id' => $parent->id]);
        $tutor = User::factory()->tutor()->create();

        SubjectRate::create([
            'student_id' => $student->id,
            'subject' => 'Math',
            'rate' => 75.00,
        ]);

        $session = TutoringSession::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'subject' => 'Math',
            'date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'duration' => '1:00',
            'location' => 'Online',
            'status' => 'Scheduled',
        ]);

        $service = new SessionService();
        $service->billSession($session);

        $this->assertSame(125.00, $parent->credit->refresh()->credit_balance);
        $this->assertSame('Completed', $session->refresh()->status);
    }
}
