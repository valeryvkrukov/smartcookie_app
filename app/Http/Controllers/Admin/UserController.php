<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditPurchase;
use App\Notifications\ClientRateSet;
use App\Notifications\StudentAssigned;
use App\Notifications\CreditBalanceChanged;
use App\Notifications\CreditsPurchased;
use App\Notifications\ManualPaymentConfirmed;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('last_name')->paginate(config('app.pagination_num', 12))->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $tutors = User::where('role', 'tutor')->orderBy('last_name')->get();
        $parents = User::where('role', 'customer')->orderBy('last_name')->get();

        return view('admin.users.edit', compact('user', 'tutors', 'parents'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email,' . $user->id,
            'role'            => 'required|in:admin,tutor,customer,student',
            'parent_id'       => 'nullable|exists:users,id',
            'tutor_id'        => 'nullable|exists:users,id',
            'can_tutor'       => 'sometimes|boolean',
            'hourly_payout'   => 'sometimes|array',
            'hourly_payout.*' => 'nullable|numeric|min:0',
        ]);
        
        $user->update($data);

        // ── Flags: update admin, subscription, and self-student flags
        $user->update([
            'is_admin'        => $request->has('is_admin'),
            'is_subscribed'   => $request->has('is_subscribed'),
            'is_self_student' => $request->boolean('is_self_student'),
        ]);

        if ($request->filled('hourly_payout') && 
            ($user->role === 'tutor' || $user->role === 'admin' && $request->input('can_tutor'))
        ) {
            foreach ($request->input('hourly_payout') as $studentId => $payout) {
                DB::table('tutor_student_assignments')
                    ->where('tutor_id', $user->id)
                    ->where('student_id', $studentId)
                    ->update(['hourly_payout' => $payout]);
            }
        }

        // ── Credit rate: update dollar cost per credit for a customer account
        if ($user->role === 'customer') {
            $oldRate = $user->credit?->dollar_cost_per_credit;
            $newRate = $request->input('dollar_cost_per_credit');

            $user->credit()->update([
                'dollar_cost_per_credit' => $newRate,
            ]);

            // ── Notification: alert client when rate is first set or changed
            if ($newRate && (string) $newRate !== (string) $oldRate) {
                $user->notify(new ClientRateSet((float) $newRate));
            }
        }

        // ── Assignment: attach a new student to the tutor
        if ($request->filled('new_student_id') && $request->filled('new_hourly_payout')) {
            $payout = (float) $request->new_hourly_payout;

            $user->assignedStudents()->syncWithoutDetaching([
                $request->new_student_id => ['hourly_payout' => $payout]
            ]);

            // ── Notification: inform tutor of new student assignment
            $student = User::find($request->new_student_id);
            if ($student) {
                $user->notify(new StudentAssigned($student, $payout));
            }

            return redirect()->back()->with('success', 'Student assigned successfully!');
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated!');
    }

    public function destroy(User $user)
    {
        $name = $user->full_name;
        
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "User [{$name}] has been successfully deleted.");
    }

    public function applyPayment(Request $request, User $user)
    {
        abort_if($user->role !== 'customer', 403);

        $data = $request->validate([
            'credits'        => ['required', 'numeric', 'min:0.5', 'max:100'],
            'total_paid'     => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:venmo,zelle,cash,other'],
            'note'           => ['nullable', 'string', 'max:255'],
        ]);

        $credits   = (float) $data['credits'];
        $totalPaid = (float) $data['total_paid'];
        $method    = ucfirst($data['payment_method']);
        $note      = trim($data['note'] ?? '');
        $admin     = auth()->user();

        // ── Apply credits to balance
        $user->credit()->firstOrCreate(
            ['user_id' => $user->id],
            ['credit_balance' => 0, 'dollar_cost_per_credit' => null]
        );
        $user->credit->increment('credit_balance', $credits);
        $user->credit->refresh();

        // ── Record purchase for financial reports
        CreditPurchase::create([
            'user_id'           => $user->id,
            'amount'            => $credits,
            'credits_purchased' => $credits,
            'total_paid'        => $totalPaid,
            'stripe_session_id' => null,
            'type'              => 'deposit',
        ]);

        // ── Notify customer of balance change
        $reason = "Manual payment confirmed via {$method}"
            . ($note ? " – {$note}" : '')
            . " (confirmed by {$admin->full_name})";

        $user->notify(new CreditBalanceChanged(
            amount: $credits,
            direction: 'credit',
            balanceAfter: $user->credit->credit_balance,
            reason: $reason,
        ));

        // ── Notify tutors assigned to this customer's students
        $tutors = User::whereHas('assignedStudents', function ($q) use ($user) {
            $q->where('parent_id', $user->id);
        })->get();
        if ($tutors->isNotEmpty()) {
            Notification::send($tutors, new CreditsPurchased($user, $credits));
        }

        // ── System log: notify all admins so the payment appears in system logs
        $admins = User::where('is_admin', true)->get();
        Notification::send($admins, new ManualPaymentConfirmed(
            client: $user,
            creditsPurchased: $credits,
            totalPaid: $totalPaid,
            paymentMethod: $method,
            note: $note,
            confirmedByName: $admin->full_name,
        ));

        return redirect()->route('admin.users.edit', $user->id)
            ->with('success', "Applied {$credits} credit(s) to {$user->full_name}'s account.");
    }
}
