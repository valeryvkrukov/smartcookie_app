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
        $tutors = User::where('can_tutor', true)->orderBy('last_name')->get();
        $students = User::where('role', 'student')
            ->orWhere(fn($q) => $q->where('role', 'customer')->where('is_self_student', true))
            ->orderBy('last_name')->get();

        $selectedDate = $request->input('date');

        return view('admin.sessions.create', compact('tutors', 'students', 'selectedDate'));
    }

    public function edit(TutoringSession $session)
    {
        $tutors = User::where('can_tutor', true)->orderBy('last_name')->get();
        $students = User::where('role', 'student')
            ->orWhere(fn($q) => $q->where('role', 'customer')->where('is_self_student', true))
            ->orderBy('last_name')->get();
        
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
            'location'   => 'nullable|string',
            'status'     => 'nullable|in:Scheduled,Completed,Billed,Cancelled',
        ]);

        $timeString = $request->time_h . ':' . $request->time_m . ' ' . $request->time_ampm;
        $startTime = Carbon::parse($timeString)->format('H:i:s');

        if ($request->boolean('is_initial') && $request->boolean('recurs_weekly')) {
            return response()->json(['success' => false, 'message' => 'A session cannot be both Initial and Recurring.'], 422);
        }

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
                    $data['tutor_id'], $newDate->format('Y-m-d'), $startTime, $data['duration'],
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
                    'tutor_id'   => $data['tutor_id'],
                    'student_id' => $data['student_id'],
                    'subject'    => $data['subject'],
                    'date'       => $s->date->copy()->addDays($daysDiff)->format('Y-m-d'),
                    'start_time' => $startTime,
                    'duration'   => $data['duration'],
                    'location'   => $data['location'] ?? null,
                ]);
            }
        } else {
            $session->update([
                'tutor_id'     => $request->tutor_id,
                'student_id'   => $request->student_id,
                'subject'      => $request->subject,
                'date'         => $request->date,
                'start_time'   => $startTime,
                'duration'     => $request->duration,
                'location'     => $request->location ?: null,
                'is_initial'   => $request->boolean('is_initial'),
                'recurs_weekly'=> $request->boolean('recurs_weekly'),
                'recurring_id' => null,
                ...($request->filled('status') ? ['status' => $request->status] : []),
            ]);
        }

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
    }

    public function store(Request $request)
    {
        $timeString = "{$request->time_h}:{$request->time_m} {$request->time_ampm}";
        $startTime = Carbon::parse($timeString)->format('H:i:s');
        $request->merge(['start_time' => $startTime]);
        
        $data = $request->validate([
            'tutor_id'      => 'required|exists:users,id',
            'student_id'    => 'required|exists:users,id',
            'subject'       => 'required|string|max:255',
            'date'          => 'required|date',
            'start_time'    => 'required',
            'duration'      => 'required|in:0:30,1:00,1:30,2:00',
            'location'      => 'nullable|string',
            'is_initial'    => 'nullable|boolean',
            'recurs_weekly' => 'nullable|boolean',
        ]);

        $data['is_initial']    = $request->boolean('is_initial');
        $data['recurs_weekly'] = $request->boolean('recurs_weekly');

        if ($data['is_initial'] && $data['recurs_weekly']) {
            return response()->json(['success' => false, 'message' => 'A session cannot be both Initial and Recurring.'], 422);
        }

        try {
            $this->sessionService->schedule($data);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // ── Error handling: catch SessionService exceptions (time conflicts, etc.)
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 422);
        }

        return redirect()->route('admin.calendar.index')->with('success', 'Session added to calendar');
    }

    public function destroy(Request $request, TutoringSession $session)
    {
        if ($request->boolean('delete_series') && $session->recurring_id) {
            TutoringSession::where('recurring_id', $session->recurring_id)
                ->where('date', '>=', $session->date)
                ->whereNotIn('status', ['Billed', 'Completed'])
                ->delete();
        } else {
            $session->delete();
        }

        return response()->json(['success' => true]);
    }
}
