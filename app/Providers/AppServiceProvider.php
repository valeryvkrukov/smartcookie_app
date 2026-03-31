<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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
        View::composer('admin.sessions.partials.quick-form', function ($view) {
            $view->with([
                'tutors' => User::where('role', 'tutor')->orderBy('last_name')->get(),
                'students' => User::where('role', 'student')->orderBy('last_name')->get()
            ]);
        });

        View::composer(['admin.sessions.partials.quick-form', 'admin.users.edit'], function ($view) {
            $view->with([
                'tutors' => User::isTutor()->orderBy('last_name')->get(),
                'students' => User::where('role', 'student')->orderBy('last_name')->get()
            ]);
        });

        View::composer('tutor.sessions.partials.quick-form', function ($view) {
            if (Auth::check() && Auth::user()->role === 'tutor') {
                $view->with('students', Auth::user()->assignedStudents()->get());
            } else {
                $view->with('students', collect());
            }
        });
    }
}
