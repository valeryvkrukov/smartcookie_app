<?php

namespace App\Http\Controllers\Tutor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\{TutoringSession, Timesheet, Credit, User};
use App\Notifications\CreditBalanceChanged;
use App\Notifications\SessionCompleted;
use Illuminate\Support\Facades\Notification;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        $tutorId = auth()->id();

        // 1. All sessions for this tutor, ordered by date (newest first)
        $sessions = \App\Models\TutoringSession::where('tutor_id', $tutorId)
            ->with('student')
            ->orderBy('date', 'desc')
            ->paginate(15);

        // 2. The same "Pending Logs" (passed, but not filled)
        // We use our scope or write the condition directly:
        $pendingSessions = \App\Models\TutoringSession::where('tutor_id', $tutorId)
            ->where('date', '<', now()->format('Y-m-d'))
            ->where('status', '!=', 'completed')
            ->get();

        return view('tutor.timesheets.index', compact('sessions', 'pendingSessions'));
    }

    public function store(Request $request)
    {
        $session = TutoringSession::with('student.parent.credit')->findOrFail($request->session_id);

        // Atomic check: if the session is already paid, do nothing
        if ($session->status === 'Billed') {
            return back()->with('error', 'This session has already been logged.');
        }

        $parent = $session->student->parent;

        // Calculate credits (0:30 = 0.5)
        $creditsNeeded = Timesheet::calculateCredits($session->duration);

        // Parent balance checking
        if ($parent->credit->credit_balance < $creditsNeeded) {
            return back()->withErrors(['error' => "Insufficient credits! Parent has only {$parent->credit->credit_balance}"]);
        }

        // Calculate hourly payout for the Tutor
        $assignment = DB::table('tutor_student_assignments')
            ->where('tutor_id', $session->tutor_id)
            ->where('student_id', $session->student_id)
            ->first();

        $payout = ($creditsNeeded) * ($assignment->hourly_payout ?? 25.00);

        return DB::transaction(function () use ($session, $parent, $creditsNeeded, $payout) {
            $parent->credit->decrement('credit_balance', $creditsNeeded);
            $parent->credit->refresh();

            // Create record in Timesheet
            Timesheet::create([
                'tutoring_session_id' => $session->id,
                'tutor_id' => $session->tutor_id,
                'parent_id' => $parent->id,
                'credits_spent' => $creditsNeeded,
                'tutor_payout' => $payout,
                'period' => now()->day <= 15 ? '1-15' : '16-end'
            ]);

            // Mark session as completed
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

        $session = TutoringSession::with(['tutor', 'student'])->findOrFail($validated['session_id']);

        if ($session->tutor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $session->update([
            'tutor_notes' => $validated['tutor_notes'],
            'status'      => 'Completed',
        ]);

        // Notify all admins so it appears in System Logs
        $admins = User::where('is_admin', true)->get();
        Notification::send($admins, new SessionCompleted($session));

        return response()->json(['success' => true]);
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
