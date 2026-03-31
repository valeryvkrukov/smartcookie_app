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
        return view('tutor.calendar.index');
    }

    public function events(Request $request)
    {
        // Take tutor's sessions that fall within the date range sent by FullCalendar (start/end)
        $sessions = TutoringSession::where('tutor_id', auth()->id())
            ->whereBetween('date', [$request->start, $request->end])
            ->where('status', '!=', 'Cancelled')
            ->with('student') // To show student name in the title
            ->get();

        $events = $sessions->map(function ($session) {
            $startIso = $session->date->format('Y-m-d') . 'T' . $session->start_time;
            
            list($h, $m) = explode(':', $session->duration);
            $endIso = Carbon::parse($startIso)->addHours((int)$h)->addMinutes((int)$m)->format('Y-m-d\TH:i:s');

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
                    'isRecurring'   => !empty($session->recurring_id),
                ]
            ];
        });

        return response()->json($events);
    }
}
