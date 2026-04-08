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

        // "Next 2 sessions" panel
        $studentIds = $user->is_self_student
            ? [$user->id]
            : User::where('parent_id', $user->id)->where('role', 'student')->pluck('id')->toArray();

        $nextSessions = TutoringSession::whereIn('student_id', $studentIds)
            ->where('status', 'Scheduled')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(2)
            ->with('student', 'tutor')
            ->get();

        return view('customer.calendar.index', compact('students', 'selectedStudentId', 'nextSessions'));
    }

    public function events(Request $request)
    {
        $query = TutoringSession::where(function ($q) {
            // ── Parent query: sessions for all children of this customer
            $q->whereHas('student', fn($sq) => $sq->where('parent_id', auth()->id()))
              // ── Self-student query: customer is the student directly
              ->orWhere('student_id', auth()->id());
        });

        // ── Filter: narrow to a specific student when student_id is provided
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // ── Filter: narrow to the visible date range sent by FullCalendar
        $start = substr($request->input('start', now()->subMonth()->toDateString()), 0, 10);
        $end   = substr($request->input('end',   now()->addMonth()->toDateString()),  0, 10);
        $query->whereBetween('date', [$start, $end]);

        $events = $query->with('tutor', 'student.parent.credit', 'student.credit')->get()->map(function($s) {
            $tutorTz = $s->tutor->time_zone ?? 'UTC';
            $start = Carbon::createFromFormat('Y-m-d H:i:s', $s->date->format('Y-m-d') . ' ' . $s->start_time, $tutorTz);
            $end = $start->copy()->addMinutes($s->duration);

            // ── Credit state: computed only for future Scheduled sessions
            $isFutureScheduled = $s->status === 'Scheduled' && $s->date->gte(now()->startOfDay());
            $isAdminTutor = $s->tutor?->role === 'admin';
            $creditBalance = $s->student?->parent
                ? ($s->student->parent->credit?->credit_balance ?? 0)
                : ($s->student?->credit?->credit_balance ?? 0);
            $noCredits  = $isFutureScheduled && !$isAdminTutor && $creditBalance <= 0;
            $halfCredit = $isFutureScheduled && !$isAdminTutor && !$noCredits && $creditBalance <= 0.5;

            $colors = match(true) {
                $s->status === 'Cancelled'                   => ['bg' => '#94a3b8', 'border' => '#64748b'],
                in_array($s->status, ['Billed','Completed'])  => ['bg' => '#10b981', 'border' => '#059669'],
                $noCredits                                   => ['bg' => '#ef4444', 'border' => '#dc2626'],
                $halfCredit                                  => ['bg' => '#f59e0b', 'border' => '#d97706'],
                (bool)$s->is_initial                         => ['bg' => '#f59e0b', 'border' => '#d97706'],
                $s->series_id !== null                       => ['bg' => '#6366f1', 'border' => '#4f46e5'],
                default                                      => ['bg' => '#4f46e5', 'border' => '#4338ca'],
            };

            $recurringPrefix  = $s->series_id !== null ? '↻ ' : '';
            $halfCreditSuffix = $halfCredit ? ' · half hour credit' : '';

            return [
                'id'              => $s->id,
                'title'           => $recurringPrefix . "{$s->student->first_name} | {$s->subject}" . $halfCreditSuffix,
                'start'           => $start->toIso8601String(),
                'end'             => $end->toIso8601String(),
                'display'         => 'list-item',
                'backgroundColor' => $colors['bg'],
                'borderColor'     => $colors['border'],
                'textColor'       => '#1e293b',
                'extendedProps'   => [
                    'subject'             => $s->subject,
                    'duration'            => $s->duration,
                    'status'              => $s->status,
                    'tutorName'           => $s->tutor?->full_name ?? 'TBD',
                    'studentName'         => $s->student?->full_name ?? '',
                    'location'            => $s->location ?? '',
                    'recurringId'         => $s->series_id,
                    'insufficientCredits' => $noCredits,
                    'cancelReason'        => $s->tutor_notes,
                ],
            ];
        });

        return response()->json($events);
    }

    public function cancel(Request $request, TutoringSession $session)
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

        $reason = trim($request->input('reason', ''));
        if ($reason === '') {
            return response()->json(['success' => false, 'message' => 'A cancellation reason is required.'], 422);
        }

        $cancelSeries = request()->boolean('series');

        if ($cancelSeries && $session->series_id) {
            TutoringSession::where('series_id', $session->series_id)
                ->where('status', 'Scheduled')
                ->where('date', '>=', $session->date)
                ->update(['status' => 'Cancelled']);
        } else {
            $session->update(['status' => 'Cancelled']);
        }

        if ($session->tutor) {
            $session->tutor->notify(new SessionCancelledByClient($session, $user, $reason));
        }

        return response()->json(['success' => true]);
    }
}
