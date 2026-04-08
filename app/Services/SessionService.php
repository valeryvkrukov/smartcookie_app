<?php

namespace App\Services;

use App\Models\TutoringSession;
use App\Models\User;
use App\Models\AgreementRequest;
use App\Notifications\CreditBalanceChanged;
use App\Notifications\SessionScheduled;
use Carbon\Carbon;

class SessionService
{
    /**
     * The main method for creating lessons with checks
     */
    public function schedule(array $data)
    {
        $student = User::with(['parent.credit', 'credit'])->findOrFail($data['student_id']);
        
        // Self-student: the customer IS the billed party (no separate parent account)
        $parent = $student->parent ?? $student;

        // Check for Credit Record
        if (!$parent->credit) {
            throw new \Exception("Financial record missing for: {$parent->full_name}. Please update their profile.");
        }

        // Check for Credits
        if ($parent->credit->credit_balance <= 0) {
            throw new \Exception("Client has ZERO credits. Please ask parent to refill balance.");
        }

        // Check for Agreements
        $hasPending = AgreementRequest::where('user_id', $parent->id)
            ->where('status', 'Awaiting signature')
            ->exists();
        
        if ($hasPending) {
            throw new \Exception("Client has unsigned agreements. Cannot schedule.");
        }

        // ?? Initial Session logic
        if (!empty($data['is_initial'])) {
            $data['duration'] = 60; // 1 hour in minutes
            $data['recurs_weekly'] = false;
        }

        // Generate Uniuqe ID for serie of sessions
        $isRecurring = !empty($data['recurs_weekly']);
        $recurringId = $isRecurring ? uniqid('rec_') : null;
        $count = $isRecurring ? 12 : 1;

        $baseDate = Carbon::parse($data['date']);
        $startTime = Carbon::parse($data['start_time']);

        $createdSessions = [];

        // Create rec. sessions
        for ($i = 0; $i < $count; $i++) {
            $currentDate = $baseDate->copy()->addWeeks($i);

            if ($this->hasConflict($data['tutor_id'], $currentDate->format('Y-m-d'), $data['start_time'], $data['duration'])) {
                throw new \Exception("Time Conflict: You already have a session on {$currentDate->format('Y-m-d')} at {$data['start_time']}.");
            }
            $sessionData = array_merge($data, [
                'recurring_id' => $recurringId,
                'date'         => $currentDate->format('Y-m-d'),
                'status'       => 'Scheduled',
                // 'is_initial' set only for the first session
                'is_initial'   => ($i === 0) ? ($data['is_initial'] ?? false) : false,
            ]);

            $createdSessions[] = TutoringSession::create($sessionData);
        }

        if (!empty($createdSessions)) {
            $firstSession = $createdSessions[0];
            $tutor = $firstSession->tutor;
            $parent = $student->parent;

            // Determine if this is a "last-minute" schedule (< 30h until start)
            $tutorTz    = $tutor?->time_zone ?? 'UTC';
            $sessionStart = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $firstSession->date->format('Y-m-d') . ' ' . $firstSession->start_time,
                $tutorTz
            );
            $isLastMinute = $sessionStart->diffInHours(Carbon::now($tutorTz), false) > -30;

            // 1. Notify Tutor (only if they have is_subscribed enabled)
            if ($tutor && $tutor->is_subscribed) {
                $tutor->notify(new SessionScheduled($firstSession, $isRecurring));
            }

            // 2. Notify Client (parent or self-student)
            // Always notify immediately — for last-minute sessions this serves as the
            // immediate notification required by spec; for normal sessions it's the
            // standard "session scheduled" email (separate 30h reminder will follow).
            $billedParty = $student->parent ?? $student;
            if ($billedParty && $billedParty->is_subscribed) {
                $billedParty->notify(new SessionScheduled($firstSession, $isRecurring, $isLastMinute));
            }
        }

        return $createdSessions;
    }

    /**
     * $excludeSessionId   – exclude a specific session (e.g. the one being updated)
     * $excludeRecurringId – exclude all sessions in a recurring series (e.g. during series update)
     */
    public function hasConflict($tutorId, $date, $startTime, $duration, $excludeSessionId = null, $excludeRecurringId = null): bool
    {
        $start = Carbon::parse("$date $startTime");

        $end = (clone $start)->addMinutes((int)$duration);

        return TutoringSession::where('tutor_id', $tutorId)
            ->where('date', $date)
            ->where('status', 'Scheduled')   // only future scheduled sessions block a slot
            ->when($excludeSessionId,   fn($q) => $q->where('id', '!=', $excludeSessionId))
            ->when($excludeRecurringId, fn($q) => $q->where(function ($q) use ($excludeRecurringId) {
                $q->whereNull('recurring_id')->orWhere('recurring_id', '!=', $excludeRecurringId);
            }))
            ->get()
            ->filter(function ($session) use ($start, $end) {
                $sStart = Carbon::parse($session->date->format('Y-m-d') . ' ' . $session->start_time);
                $sEnd   = (clone $sStart)->addMinutes($session->duration);

                return $start->lt($sEnd) && $end->gt($sStart);
            })->isNotEmpty();
    }

    public function billSession(TutoringSession $session)
    {
        $student = $session->student;
        
        // Searcch for subject-specific rate for this student
        $subjectRate = $student->subjectRates()
            ->where('subject', $session->subject)
            ->first();

        // If no subject-specific rate is found, use the default rate
        $rate = $subjectRate ? $subjectRate->rate : 50.00; 

        // Decrement the student's parent credit balance by the session rate
        $parentCredit = $student->parent->credit;

        if ($parentCredit->credit_balance < $rate) {
            // TODO: Handle insufficient credits (e.g., mark session as unpaid, notify parent, etc.)
        }

        $parentCredit->decrement('credit_balance', $rate);

        $parentCredit->refresh();

        $student->parent->notify(new CreditBalanceChanged(
            amount: (float) $rate,
            direction: 'debit',
            balanceAfter: (float) $parentCredit->credit_balance,
            reason: 'Session billed: '.$session->subject.' on '.$session->date->format('Y-m-d')
        ));

        // Mark the session as completed
        $session->update(['status' => 'Completed']);
    }

}