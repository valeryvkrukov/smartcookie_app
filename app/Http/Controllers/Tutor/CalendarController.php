<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TutoringSession;
use App\Services\SessionService;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        $students = auth()->user()->assignedStudents()->get(['users.id', 'users.time_zone']);
        return view('tutor.calendar.index', compact('students'));
    }

    public function events(Request $request)
    {
        // ── Sessions: fetch tutor sessions within FullCalendar's requested date range
        $sessions = TutoringSession::where('tutor_id', auth()->id())
            ->whereBetween('date', [$request->start, $request->end])
            ->with('student') // To show student name in the title
            ->get();

        $tutorTz = auth()->user()->time_zone ?? 'UTC';

        $events = $sessions->map(function ($session) use ($tutorTz) {
            $start = Carbon::createFromFormat('Y-m-d H:i:s', $session->date->format('Y-m-d') . ' ' . $session->start_time, $tutorTz);
            $end = $start->copy()->addMinutes($session->duration);
            $startIso = $start->toIso8601String();
            $endIso = $end->toIso8601String();

            $colors = match(true) {
                $session->status === 'Cancelled'              => ['bg' => '#94a3b8', 'border' => '#64748b'],
                in_array($session->status, ['Billed','Completed']) => ['bg' => '#10b981', 'border' => '#059669'],
                (bool)$session->is_initial                   => ['bg' => '#f59e0b', 'border' => '#d97706'],
                !empty($session->recurring_id)               => ['bg' => '#6366f1', 'border' => '#4f46e5'],
                default                                      => ['bg' => '#4f46e5', 'border' => '#4338ca'],
            };

            $recurringPrefix = !empty($session->recurring_id) ? '↻ ' : '';

            return [
                'id'                => $session->id,
                'title'             => $recurringPrefix . ($session->student->first_name ?? '?') . ' | ' . $session->subject,
                'start'             => $startIso,
                'end'               => $endIso,
                'allDay'            => false,
                'backgroundColor'   => $colors['bg'],
                'borderColor'       => $colors['border'],
                'extendedProps' => [
                    'studentId'     => (string)$session->student_id,
                    'tutorId'       => (string)$session->tutor_id,
                    'subject'       => $session->subject,
                    'duration'      => $session->duration,
                    'location'      => $session->location,
                    'status'        => $session->status,
                    'tutorName'     => auth()->user()->full_name,
                    'studentName'   => $session->student?->full_name ?? '?',
                    'isRecurring'       => !empty($session->recurring_id),
                    'isInitial'         => (bool) $session->is_initial,
                    'isRecurringWeekly' => (bool) $session->recurs_weekly,
                    // ── Time props: pre-computed in tutor timezone to avoid client-side TZ bugs
                    'time_h'    => $start->format('h'),
                    'time_m'    => $start->format('i'),
                    'time_ampm' => $start->format('A'),
                ]
            ];
        });

        return response()->json($events);
    }
}
