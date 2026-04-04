<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // ── Query: load students with their assigned tutors
        $students = $user->assignedStudents()->get();

        return view('client.tutors.index', compact('students'));
    }
}
