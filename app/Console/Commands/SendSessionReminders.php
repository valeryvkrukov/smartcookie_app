<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TutoringSession;
use App\Models\User;
use App\Notifications\SessionReminder;
use App\Notifications\SessionCancelledNoCredits;
use Carbon\Carbon;

class SendSessionReminders extends Command
{
    protected $signature   = 'sessions:send-reminders';
    protected $description = 'Send 30-hour reminder emails to clients. Auto-cancel zero-credit sessions at the 24-hour mark.';

    public function handle(): void
    {
        $now = Carbon::now('UTC');

        // ── 30-hour reminders ───────────────────────────────────────────────
        // Window: sessions whose UTC start time is between 29h50m and 30h10m from now
        // (runs every 10 min / hourly — 20-minute window avoids duplicates if cron
        //  occasionally runs a minute late while still preventing double-sends)
        $windowStart = $now->copy()->addMinutes(29 * 60 + 50);
        $windowEnd   = $now->copy()->addMinutes(30 * 60 + 10);

        $sessions = TutoringSession::where('status', 'Scheduled')
            ->with('tutor', 'student.parent.credit', 'student.credit')
            ->get()
            ->filter(function (TutoringSession $s) use ($windowStart, $windowEnd) {
                $tutorTz   = $s->tutor?->time_zone ?? 'UTC';
                $startUtc  = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $s->date->format('Y-m-d') . ' ' . $s->start_time,
                    $tutorTz
                )->utc();
                return $startUtc->between($windowStart, $windowEnd);
            });

        foreach ($sessions as $session) {
            $billedParty = $session->student?->parent ?? $session->student;
            if (! $billedParty) continue;

            $balance = (float) ($billedParty->credit?->credit_balance ?? 0);

            if ($billedParty->is_subscribed) {
                $billedParty->notify(new SessionReminder($session, $balance));
            }
        }

        $this->info("30h reminders: {$sessions->count()} session(s) processed.");

        // ── 24-hour zero-credit auto-cancellation ───────────────────────────
        $cancelWindowStart = $now->copy()->addMinutes(23 * 60 + 50);
        $cancelWindowEnd   = $now->copy()->addMinutes(24 * 60 + 10);

        $zeroCredit = TutoringSession::where('status', 'Scheduled')
            ->with('tutor', 'student.parent.credit', 'student.credit')
            ->get()
            ->filter(function (TutoringSession $s) use ($cancelWindowStart, $cancelWindowEnd) {
                $tutorTz  = $s->tutor?->time_zone ?? 'UTC';
                $startUtc = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $s->date->format('Y-m-d') . ' ' . $s->start_time,
                    $tutorTz
                )->utc();
                if (! $startUtc->between($cancelWindowStart, $cancelWindowEnd)) {
                    return false;
                }
                $billedParty = $s->student?->parent ?? $s->student;
                $balance     = (float) ($billedParty?->credit?->credit_balance ?? 0);
                return $balance <= 0;
            });

        foreach ($zeroCredit as $session) {
            $session->update(['status' => 'Cancelled']);

            if ($session->tutor?->is_subscribed) {
                $session->tutor->notify(new SessionCancelledNoCredits($session));
            }
        }

        $this->info("24h auto-cancellations: {$zeroCredit->count()} session(s) cancelled.");
    }
}
