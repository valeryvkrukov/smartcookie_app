<?php

namespace App\Http\Controllers\Tutor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TutoringSession;
use App\Models\User;
use App\Services\SessionService;
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

        // Block "Next 5 tutoring sessions"
        $nextSessions = TutoringSession::where('tutor_id', $tutorId)
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'Scheduled')
            ->with('student')
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();

        // Build JSON for FullCalendar JS
        $events = TutoringSession::where('tutor_id', $tutorId)
            ->with('student')
            ->get()
            ->map(function ($session) {
                $start = \Carbon\Carbon::parse($session->date->format('Y-m-d') . ' ' . $session->start_time);
                $parts = explode(':', $session->duration);
                $hours = (int) ($parts[0] ?? 0);
                $minutes = (int) ($parts[1] ?? 0);

                $end = (clone $start)->addHours($hours)->addMinutes($minutes);

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
        // Tutor can select only from students assigned to him
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
                // Convert "01:30 PM" to "13:30:00"
                $startTime = Carbon::createFromFormat('h:i A', $timeString)->format('H:i:s');
                // Merge the start_time into the request so it can be validated and passed to the service
                $request->merge(['start_time' => $startTime]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Invalid time format'], 422);
            }
        }

        $data = $request->validate([
            'recurs_weekly' => 'boolean',
            'student_id'    => 'required|integer|exists:users,id',
            'subject'       => 'required|string|max:255',
            'date'          => 'required|date',
            'start_time'    => 'required', // Take H:M AM/PM on the frontend
            'duration'      => 'required|in:0:30,1:00,1:30,2:00',
            'location'      => 'required|string|max:255',
            'is_initial'    => 'boolean',
            'recurs_weekly' => 'nullable|boolean',
        ]);

        $data['tutor_id'] = auth()->id();
        $data['recurs_weekly'] = $request->has('recurs_weekly');

        try {
            $this->sessionService->schedule($data);
            
            //if ($request->ajax()) {
                return response()->json(['success' => true]);
            //}

            //return back()->with('success', 'Scheduled!');
        } catch (\Exception $e) {
            // If service throws an error (0 credits or conflicts) - receive a graceful error to this effect
            if ($request->ajax()) {
                return response()->json([
                    'success' => false, 
                    'message' => $e->getMessage()
                ], 422); // Status 422 for validation errors
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request, TutoringSession $session)
    {
        // Check if the session belongs to the tutor
        if ($session->tutor_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Permission Denied: You can only cancel your own sessions.']);
        }

        if ($request->has('delete_series') && $session->recurring_id) {
            TutoringSession::where('recurring_id', $session->recurring_id)
                ->where('date', '>=', $session->date)
                ->where('status', 'Scheduled') // Don't touch `Billed` 
                ->delete();
                
            $message = "The session series has been cancelled successfully.";
        } else {
            $session->delete();
            
            $message = "The session has been cancelled.";
        }

        $session->delete();

        return redirect()->route('tutor.calendar.index')->with('success', $message);
    }

}
