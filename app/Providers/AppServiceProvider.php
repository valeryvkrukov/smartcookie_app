<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Student;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['admin.sessions.partials.quick-form', 'admin.users.edit'], function ($view) {
            $view->with([
                'tutors'   => User::isTutor()->orderBy('last_name')->get(),
                // Active students (role=student, not inactive) + self-students (customers acting as their own student)
                'students' => User::where(function($q) {
                        $q->where('role', 'student')->where('is_inactive', false);
                    })
                    ->orWhere(fn($q) => $q->where('role', 'customer')->where('is_self_student', true))
                    ->orderBy('last_name')->get(),
            ]);
        });

        View::composer('tutor.sessions.partials.quick-form', function ($view) {
            if (Auth::check() && Auth::user()->role === 'tutor') {
                // Exclude inactive students from session creation/edit form
                $view->with('students', Auth::user()->assignedStudents()->where('is_inactive', false)->get());
            } else {
                $view->with('students', collect());
            }
        });
    }
}
