<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentProfile;

class StudentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Include inactive — they remain visible to the owning customer with a badge
        $linked = Student::withInactive()
            ->where('parent_id', $user->id)
            ->with(['subjectRates', 'assignedTutors'])
            ->get();

        if ($user->is_self_student) {
            $user->loadMissing(['subjectRates', 'assignedTutors']);
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
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'student_grade' => 'nullable|string|max:100',
            'blurb'         => 'nullable|string|max:1000',
            'address'       => 'required|string|max:500',
            'phone'         => 'required|string|max:50',
            'student_email' => 'nullable|email|unique:users,email',
        ]);

        $newStudent = User::create([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'address'       => $data['address'],
            'phone'         => $data['phone'],
            'role'          => 'student',
            'parent_id'     => auth()->id(),
            'password'      => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
            'email'         => !empty($data['student_email'])
                ? $data['student_email']
                : strtolower($data['first_name'] . '.' . $data['last_name'] . '.' . uniqid() . '@smartcookie.local'),
        ]);

        StudentProfile::create([
            'user_id'       => $newStudent->id,
            'student_grade' => $data['student_grade'] ?? null,
            'blurb'         => $data['blurb'] ?? null,
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
            'address'       => 'required|string|max:500',
            'phone'         => 'required|string|max:50',
            'student_email' => 'nullable|email|unique:users,email,' . $id,
        ]);

        $updateData = [
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'address'       => $data['address'],
            'phone'         => $data['phone'],
        ];

        $profileData = [
            'student_grade' => $data['student_grade'],
            'blurb'         => $data['blurb'],
        ];

        // ── Self-student: editing own profile (email managed via /profile page)
        if ($user->is_self_student && (int) $id === $user->id) {
            $user->update($updateData);
            $user->studentProfile()->updateOrCreate(['user_id' => $user->id], $profileData);
            return response()->json(['success' => true]);
        }

        $student = User::where('id', $id)
            ->where('role', 'student')
            ->where('parent_id', $user->id)
            ->firstOrFail();

        // ── Email: update only when a non-empty value is provided
        if (!empty($data['student_email'])) {
            $updateData['email'] = $data['student_email'];
        }

        $student->update($updateData);
        $student->studentProfile()->updateOrCreate(['user_id' => $student->id], $profileData);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if ($user->id == $id) {
            return redirect()->route('customer.students.index')->with('error', 'You cannot remove your own account.');
        }

        // Allow deleting inactive students too
        $student = Student::withInactive()
            ->where('id', $id)
            ->where('parent_id', $user->id)
            ->firstOrFail();

        $student->delete();

        return redirect()->route('customer.students.index')->with('success', 'Student removed.');
    }

    /**
     * Toggle between "customer" (manage children) and "self student" modes.
     * Switching TO self-student marks all linked child students as inactive.
     * Switching BACK reactivates them.
     */
    public function toggleSelfStudent()
    {
        $user = auth()->user();
        $becomingSelf = ! $user->is_self_student;

        if ($becomingSelf) {
            // Deactivate all linked students
            User::where('role', 'student')
                ->where('parent_id', $user->id)
                ->update(['is_inactive' => true]);
        } else {
            // Reactivate all linked students
            User::where('role', 'student')
                ->where('parent_id', $user->id)
                ->update(['is_inactive' => false]);
        }

        $user->update(['is_self_student' => $becomingSelf]);

        return redirect()->route('customer.students.index')
            ->with('success', $becomingSelf
                ? 'You are now registered as a self-student. Your children\'s profiles have been deactivated.'
                : 'Switched back to parent mode. Your children\'s profiles are active again.'
            );
    }
}
