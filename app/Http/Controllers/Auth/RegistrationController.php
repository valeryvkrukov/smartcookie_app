<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Credit;
use App\Notifications\NewClientRegistered;
use App\Notifications\WelcomeCustomerRegistered;

class RegistrationController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        if ($request->filled('name') && ! $request->filled('parent_name')) {
            return $this->storeDefaultRegistration($request);
        }

        $isSelfStudent = $request->boolean('is_self_student');

        $request->validate([
            // Parent / client fields
            'parent_name'    => 'required|string|max:255',
            'parent_email'   => 'required|email|unique:users,email',
            'password'       => 'required|min:8',
            'address'        => 'required|string',
            'phone'          => 'required|string',
            // Student fields — required only when NOT self-student
            'student_name'   => $isSelfStudent ? 'nullable|string|max:255' : 'required|string|max:255',
            'student_grade'  => $isSelfStudent ? 'nullable|string' : 'required|string',
            'student_school' => $isSelfStudent ? 'nullable|string' : 'required|string',
            'student_email'  => 'nullable|email',
            'tutoring_goals' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $isSelfStudent) {
            [$parentFirstName, $parentLastName] = User::splitName($request->parent_name);

            // Create Parent / Client account
            $parent = User::create([
                'first_name'      => $parentFirstName,
                'last_name'       => $parentLastName,
                'email'           => $request->parent_email,
                'password'        => Hash::make($request->password),
                'address'         => $request->address,
                'phone'           => $request->phone,
                'role'            => 'customer',
                'is_self_student' => $isSelfStudent,
            ]);

            // Wallet initialization
            Credit::create(['user_id' => $parent->id, 'credit_balance' => 0]);

            $studentName = null;

            if (! $isSelfStudent) {
                [$studentFirstName, $studentLastName] = User::splitName($request->student_name);

                User::create([
                    'first_name'     => $studentFirstName,
                    'last_name'      => $studentLastName,
                    'email'          => $request->student_email ?? 'student_' . uniqid() . '@tutor.com',
                    'password'       => Hash::make(Str::random(16)),
                    'parent_id'      => $parent->id,
                    'student_grade'  => $request->student_grade,
                    'student_school' => $request->student_school,
                    'tutoring_goals' => $request->tutoring_goals,
                    'role'           => 'student',
                ]);

                $studentName = $request->student_name;
            }

            $admins = User::where('is_admin', true)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewClientRegistered($parent, $request->only([
                    'student_name', 'student_grade', 'student_school', 'tutoring_goals'
                ])));
            }

            $parent->notify(new WelcomeCustomerRegistered($parent, $studentName));

            return redirect()->route('login')->with('success', 'Account created! Please log in.');
        });
    }

    private function storeDefaultRegistration(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        [$firstName, $lastName] = User::splitName($validated['name']);

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
