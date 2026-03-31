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

        $events = $query->with('tutor')->get()->map(function($s) {
            return [
                'id' => $s->id,
                'title' => "{$s->student->first_name} | {$s->subject}",
                'start' => $s->date->format('Y-m-d') . 'T' . $s->start_time,
                'end' => \Carbon\Carbon::parse($s->date->format('Y-m-d') . ' ' . $s->start_time)
                        ->addHours((int)explode(':', $s->duration)[0])
                        ->addMinutes((int)explode(':', $s->duration)[1])
                        ->toIso8601String(),
                'backgroundColor' => $s->status === 'Billed' ? '#10b981' : '#4f46e5',
                'borderColor' => $s->status === 'Billed' ? '#059669' : '#4338ca',
            ];
        });

        return response()->json($events);
    }
}
