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
        // Take tutor's sessions that fall within the date range sent by FullCalendar (start/end)
        $sessions = TutoringSession::where('tutor_id', auth()->id())
            ->whereBetween('date', [$request->start, $request->end])
            ->where('status', '!=', 'Cancelled')
            ->with('student') // To show student name in the title
            ->get();

        $tutorTz = auth()->user()->time_zone ?? 'UTC';

        $events = $sessions->map(function ($session) use ($tutorTz) {
            $start = Carbon::createFromFormat('Y-m-d H:i:s', $session->date->format('Y-m-d') . ' ' . $session->start_time, $tutorTz);
            list($h, $m) = explode(':', $session->duration);
            $end = $start->copy()->addHours((int)$h)->addMinutes((int)$m);
            $startIso = $start->toIso8601String();
            $endIso = $end->toIso8601String();

            return [
                'id'                => $session->id,
                'title'             => "{$session->tutor->last_name} | {$session->subject}",
                'start'             => $startIso,
                'end'               => $endIso,
                'allDay'            => false,
                'backgroundColor'   => '#4f46e5',
                'borderColor'       => '#4338ca',
                'extendedProps' => [
                    'studentId'     => (string)$session->student_id,
                    'tutorId'       => (string)$session->tutor_id,
                    'subject'       => $session->subject,
                    'duration'      => $session->duration,
                    'location'      => $session->location,
                    'isRecurring'       => !empty($session->recurring_id),
                    'isInitial'         => (bool) $session->is_initial,
                    'isRecurringWeekly' => (bool) $session->recurs_weekly,
                ]
            ];
        });

        return response()->json($events);
    }
}
