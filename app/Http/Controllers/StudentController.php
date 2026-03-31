<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Client;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $client = Client::find($request->user()->id);

        if (!$client) {
            return view('students.index', ['students' => collect()]);
        }
        
        $students = $client->students;

        return view('students.index', compact('students'));
    }

    public function create()
    {
        return view('students.form', ['student' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'       => 'required|string|max:255',
            'student_grade'    => 'required|string',
            'student_school'   => 'required|string',
            'tutoring_subject' => 'required|string',
            'student_email'    => 'nullable|email',
            'tutoring_goals'   => 'nullable|string',
        ]);

        $request->user()->students()->create([
            'first_name'       => $data['first_name'],
            'email'            => $data['student_email'] ?? 'student_'.Str::random(8).'@tutor.com',
            'password'         => Hash::make(Str::random(16)),
            'role'             => 'student',
            'student_grade'    => $data['student_grade'],
            'student_school'   => $data['student_school'],
            'tutoring_subject' => $data['tutoring_subject'],
            'tutoring_goals'   => $data['tutoring_goals'],
        ]);

        return redirect()->route('students.index')->with('status', 'student-added');
    }

    public function edit($id)
    {
        $student = auth()->user()->students()->findOrFail($id);

        return view('students.form', compact('student'));
    }

    public function update(Request $request, $id)
    {
        $student = auth()->user()->students()->findOrFail($id);

        $data = $request->validate([
            'first_name'       => 'required|string|max:255',
            'student_grade'    => 'required|string',
            'student_school'   => 'required|string',
            'tutoring_subject' => 'required|string',
            'student_email'    => 'nullable|email',
            'tutoring_goals'   => 'nullable|string',
        ]);

        $student->update($data);

        return redirect()->route('students.index')->with('status', 'student-updated');
    }

    public function destroy($id)
    {
        $student = auth()->user()->students()->findOrFail($id);

        $student->delete();

        return redirect()->route('students.index')->with('status', 'student-removed');
    }
}
