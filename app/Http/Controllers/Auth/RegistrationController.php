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

        $request->validate([
            // Parent fields
            'parent_name'   => 'required|string|max:255',
            'parent_email'  => 'required|email|unique:users,email',
            'password'      => 'required|min:8', // 'confirmed' is removed for now
            'address'       => 'required|string',
            'phone'         => 'required|string',
            // Student fields
            'student_name'  => 'required|string|max:255',
            'student_grade' => 'required|string',
            'student_school'=> 'required|string',
            'student_email' => 'nullable|email',
            'tutoring_goals'=> 'nullable|string',
        ]);

        /*$recaptchaToken = $request->input('g-recaptcha-response');
        $response = Http::asForm()->post('https://www.google.com', [
            'secret'   => config('services.recaptcha.secret'),
            'response' => $recaptchaToken,
            'remoteip' => $request->ip(),
        ]);
        $result = $response->json();

        if (!$result['success'] || ($result['score'] ?? 0) < 0.5) {
            return back()->withErrors(['captcha' => 'Security error. Try again please.'])->withInput();
        }*/

        return DB::transaction(function () use ($request) {
            [$parentFirstName, $parentLastName] = User::splitName($request->parent_name);
            [$studentFirstName, $studentLastName] = User::splitName($request->student_name);

            // Create Parent (Client)
            $parent = User::create([
                'first_name' => $parentFirstName,
                'last_name'  => $parentLastName,
                'email'      => $request->parent_email,
                'password'   => Hash::make($request->password),
                'address'    => $request->address,
                'phone'      => $request->phone,
                'role'       => 'customer',
            ]);

            // Wallet initialization
            // TODO: fix dollar_cost_per_credit = null, for 'grayed out' partition
            Credit::create(['user_id' => $parent->id, 'credit_balance' => 0]);

            // Create first Student
            User::create([
                'first_name'     => $studentFirstName,
                'last_name'      => $studentLastName,
                'email'          => $request->student_email ?? 'student_'.uniqid().'@tutor.com',
                'password'       => Hash::make(Str::random(16)), // Студенту пароль не нужен для входа по ТЗ
                'parent_id'      => $parent->id,
                'student_grade'  => $request->student_grade,
                'student_school' => $request->student_school,
                'tutoring_goals' => $request->tutoring_goals,
                'role'           => 'student',
            ]);

            $admins = User::where('is_admin', true)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewClientRegistered($parent, $request->only([
                    'student_name', 'student_grade', 'student_school', 'tutoring_goals'
                ])));
            }

            $parent->notify(new WelcomeCustomerRegistered($parent, $request->student_name));

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
