<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get all Students with their Tutors
        $students = $user->assignedStudents()->get();

        return view('client.tutors.index', compact('students'));
    }
}
