<?php

namespace App\Http\Controllers\Tutor;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\{TutoringSession, Timesheet, Credit, User};
use App\Notifications\CreditBalanceChanged;
use App\Notifications\LowCreditBalance;
use App\Notifications\SessionCompleted;
use Illuminate\Support\Facades\Notification;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        $tutorId = auth()->id();

        // ── Sessions: all sessions for this tutor ordered by date ascending
        $sessions = \App\Models\TutoringSession::where('tutor_id', $tutorId)
            ->with('student')
            ->orderBy('date', 'asc')
            ->paginate(config('app.pagination_num', 12));

        // ── Pending sessions: past sessions not yet logged or completed
        // ── Note: using direct condition instead of a named scope
        $pendingSessions = \App\Models\TutoringSession::where('tutor_id', $tutorId)
            ->where('date', '<', now()->format('Y-m-d'))
            ->where('status', '!=', 'completed')
            ->get();

        // ── Assigned students: available for ad-hoc session logging
        $assignedStudents = auth()->user()->assignedStudents()
            ->orderBy('first_name')
            ->get(['users.id', 'users.first_name', 'users.last_name']);

        return view('tutor.timesheets.index', compact('sessions', 'pendingSessions', 'assignedStudents'));
    }

    public function store(Request $request)
    {
        $session = TutoringSession::with(['student.parent.credit', 'student.credit'])->findOrFail($request->session_id);

        // ── Guard: reject if session has already been billed or completed
        if (in_array($session->status, ['Billed', 'Completed'])) {
            return back()->with('error', 'This session has already been logged.');
        }

        // ── Billing party: self-student means the customer is billed directly
        $parent = $session->student->parent ?? $session->student;

        // ── Credits: calculate amount required based on session duration
        $creditsNeeded = Timesheet::calculateCredits($session->duration);

        // ── Guard: reject if credit balance is insufficient
        if ($parent->credit->credit_balance < $creditsNeeded) {
            return back()->withErrors(['error' => "Insufficient credits! Balance: {$parent->credit->credit_balance}"]);
        }

        // ── Payout: compute tutor payout from assignment hourly rate
        $assignment = DB::table('tutor_student_assignments')
            ->where('tutor_id', $session->tutor_id)
            ->where('student_id', $session->student_id)
            ->first();

        $payout = ($creditsNeeded) * ($assignment->hourly_payout ?? 25.00);

        return DB::transaction(function () use ($session, $parent, $creditsNeeded, $payout) {
            $parent->credit->decrement('credit_balance', $creditsNeeded);
            $parent->credit->refresh();

            // ── Timesheet: create billing record
            Timesheet::create([
                'tutoring_session_id' => $session->id,
                'tutor_id' => $session->tutor_id,
                'parent_id' => $parent->id,
                'credits_spent' => $creditsNeeded,
                'tutor_payout' => $payout,
                'period' => now()->day <= 15 ? '1-15' : '16-end'
            ]);

            // ── Status: mark session as Billed
            $session->update(['status' => 'Billed']);

            $parent->notify(new CreditBalanceChanged(
                amount: (float) $creditsNeeded,
                direction: 'debit',
                balanceAfter: (float) $parent->credit->credit_balance,
                reason: 'Timesheet logged for '.$session->subject.' on '.$session->date->format('Y-m-d')
            ));

            return redirect()->back()->with('success', 'Session logged and credits deducted!');
        });
    }

    public function log(Request $request)
    {
        $validated = $request->validate([
            'session_id'  => 'required|integer|exists:tutoring_sessions,id',
            'tutor_notes' => 'required|string|min:10|max:3000',
        ]);

        $session = TutoringSession::with(['tutor', 'student.parent.credit', 'student.credit'])
            ->findOrFail($validated['session_id']);

        if ($session->tutor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        if ($session->status === 'Billed') {
            return response()->json(['success' => false, 'message' => 'This session has already been billed.'], 422);
        }

        // ── Guard: reject if session end time is still in the future
        $tutorTz = $session->tutor->time_zone ?? 'UTC';
        $sessionEnd = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $session->date->format('Y-m-d') . ' ' . $session->start_time,
            $tutorTz
        )->addMinutes($session->duration);

        if ($sessionEnd->isFuture()) {
            $endsIn = $sessionEnd->diffForHumans(Carbon::now($tutorTz), ['parts' => 1]);
            return response()->json([
                'success' => false,
                'message' => "Session hasn't ended yet. It finishes {$endsIn}.",
            ], 422);
        }

        // ── Billing party: self-student is billed directly (no separate parent account)
        $parent        = $session->student->parent ?? $session->student;
        $creditsNeeded = Timesheet::calculateCredits($session->duration);

        if ($parent->credit->credit_balance < $creditsNeeded) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient credits. Customer has {$parent->credit->credit_balance} credit(s), {$creditsNeeded} required.",
            ], 422);
        }

        $assignment = DB::table('tutor_student_assignments')
            ->where('tutor_id', $session->tutor_id)
            ->where('student_id', $session->student_id)
            ->first();

        $payout = $creditsNeeded * ($assignment->hourly_payout ?? 25.00);

        return DB::transaction(function () use ($session, $parent, $creditsNeeded, $payout, $validated) {
            $parent->credit->decrement('credit_balance', $creditsNeeded);
            $parent->credit->refresh();

            Timesheet::create([
                'tutoring_session_id' => $session->id,
                'tutor_id'   => $session->tutor_id,
                'parent_id'  => $parent->id,
                'credits_spent' => $creditsNeeded,
                'tutor_payout'  => $payout,
                'period' => now()->day <= 15 ? '1-15' : '16-end',
            ]);

            $session->update([
                'tutor_notes' => $validated['tutor_notes'],
                'status'      => 'Completed',
            ]);

            $parent->notify(new CreditBalanceChanged(
                amount: (float) $creditsNeeded,
                direction: 'debit',
                balanceAfter: (float) $parent->credit->credit_balance,
                reason: 'Session completed: ' . $session->subject . ' on ' . $session->date->format('Y-m-d'),
            ));

            // ── Low balance: notify client when balance drops to 0.5 credits or below
            $balanceAfter = (float) $parent->credit->credit_balance;
            if ($balanceAfter <= 0.5 && ($balanceAfter + $creditsNeeded) >= 0.5) {
                $parent->notify(new LowCreditBalance($balanceAfter));
            }

            $admins = User::where('is_admin', true)->get();
            Notification::send($admins, new SessionCompleted($session));

            return response()->json(['success' => true]);
        });
    }

    public function logAdHoc(Request $request)
    {
        $tutorId = auth()->id();

        $validated = $request->validate([
            'student_id'  => 'required|integer|exists:users,id',
            'subject'     => 'required|string|max:255',
            'date'        => 'required|date|before_or_equal:today',
            'start_time'  => 'required|date_format:H:i',
            'duration'    => 'required|in:30,60,90,120,150,180',
            'tutor_notes' => 'required|string|min:10|max:3000',
        ]);

        // ── Authorization: student must be assigned to this tutor
        $assignment = DB::table('tutor_student_assignments')
            ->where('tutor_id', $tutorId)
            ->where('student_id', $validated['student_id'])
            ->first();

        if (! $assignment) {
            return response()->json(['success' => false, 'message' => 'This student is not assigned to you.'], 403);
        }

        // ── Guard: session end must already be in the past
        $tutorTz = auth()->user()->time_zone ?? 'UTC';
        $sessionEnd = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $validated['date'] . ' ' . $validated['start_time'] . ':00',
            $tutorTz
        )->addMinutes((int) $validated['duration']);

        if ($sessionEnd->isFuture()) {
            $endsIn = $sessionEnd->diffForHumans(Carbon::now($tutorTz), ['parts' => 1]);
            return response()->json([
                'success' => false,
                'message' => "That session hasn't ended yet — it finishes {$endsIn}.",
            ], 422);
        }

        $student = User::with(['parent.credit', 'credit'])->findOrFail($validated['student_id']);
        $parent  = $student->parent ?? $student;

        $creditsNeeded = Timesheet::calculateCredits($validated['duration']);

        if ($parent->credit->credit_balance < $creditsNeeded) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient credits. Customer has {$parent->credit->credit_balance} credit(s), {$creditsNeeded} required.",
            ], 422);
        }

        $payout = $creditsNeeded * ($assignment->hourly_payout ?? 25.00);

        return DB::transaction(function () use ($tutorId, $validated, $parent, $creditsNeeded, $payout) {
            $session = TutoringSession::create([
                'tutor_id'    => $tutorId,
                'student_id'  => $validated['student_id'],
                'subject'     => $validated['subject'],
                'date'        => $validated['date'],
                'start_time'  => $validated['start_time'] . ':00',
                'duration'    => $validated['duration'],
                'status'      => 'Completed',
                'tutor_notes' => $validated['tutor_notes'],
            ]);

            $parent->credit->decrement('credit_balance', $creditsNeeded);
            $parent->credit->refresh();

            Timesheet::create([
                'tutoring_session_id' => $session->id,
                'tutor_id'            => $tutorId,
                'parent_id'           => $parent->id,
                'credits_spent'       => $creditsNeeded,
                'tutor_payout'        => $payout,
                'period'              => now()->day <= 15 ? '1-15' : '16-end',
            ]);

            $parent->notify(new CreditBalanceChanged(
                amount: (float) $creditsNeeded,
                direction: 'debit',
                balanceAfter: (float) $parent->credit->credit_balance,
                reason: 'Session completed: ' . $validated['subject'] . ' on ' . $validated['date'],
            ));

            $balanceAfter = (float) $parent->credit->credit_balance;
            if ($balanceAfter <= 0.5 && ($balanceAfter + $creditsNeeded) >= 0.5) {
                $parent->notify(new LowCreditBalance($balanceAfter));
            }

            $admins = User::where('is_admin', true)->get();
            Notification::send($admins, new SessionCompleted($session));

            return response()->json(['success' => true]);
        });
    }

    public function destroy(TutoringSession $session)    {
        if ($session->status === 'Billed') {
            $session->loadMissing('student.parent.credit');
            $parentCredit = $session->student->parent->credit;
            $cost = ($session->duration === '0:30') ? 0.5 : (float)$session->duration;
            $parentCredit->increment('credit_balance', $cost);
            $parentCredit->refresh();

            if ($session->student?->parent) {
                $session->student->parent->notify(new CreditBalanceChanged(
                    amount: (float) $cost,
                    direction: 'credit',
                    balanceAfter: (float) $parentCredit->credit_balance,
                    reason: 'Credit restored after billed session removal'
                ));
            }
        }

        $session->delete();

        return back()->with('success', 'Duplicate session removed and credits restored.');
    }
}
