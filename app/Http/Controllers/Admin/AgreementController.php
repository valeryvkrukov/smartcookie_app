<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function index()
    {
        $requests = \App\Models\AgreementRequest::with('user', 'agreement')
            ->orderBy('status', 'desc') // Show awaiting first, then signed
            ->get();

        return view('admin.agreements.index', compact('requests'));
    }
    
    public function assign(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'agreement_id' => 'required|exists:agreements,id',
        ]);

        // Check if there's already a request for this user and agreement
        AgreementRequest::firstOrCreate([
            'user_id' => $data['user_id'],
            'agreement_id' => $data['agreement_id'],
        ], [
            'status' => 'Awaiting signature'
        ]);

        return response()->json(['success' => true]);
    }
}
