<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TutoringSession;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Data for Customer
        if ($user->role === 'customer') {
            $data = [
                'balance' => $user->credit->credit_balance ?? 0,
                'students' => $user->students()->count(),
                'next_session' => TutoringSession::whereIn('student_id', $user->students->pluck('id'))
                    ->where('date', '>=', now())
                    ->orderBy('date')->first()
            ];

            return view('dashboard.customer', $data);
        }

        // Data for Tutor
        if ($user->role === 'tutor') {
            $data = [
                'today_sessions' => TutoringSession::where('tutor_id', $user->id)
                    ->where('date', now()->toDateString())
                    ->count(),
                'next_session' => TutoringSession::where('tutor_id', $user->id)
                    ->where('date', '>=', now())
                    ->orderBy('date')->first()
            ];
            return view('dashboard.tutor', $data);
        }

        // Data for Admin
        return view('dashboard.admin');
    }

}

