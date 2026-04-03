<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tutor;

class StudentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->is_self_student) {
            $students = collect([$user->loadMissing(['subjectRates', 'tutor'])]);
        } else {
            $students = \App\Models\User::where('role', 'student')
                ->where('parent_id', $user->id)
                ->with(['subjectRates', 'tutor'])
                ->get();
        }

        return view('customer.students.index', compact('students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'student_grade' => 'nullable|string|max:100',
            'blurb' => 'nullable|string|max:1000',
        ]);

        $student = \App\Models\User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'student_grade' => $data['student_grade'],
            'blurb' => $data['blurb'], // Optional field
            'role' => 'student',
            'parent_id' => auth()->id(), // Binding to parent
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)), // Random password, since students don't log in directly
            'email' => strtolower($data['first_name'] . '.' . $data['last_name'] . '.' . uniqid() . '@smartcookie.local'), // Temporary email
        ]);

        return response()->json(['success' => true]);
    }
}
