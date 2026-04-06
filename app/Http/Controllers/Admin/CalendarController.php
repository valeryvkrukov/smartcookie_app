<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TutoringSession;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $tutors = User::where('role', 'tutor')->orderBy('last_name')->get();

        // 1. Get ALL sessions
        $query = TutoringSession::with(['tutor', 'student']);

        // 2. Add filter, if Tutor selected
        if ($request->filled('tutor_id')) {
            $query->where('tutor_id', $request->tutor_id);
        }

        $events = TutoringSession::with(['tutor', 'student'])->get()->map(function ($session) {
            $start = Carbon::parse($session->date->format('Y-m-d') . ' ' . $session->start_time);

            list($hours, $minutes) = explode(':', $session->duration);
            $end = $start->copy()->addHours((int)$hours)->addMinutes((int)$minutes);
            $hasCredits = ($session->student?->parent?->credit?->credit_balance ?? 0) > 0;

            $tutorName = $session->tutor?->last_name ?? 'N/A';
            $studentName = $session->student?->last_name ?? 'Guest';

            $title = "{$tutorName}-{$session->duration} {$studentName}";
            
            return [
                'id' => $session->id,
                'title' => $title,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'allDay' => false,
                'backgroundColor' => $hasCredits ? '#4f46e5' : '#ef4444',
                'borderColor' => $hasCredits ? '#4338ca' : '#dc2626',
            ];
        });

        $students = User::where('role', 'student')
            ->orWhere(fn($q) => $q->where('role', 'customer')->where('is_self_student', true))
            ->orderBy('last_name')
            ->get(['id', 'time_zone']);

        return view('admin.calendar.index', [
            'eventsJson' => $events->toJson(),
            'tutors'     => $tutors,
            'students'   => $students,
        ]);
    }

    public function events(Request $request)
    {
        $query = TutoringSession::with(['tutor', 'student.parent.credit']);

        // ── Filter: narrow sessions by tutor_id when provided
        if ($request->filled('tutor_id')) {
            $query->where('tutor_id', $request->tutor_id);
        }

        $events = $query->get()->map(function ($session) {
            $tutorTz = $session->tutor->time_zone ?? 'UTC';
            $start = Carbon::createFromFormat('Y-m-d H:i:s', $session->date->format('Y-m-d') . ' ' . $session->start_time, $tutorTz);
            list($h, $m) = explode(':', $session->duration);
            $end = $start->copy()->addHours((int)$h)->addMinutes((int)$m);
            $startIso = $start->toIso8601String();
            $endIso = $end->toIso8601String();

            $hasCredits = ($session->student?->parent?->credit?->credit_balance ?? 0) > 0;

            $colors = match(true) {
                $session->status === 'Cancelled'                  => ['bg' => '#94a3b8', 'border' => '#64748b'],
                in_array($session->status, ['Billed','Completed']) => ['bg' => '#10b981', 'border' => '#059669'],
                !$hasCredits                                      => ['bg' => '#ef4444', 'border' => '#dc2626'],
                (bool)$session->is_initial                        => ['bg' => '#f59e0b', 'border' => '#d97706'],
                !empty($session->recurring_id)                    => ['bg' => '#6366f1', 'border' => '#4f46e5'],
                default                                           => ['bg' => '#4f46e5', 'border' => '#4338ca'],
            };

            $recurringPrefix = !empty($session->recurring_id) ? '↻ ' : '';

            return [
                'id'                => $session->id,
                'title'             => $recurringPrefix . ($session->tutor?->last_name ?? 'No Tutor') . ' | ' . ($session->subject ?? 'No Subject'),
                'start'             => $startIso,
                'end'               => $endIso,
                'backgroundColor'   => $colors['bg'],
                'borderColor'       => $colors['border'],
                'extendedProps' => [
                    'studentId'     => (string)$session->student_id,
                    'tutorId'       => (string)$session->tutor_id,
                    'subject'       => (string)$session->subject,
                    'duration'      => (string)$session->duration,
                    'location'      => $session->location,
                    'status'        => $session->status,
                    'isRecurring'        => !empty($session->recurring_id),
                    'isInitial'          => (bool) $session->is_initial,
                    'isRecurringWeekly'  => (bool) $session->recurs_weekly,
                    // ── Time props: pre-computed in tutor timezone to avoid client-side TZ bugs
                    'time_h'    => $start->format('h'),
                    'time_m'    => $start->format('i'),
                    'time_ampm' => $start->format('A'),
                ]
            ];
        })->values()->toArray();

        return response()->json($events);
    }
}
