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
            $hasCredits = $session->student->parent->credit->credit_balance > 0;

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

        return view('admin.calendar.index', [
            'eventsJson' => $events->toJson(),
            'tutors' => $tutors
        ]);
    }

    public function events(Request $request)
    {
        $query = TutoringSession::with(['tutor', 'student.parent.credit']);

        // Filter by tutor if tutor_id is provided
        if ($request->filled('tutor_id')) {
            $query->where('tutor_id', $request->tutor_id);
        }

        $events = $query->get()->map(function ($session) {
            $startIso = $session->date->format('Y-m-d') . 'T' . $session->start_time;
            
            list($h, $m) = explode(':', $session->duration);
            $endIso = Carbon::parse($startIso)->addHours((int)$h)->addMinutes((int)$m)->format('Y-m-d\TH:i:s');

            $hasCredits = ($session->student->parent->credit->credit_balance ?? 0) > 0;

            return [
                'id'                => $session->id,
                'title'             => ($session->tutor?->last_name ?? 'No Tutor') . " | " . ($session->subject ?? 'No Subject'),
                'start'             => $startIso,
                'end'               => $endIso,
                'backgroundColor'   => $hasCredits ? '#4f46e5' : '#ef4444',
                'borderColor'       => $hasCredits ? '#4338ca' : '#dc2626',
                'extendedProps' => [
                    'studentId'     => (string)$session->student_id,
                    'tutorId'       => (string)$session->tutor_id,
                    'subject'       => (string)$session->subject,
                    'duration'      => (string)$session->duration,
                    'isRecurring'   => !empty($session->recurring_id),
                    //'credits'   => $session->student->parent->credit->credit_balance ?? 0
                ]
            ];
        })->values()->toArray();

        return response()->json($events);
    }
}
