<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgreementRequest;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'Signed');
        $search = $request->input('search');

        $query = AgreementRequest::with('user', 'agreement');

        if ($status === 'Pending') {
            $query->where('status', 'Awaiting signature');
        } else {
            $query->where('status', 'Signed');
        }

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('agreement', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                });
            });
        }

        $requests = $query->orderByRaw("FIELD(status, 'Awaiting signature', 'Signed')")->get();

        return view('admin.agreements.index', compact('requests'));
    }
    
    public function assign(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'agreement_id' => 'required|exists:agreements,id',
        ]);

        // ── Duplicate guard: skip if this user-agreement pair already exists
        AgreementRequest::firstOrCreate([
            'user_id' => $data['user_id'],
            'agreement_id' => $data['agreement_id'],
        ], [
            'status' => 'Awaiting signature'
        ]);

        return response()->json(['success' => true]);
    }
}
