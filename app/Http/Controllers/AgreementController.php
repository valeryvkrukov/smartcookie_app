<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementRequest;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $query = AgreementRequest::with(['user', 'agreement']);

            if ($search = $request->input('search')) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('agreement', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $requests = $query->latest('signed_at')->paginate(config('app.pagination_num', 12))->withQueryString();

            return view('admin.agreements.index', compact('requests'));
        }
        
        $agreements = Agreement::where('is_active', true)->get();
        
        // All agreements signed by current user
        $signedRequests = AgreementRequest::where('user_id', $user->id)
            ->where('status', 'Signed')
            ->pluck('agreement_id')
            ->toArray();

        return view('agreements.index', compact('agreements', 'signedRequests'));
    }

    public function show(Request $request, Agreement $agreement)
    {
        // Check for signed/unsigned
        $isSigned = AgreementRequest::where('user_id', auth()->id())
            ->where('agreement_id', $agreement->id)
            ->where('status', 'Signed')
            ->exists();

        return view('agreements.show', compact('request', 'agreement', 'isSigned'));
    }

    public function sign(Request $request, Agreement $agreement)
    {
        AgreementRequest::updateOrCreate(
            [
                'user_id' => auth()->id(), 
                'agreement_id' => $agreement->id
            ],
            [
                'status' => 'Signed', 
                'signed_at' => now(),
                'ip_address' => $request->ip() // optional
            ]
        );

        return redirect()->route('agreements.index')->with('success', 'Contract signed successfully!');
    }
}
