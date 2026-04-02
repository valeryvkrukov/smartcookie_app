<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TutoringSession;
use App\Models\User;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $students = User::where('parent_id', auth()->id())
            ->where('role', 'student')
            ->orderBy('first_name')
            ->get();

        $selectedStudentId = $request->query('student_id');

        return view('customer.calendar.index', compact('students', 'selectedStudentId'));
    }

    public function events(Request $request)
    {
        $query = TutoringSession::whereHas('student', function($q) {
            $q->where('parent_id', auth()->id());
        });

        // Optional filtering by student
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $events = $query->with('tutor', 'student')->get()->map(function($s) {
            $tutorTz = $s->tutor->time_zone ?? 'UTC';
            $start = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $s->date->format('Y-m-d') . ' ' . $s->start_time, $tutorTz);
            $end = $start->copy()
                ->addHours((int)explode(':', $s->duration)[0])
                ->addMinutes((int)explode(':', $s->duration)[1]);
            return [
                'id' => $s->id,
                'title' => "{$s->student->first_name} | {$s->subject}",
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'backgroundColor' => $s->status === 'Billed' ? '#10b981' : '#4f46e5',
                'borderColor' => $s->status === 'Billed' ? '#059669' : '#4338ca',
            ];
        });

        return response()->json($events);
    }
}
