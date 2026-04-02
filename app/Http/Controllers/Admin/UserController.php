<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Credit;

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

        $users = $query->orderBy('last_name')->paginate(12)->withQueryString();

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

        // Update `Admin` flag ("Tutor with Admin checkbox" in the docs)
        $user->update([
            'is_admin' => $request->has('is_admin'),
            'is_subscribed' => $request->has('is_subscribed'),
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

        // Update price of the Credit (for Parent) 
        if ($user->role === 'customer') {
            $user->credit()->update([
                'dollar_cost_per_credit' => $request->dollar_cost_per_credit
            ]);
        }

        // Add new value to the Tutor
        if ($request->filled('new_student_id') && $request->filled('new_hourly_payout')) {
            $user->assignedStudents()->syncWithoutDetaching([
                $request->new_student_id => ['hourly_payout' => $request->new_hourly_payout]
            ]);

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
}
