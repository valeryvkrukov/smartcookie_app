<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = $request->user()->assignedStudents()
            ->with(['parent.credit'])
            ->get();

        return view('tutor.students.index', compact('students'));
    }
}
