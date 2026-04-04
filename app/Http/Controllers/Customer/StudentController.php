<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class StudentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $linked = User::where('role', 'student')
            ->where('parent_id', $user->id)
            ->with(['subjectRates', 'tutor'])
            ->get();

        if ($user->is_self_student) {
            $user->loadMissing(['subjectRates', 'tutor']);
            $students = collect([$user])->concat($linked);
        } else {
            $students = $linked;
        }

        $selfStudentId = $user->is_self_student ? $user->id : null;

        return view('customer.students.index', compact('students', 'selfStudentId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'student_grade' => 'nullable|string|max:100',
            'blurb' => 'nullable|string|max:1000',
        ]);

        User::create([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'student_grade' => $data['student_grade'],
            'blurb'         => $data['blurb'],
            'role'          => 'student',
            'parent_id'     => auth()->id(),
            'password'      => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
            'email'         => strtolower($data['first_name'] . '.' . $data['last_name'] . '.' . uniqid() . '@smartcookie.local'),
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();

        $data = $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'student_grade' => 'nullable|string|max:100',
            'blurb'         => 'nullable|string|max:1000',
        ]);

        // Self-student editing their own profile
        if ($user->is_self_student && (int) $id === $user->id) {
            $user->update($data);
            return redirect()->route('customer.students.index')->with('success', 'Profile updated.');
        }

        $student = User::where('id', $id)
            ->where('role', 'student')
            ->where('parent_id', $user->id)
            ->firstOrFail();

        $student->update($data);

        return redirect()->route('customer.students.index')->with('success', 'Student updated.');
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if ($user->id == $id) {
            return redirect()->route('customer.students.index')->with('error', 'You cannot remove your own account.');
        }

        $student = User::where('id', $id)
            ->where('role', 'student')
            ->where('parent_id', $user->id)
            ->firstOrFail();

        $student->delete();

        return redirect()->route('customer.students.index')->with('success', 'Student removed.');
    }
}
