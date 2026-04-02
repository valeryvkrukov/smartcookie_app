<?php

use Illuminate\Support\Facades\Route;

// Auth & Global
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;

// Customer (Parents)
use App\Http\Controllers\Customer\CalendarController as CustomerCalendarController;
use App\Http\Controllers\Customer\CreditController as CustomerCreditController;
use App\Http\Controllers\Customer\AgreementController as CustomerAgreementController;
use App\Http\Controllers\Customer\StudentController as CustomerStudentController;

// Tutor
use App\Http\Controllers\Tutor\SessionController as TutorSessionController;
use App\Http\Controllers\Tutor\TimesheetController as TutorTimesheetController;
use App\Http\Controllers\Tutor\StudentController as TutorStudentController;
use App\Http\Controllers\Tutor\CalendarController as TutorCalendarController;

// Admin
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AgreementController as AdminAgreementController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\SessionController as AdminSessionController;
use App\Http\Controllers\Admin\FinancialController as AdminFinancialController;
use App\Http\Controllers\Admin\SubjectRateController as AdminSubjectRateController;

// --- PUBLIC ---
Route::get('/', function () { return view('welcome'); });

Route::get('register', [RegistrationController::class, 'show'])->name('register');
Route::post('register', [RegistrationController::class, 'store']);

// --- SHARED AUTH ---
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});

// --- CUSTOMER (PARENTS) ---
// Apply 'check.agreements', to block access to all customer routes 
// if they have pending agreements, except for the agreements page itself (handled inside the middleware)
Route::middleware(['auth', 'role:customer', 'check.agreements'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/calendar', [CustomerCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CustomerCalendarController::class, 'events'])->name('calendar.events');
    
    // Financials
    Route::get('/credits', [CustomerCreditController::class, 'index'])->name('credits.index');
    Route::post('/credits/purchase', [CustomerCreditController::class, 'purchase'])->name('credits.purchase');
    Route::get('/credits/success', [CustomerCreditController::class, 'success'])->name('credits.success');

    // Agreements (Исключены из Middleware CheckAgreements внутри самого класса)
    Route::get('/agreements', [CustomerAgreementController::class, 'index'])->name('agreements.index');
    Route::post('/agreements/sign', [CustomerAgreementController::class, 'sign'])->name('agreements.sign');

    Route::resource('/students', CustomerStudentController::class)->except(['show']);
});

// --- TUTOR ---
Route::middleware(['auth', 'role:tutor'])->prefix('tutor')->name('tutor.')->group(function () {
    Route::get('/calendar', [TutorCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [TutorCalendarController::class, 'events'])->name('calendar.events');
    
    Route::resource('sessions', TutorSessionController::class);
    Route::get('/students', [TutorStudentController::class, 'index'])->name('students.index');
    
    Route::get('/timesheets', [TutorTimesheetController::class, 'index'])->name('timesheets.index');
    Route::post('/timesheets/log', [TutorTimesheetController::class, 'store'])->name('timesheets.store');
});

// --- ADMIN ---
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Users Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Calendar & Sessions
    Route::get('/calendar', [AdminCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [AdminCalendarController::class, 'events'])->name('calendar.events');
    Route::resource('sessions', AdminSessionController::class);

    // Business & Financials
    Route::get('/financials', [AdminFinancialController::class, 'index'])->name('financials.index');
    Route::post('/subject-rates', [AdminSubjectRateController::class, 'store'])->name('subject-rates.store');
    Route::delete('/subject-rates/{rate}', [AdminSubjectRateController::class, 'destroy'])->name('subject-rates.destroy');

    // Compliance
    Route::get('/agreements', [AdminAgreementController::class, 'index'])->name('agreements.index');
    Route::post('/agreements/assign', [AdminAgreementController::class, 'assign'])->name('agreements.assign');
});

require __DIR__.'/auth.php';
