<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TutoringSession;
use App\Models\User;
use App\Notifications\SessionUpdated;
use App\Services\SessionService;
use Carbon\Carbon;

class SessionController extends Controller
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function index()
    {}
    
    public function create(Request $request)
    {
        $tutors = User::where('role', 'tutor')->orderBy('last_name')->get();
        $students = User::where('role', 'student')->orderBy('last_name')->get();

        $selectedDate = $request->input('date');

        return view('admin.sessions.create', compact('tutors', 'students', 'selectedDate'));
    }

    public function edit(TutoringSession $session)
    {
        $tutors = User::where('role', 'tutor')->orderBy('last_name')->get();
        $students = User::where('role', 'student')->orderBy('last_name')->get();
        
        return view('admin.sessions.edit', compact('session', 'tutors', 'students'));
    }

    public function update(Request $request, TutoringSession $session)
    {
        $session->loadMissing('student.parent');

        $timeString = "{$request->time_h}:{$request->time_m} {$request->time_ampm}";
        $startTime = Carbon::parse($timeString)->format('H:i:s');
        $request->merge(['start_time' => $startTime]);
        
        $data = $request->validate([
            'tutor_id'   => 'required|exists:users,id',
            'student_id' => 'required|exists:users,id',
            'subject'    => 'required|string',
            'date'       => 'required|date',
            'time_h'     => 'required',
            'time_m'     => 'required',
            'time_ampm'  => 'required',
            'duration'   => 'required|in:0:30,1:00,1:30,2:00',
            'location'   => 'required|string',
        ]);

        $timeString = $request->time_h . ':' . $request->time_m . ' ' . $request->time_ampm;
        $startTime = Carbon::parse($timeString)->format('H:i:s');

        $session->update([
            'tutor_id'   => $request->tutor_id,
            'student_id' => $request->student_id,
            'subject'    => $request->subject,
            'date'       => $request->date,
            'start_time' => $startTime,
            'duration'   => $request->duration,
        ]);

        $session->refresh()->loadMissing('student.parent', 'tutor');

        if ($session->tutor) {
            $session->tutor->notify(new SessionUpdated($session));
        }

        if ($session->student?->parent) {
            $session->student->parent->notify(new SessionUpdated($session));
        }

        return response()->json([
            'success' => true,
            'message' => 'Session updated successfully'
        ]);

        //return redirect()->route('admin.calendar.index')->with('success', 'Session updated!');
    }

    public function store(Request $request)
    {
        $timeString = "{$request->time_h}:{$request->time_m} {$request->time_ampm}";
        $startTime = Carbon::parse($timeString)->format('H:i:s');
        $request->merge(['start_time' => $startTime]);
        
        $data = $request->validate([
            'tutor_id'   => 'required|exists:users,id',
            'student_id' => 'required|exists:users,id',
            'subject'    => 'required|string|max:255',
            'date'       => 'required|date',
            'start_time' => 'required',
            'duration'   => 'required|in:0:30,1:00,1:30,2:00',
            'location'   => 'required|string',
        ]);

        try {
            $this->sessionService->schedule($data);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // Catch exceptions from SessionService (for ex., "Time Conflict")
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 422);
        }

        return redirect()->route('admin.calendar.index')->with('success', 'Session added to calendar');
    }

    public function destroy(Request $request, TutoringSession $session)
    {
        if ($request->has('delete_series') && $session->recurring_id) {
            TutoringSession::where('recurring_id', $session->recurring_id)
                ->where('date', '>=', $session->date)
                ->where('status', 'Scheduled') // Don't touch `Billed` 
                ->delete();
            
            $message = "The entire session series has been cancelled.";
        } else {
            $message = "The specific session has been cancelled.";

            $session->delete();
        }
        
        return redirect()->route('admin.calendar.index')->with('success', $message);
    }
}
