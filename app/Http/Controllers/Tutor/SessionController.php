<?php

namespace App\Http\Controllers\Tutor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TutoringSession;
use App\Models\User;
use App\Services\SessionService;
use App\Notifications\SessionUpdated;
use Carbon\Carbon;

class SessionController extends Controller
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * The main page of Tutor (Calendar + Next 5)
     */
    public function index(Request $request)
    {
        $tutorId = auth()->id();

        // ── Next sessions: upcoming 5 sessions for the sidebar panel
        $nextSessions = TutoringSession::where('tutor_id', $tutorId)
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'Scheduled')
            ->with('student')
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();

        // ── Calendar events: build all-time event list for FullCalendar
        $events = TutoringSession::where('tutor_id', $tutorId)
            ->with('student')
            ->get()
            ->map(function ($session) {
                $start = \Carbon\Carbon::parse($session->date->format('Y-m-d') . ' ' . $session->start_time);
                $end   = (clone $start)->addMinutes($session->duration);

                return [
                    'id' => $session->id,
                    'title' => $session->student->first_name . ' - ' . $session->subject,
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                    // 'url' => route('tutor.sessions.show', $session->id), // Commented till we have't 'show' method
                    'backgroundColor' => $session->is_initial ? '#f59e0b' : '#4f46e5',
                    'borderColor' => $session->is_initial ? '#d97706' : '#4338ca',
                ];
            });

        
        return view('tutor.sessions.index', [
            'nextSessions' => $nextSessions,
            'eventsJson' => $events->toJson()
        ]);
    }

    /**
     * New session form
     */
    public function create(Request $request)
    {
        // ── Students: filter to tutor-assigned students only
        $user = auth()->user();

        $students = $user->assignedStudents()->get();

        $selectedStudent = null;

        if ($request->has('student_id')) {
            $selectedStudent = User::where('role', 'student')->find($request->student_id);

            if ($selectedStudent) {
                $selectedStudent->load('parent.credit');
            }
        }

        return view('tutor.sessions.create', compact('students', 'selectedStudent'));
    }

    /**
     * Students list
     */
    public function students()
    {
        $students = auth()->user()->assignedStudents()->get();
        
        return view('tutor.students.index', compact('students'));
    }

    /**
     * Save the session via SessionService
     */
    public function store(Request $request)
    {
        if ($request->filled(['time_h', 'time_m', 'time_ampm'])) {
            $timeString = "{$request->time_h}:{$request->time_m} {$request->time_ampm}";
            try {
                // ── Time conversion: "01:30 PM" → "13:30:00"
                $startTime = Carbon::createFromFormat('h:i A', $timeString)->format('H:i:s');
                // ── Merge: inject start_time so it passes validation and reaches the service
                $request->merge(['start_time' => $startTime]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Invalid time format'], 422);
            }
        }

        $data = $request->validate([
            'student_id'    => 'required|integer|exists:users,id',
            'subject'       => 'required|string|max:255',
            'date'          => 'required|date',
            'start_time'    => 'required',
            'duration'      => 'required|in:30,60,90,120',
            'location'      => 'nullable|string|max:255',
            'is_initial'    => 'nullable|boolean',
            'recurs_weekly' => 'nullable|boolean',
        ]);

        // ── Validation: reject sessions scheduled in the past (tutor's timezone)
        $tutorTz = auth()->user()->time_zone ?? 'UTC';
        $sessionStart = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $data['date'] . ' ' . $data['start_time'],
            $tutorTz
        );
        if ($sessionStart->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot schedule a session in the past. Please choose a future date and time.',
            ], 422);
        }

        $data['tutor_id'] = auth()->id();
        $data['recurs_weekly'] = $request->has('recurs_weekly');

        if ($request->boolean('is_initial') && $data['recurs_weekly']) {
            return response()->json(['success' => false, 'message' => 'A session cannot be both Initial and Recurring.'], 422);
        }

        try {
            $this->sessionService->schedule($data);
            
            //if ($request->ajax()) {
                return response()->json(['success' => true]);
            //}

            //return back()->with('success', 'Scheduled!');
        } catch (\Exception $e) {
            // ── Error handling: graceful 422 for credit or time-conflict failures
            if ($request->ajax()) {
                return response()->json([
                    'success' => false, 
                    'message' => $e->getMessage()
                ], 422); // Status 422 for validation errors
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, TutoringSession $session)
    {
        if ($session->tutor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $timeString = "{$request->time_h}:{$request->time_m} {$request->time_ampm}";
        try {
            $startTime = Carbon::createFromFormat('h:i A', $timeString)->format('H:i:s');
            $request->merge(['start_time' => $startTime]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid time format.'], 422);
        }

        $data = $request->validate([
            'student_id' => 'required|integer|exists:users,id',
            'subject'    => 'required|string|max:255',
            'date'       => 'required|date',
            'start_time' => 'required',
            'duration'   => 'required|in:30,60,90,120',
            'location'   => 'nullable|string|max:255',
            'is_initial' => 'nullable|boolean',
        ]);

        $updateSeries = $request->input('update_series') === '1';

        if ($updateSeries && $session->recurring_id) {
            $futureSessions = TutoringSession::where('recurring_id', $session->recurring_id)
                ->where('date', '>=', $session->date)
                ->where('status', 'Scheduled')
                ->get();

            $daysDiff = (int) Carbon::parse($session->date->format('Y-m-d'))
                          ->diffInDays(Carbon::parse($data['date']), false);

            foreach ($futureSessions as $s) {
                $newDate = $s->date->copy()->addDays($daysDiff);
                if ($this->sessionService->hasConflict(
                    auth()->id(), $newDate->format('Y-m-d'), $data['start_time'], $data['duration'],
                    null, $session->recurring_id
                )) {
                    return response()->json([
                        'success' => false,
                        'message' => "Time conflict on {$newDate->format('M d')}: another session already scheduled at that time.",
                    ], 422);
                }
            }

            foreach ($futureSessions as $s) {
                $s->update([
                    'student_id' => $data['student_id'],
                    'subject'    => $data['subject'],
                    'date'       => $s->date->copy()->addDays($daysDiff)->format('Y-m-d'),
                    'start_time' => $data['start_time'],
                    'duration'   => $data['duration'],
                    'location'   => $data['location'] ?? null,
                ]);
            }
        } else {
            if ($this->sessionService->hasConflict(
                auth()->id(), $data['date'], $data['start_time'], $data['duration'], $session->id
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'Time Conflict: another session overlaps with that time slot.',
                ], 422);
            }

            $session->update([
                'student_id'   => $data['student_id'],
                'subject'      => $data['subject'],
                'date'         => $data['date'],
                'start_time'   => $data['start_time'],
                'duration'     => $data['duration'],
                'location'     => $data['location'] ?? null,
                'is_initial'   => $request->boolean('is_initial'),
                'recurring_id' => null,
            ]);
        }

        $session->refresh()->loadMissing('student.parent', 'tutor');

        if ($session->tutor) {
            $session->tutor->notify(new SessionUpdated($session));
        }

        if ($session->student?->parent) {
            $session->student->parent->notify(new SessionUpdated($session));
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, TutoringSession $session)
    {
        if ($session->tutor_id !== auth()->id()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
            }
            return back()->withErrors(['error' => 'Permission Denied: You can only cancel your own sessions.']);
        }

        if ($request->boolean('delete_series') && $session->recurring_id) {
            TutoringSession::where('recurring_id', $session->recurring_id)
                ->where('date', '>=', $session->date)
                ->where('status', 'Scheduled')
                ->delete();
        } else {
            $session->delete();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('tutor.calendar.index')->with('success', 'Session cancelled.');
    }

}
