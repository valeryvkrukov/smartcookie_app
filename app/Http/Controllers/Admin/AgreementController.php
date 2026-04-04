<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AgreementRequest;
use App\Models\User;
use App\Notifications\NewAgreementAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        $requests  = $query->orderByRaw("FIELD(status, 'Awaiting signature', 'Signed')")->get();
        $documents = Agreement::orderBy('name')->get();

        return view('admin.agreements.index', compact('requests', 'documents'));
    }

    // ── Upload: create a new agreement document from a PDF file
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'pdf'  => 'required|file|mimes:pdf|max:20480',
        ]);

        $path = $request->file('pdf')->store('agreements', 'public');

        Agreement::create([
            'name'     => $data['name'],
            'pdf_path' => $path,
        ]);

        return back()->with('success', 'Agreement "' . $data['name'] . '" uploaded successfully.');
    }

    // ── Replace: swap the PDF file for an existing agreement record
    public function replace(Request $request, Agreement $agreement)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:20480',
        ]);

        // ── Delete old file before storing the new one
        Storage::disk('public')->delete($agreement->pdf_path);

        $agreement->update([
            'pdf_path' => $request->file('pdf')->store('agreements', 'public'),
        ]);

        return back()->with('success', 'PDF for "' . $agreement->name . '" replaced successfully.');
    }

    // ── Destroy: delete agreement and its PDF; block if signed requests exist
    public function destroy(Agreement $agreement)
    {
        if ($agreement->agreementRequests()->where('status', 'Signed')->exists()) {
            return back()->with('error', 'Cannot delete "' . $agreement->name . '" — it has signed records.');
        }

        Storage::disk('public')->delete($agreement->pdf_path);
        $agreement->delete();

        return back()->with('success', 'Agreement "' . $agreement->name . '" deleted.');
    }
    
    public function assign(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'agreement_id' => 'required|exists:agreements,id',
        ]);

        // ── Duplicate guard: skip if this user-agreement pair already exists
        $created = AgreementRequest::firstOrCreate([
            'user_id'      => $data['user_id'],
            'agreement_id' => $data['agreement_id'],
        ], [
            'status' => 'Awaiting signature',
        ]);

        // ── Notify: email the client only when a new request was created
        if ($created->wasRecentlyCreated) {
            $user      = User::findOrFail($data['user_id']);
            $agreement = Agreement::findOrFail($data['agreement_id']);
            $user->notify(new NewAgreementAssigned($agreement));
        }

        return response()->json(['success' => true]);
    }
}
