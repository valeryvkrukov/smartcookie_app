<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AgreementRequest;


class AgreementController extends Controller
{
    public function index()
    {
        $requests = AgreementRequest::where('user_id', auth()->id())
            ->with('agreement')
            ->orderBy('status', 'desc') // Needed to sign first, then show awaiting, then signed
            ->get();

        return view('customer.agreements.index', compact('requests'));
    }

    public function sign(Request $request)
    {
        $request->validate([
            'request_id'         => 'required|exists:agreement_requests,id',
            'agree_terms'        => 'required|accepted',
            'signed_full_name'   => 'required|string|max:255',
            'signed_date_manual' => 'required|date',
        ]);

        $agreementRequest = AgreementRequest::with('agreement')
            ->where('user_id', auth()->id())
            ->findOrFail($request->request_id);

        $agreementRequest->update([
            'status'             => 'Signed',
            'signed_full_name'   => $request->signed_full_name,
            'signed_date_manual' => $request->signed_date_manual,
            'signed_at'          => now(),
            // ── Snapshot: record PDF filename at signing so the audit trail is self-contained
            'pdf_filename'       => basename($agreementRequest->agreement->pdf_path),
        ]);

        return response()->json(['success' => true]);
    }

}
