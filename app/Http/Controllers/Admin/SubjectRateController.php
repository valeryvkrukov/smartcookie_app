<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubjectRate;

class SubjectRateController extends Controller
{
    public function store(Request $request)
    {
        /*$data = $request->validate([
            'student_id' => 'required|exists:users,id',
            'subject'    => 'required|string|max:255',
            'rate'       => 'required|numeric|min:0',
        ]);*/

        try {
            SubjectRate::create($request->only('student_id', 'subject', 'rate'));
        } catch (\Exception $e) {
            //return response()->json(['success' => false, 'message' => 'Error adding subject rate'], 500);
            dd($e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    public function destroy(SubjectRate $rate)
    {
        $rate->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Rate removed');
    }
}
