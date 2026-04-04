<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\TutoringSession;
use App\Models\User;
use App\Notifications\SessionCancelledByClient;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->is_self_student) {
            $students = collect([$user]);
        } else {
            $students = User::where('parent_id', $user->id)
                ->where('role', 'student')
                ->orderBy('first_name')
                ->get();
        }

        $selectedStudentId = $request->query('student_id');

        return view('customer.calendar.index', compact('students', 'selectedStudentId'));
    }

    public function events(Request $request)
    {
        $query = TutoringSession::where(function ($q) {
            // normal parent: sessions of their children
            $q->whereHas('student', fn($sq) => $sq->where('parent_id', auth()->id()))
              // self-student: sessions where the customer IS the student
              ->orWhere('student_id', auth()->id());
        });

        // Optional filtering by student
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $events = $query->with('tutor', 'student')->get()->map(function($s) {
            $tutorTz = $s->tutor->time_zone ?? 'UTC';
            $start = Carbon::createFromFormat('Y-m-d H:i:s', $s->date->format('Y-m-d') . ' ' . $s->start_time, $tutorTz);
            $end = $start->copy()
                ->addHours((int)explode(':', $s->duration)[0])
                ->addMinutes((int)explode(':', $s->duration)[1]);

            $colors = match(true) {
                $s->status === 'Cancelled'                  => ['bg' => '#94a3b8', 'border' => '#64748b'],
                in_array($s->status, ['Billed','Completed']) => ['bg' => '#10b981', 'border' => '#059669'],
                (bool)$s->is_initial                        => ['bg' => '#f59e0b', 'border' => '#d97706'],
                !empty($s->recurring_id)                    => ['bg' => '#6366f1', 'border' => '#4f46e5'],
                default                                     => ['bg' => '#4f46e5', 'border' => '#4338ca'],
            };

            $recurringPrefix = !empty($s->recurring_id) ? '↻ ' : '';

            return [
                'id'              => $s->id,
                'title'           => $recurringPrefix . "{$s->student->first_name} | {$s->subject}",
                'start'           => $start->toIso8601String(),
                'end'             => $end->toIso8601String(),
                'backgroundColor' => $colors['bg'],
                'borderColor'     => $colors['border'],
                'extendedProps'   => [
                    'subject'     => $s->subject,
                    'duration'    => $s->duration,
                    'status'      => $s->status,
                    'tutorName'   => $s->tutor?->full_name ?? 'TBD',
                    'recurringId' => $s->recurring_id,
                ],
            ];
        });

        return response()->json($events);
    }

    public function cancel(TutoringSession $session)
    {
        $user = auth()->user();

        $authorized = ($session->student->parent_id === $user->id)
            || ($user->is_self_student && $session->student_id === $user->id);

        if (! $authorized) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($session->status !== 'Scheduled') {
            return response()->json(['success' => false, 'message' => 'Only scheduled sessions can be cancelled.'], 422);
        }

        $tutorTz      = $session->tutor?->time_zone ?? 'UTC';
        $sessionStart = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $session->date->format('Y-m-d') . ' ' . $session->start_time,
            $tutorTz
        );

        if (! $sessionStart->gt(Carbon::now($tutorTz)->addHours(24))) {
            return response()->json(['success' => false, 'message' => 'Sessions can only be cancelled more than 24 hours in advance.'], 422);
        }

        $cancelSeries = request()->boolean('series');

        if ($cancelSeries && $session->recurring_id) {
            TutoringSession::where('recurring_id', $session->recurring_id)
                ->where('status', 'Scheduled')
                ->where('date', '>=', $session->date)
                ->update(['status' => 'Cancelled']);
        } else {
            $session->update(['status' => 'Cancelled']);
        }

        if ($session->tutor?->is_subscribed) {
            $session->tutor->notify(new SessionCancelledByClient($session, $user));
        }

        return response()->json(['success' => true]);
    }
}
