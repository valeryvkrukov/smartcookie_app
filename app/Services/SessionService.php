<?php

namespace App\Services;

use App\Models\TutoringSession;
use App\Models\User;
use App\Models\AgreementRequest;
use Carbon\Carbon;

class SessionService
{
    /**
     * The main method for creating lessons with checks
     */
    public function schedule(array $data)
    {
        $student = User::with('parent.credit')->findOrFail($data['student_id']);
        
        $parent = $student->parent;

        // Check for Parent/Client
        if (!$parent) {
            throw new \Exception("This student has no parent/client assigned. Check User Directory.");
        }

        // Check for Credit Record
        if (!$parent->credit) {
            throw new \Exception("Financial record missing for parent: {$parent->full_name}. Please update their profile.");
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
            $data['duration'] = '1:00';
            $data['recurs_weekly'] = false;
        }

        // Generate Uniuqe ID for serie of sessions
        $isRecurring = !empty($data['recurs_weekly']);
        $recurringId = $isRecurring ? uniqid('rec_') : null;
        $count = $isRecurring ? 12 : 1;

        $baseDate = Carbon::parse($data['date']);
        $startTime = Carbon::parse($data['start_time']);

        list($hours, $minutes) = explode(':', $data['duration']);

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

            // 1. Notify Tutor (only if they have is_subscribed enabled)
            if ($tutor && $tutor->is_subscribed) {
                $tutor->notify(new SessionScheduled($firstSession, $isRecurring));
            }

            // 2. Notify Parent (only if they have is_subscribed enabled)
            if ($parent && $parent->is_subscribed) {
                $parent->notify(new SessionScheduled($firstSession, $isRecurring));
            }
        }

        return $createdSessions;
    }

    protected function hasConflict($tutorId, $date, $startTime, $duration)
    {
        $start = Carbon::parse("$date $startTime");

        list($hours, $minutes) = explode(':', $duration);
        $end = (clone $start)->addHours((int)$hours)->addMinutes((int)$minutes);

        return TutoringSession::where('tutor_id', $tutorId)
            ->where('date', $date)
            ->where('status', '!=', 'Canceled')
            ->get()
            ->filter(function ($session) use ($start, $end) {
                $sStart = Carbon::parse($session->date->format('Y-m-d') . ' ' . $session->start_time);

                list($h, $m) = explode(':', $session->duration);
                $sEnd = (clone $sStart)->addHours((int)$h)->addMinutes((int)$m);

                // Check for intersection of time intervals
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

        // Mark the session as completed
        $session->update(['status' => 'Completed']);
    }

}