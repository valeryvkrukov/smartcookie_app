<?php

use Illuminate\Support\Facades\Route;

// ── Imports: auth and global controllers
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;

// ── Imports: customer (parent) controllers
use App\Http\Controllers\Customer\CalendarController as CustomerCalendarController;
use App\Http\Controllers\Customer\CreditController as CustomerCreditController;
use App\Http\Controllers\Customer\AgreementController as CustomerAgreementController;
use App\Http\Controllers\Customer\StudentController as CustomerStudentController;

// ── Imports: tutor controllers
use App\Http\Controllers\Tutor\SessionController as TutorSessionController;
use App\Http\Controllers\Tutor\TimesheetController as TutorTimesheetController;
use App\Http\Controllers\Tutor\StudentController as TutorStudentController;
use App\Http\Controllers\Tutor\CalendarController as TutorCalendarController;

// ── Imports: admin controllers
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AgreementController as AdminAgreementController;
use App\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Http\Controllers\Admin\SessionController as AdminSessionController;
use App\Http\Controllers\Admin\FinancialController as AdminFinancialController;
use App\Http\Controllers\Admin\SubjectRateController as AdminSubjectRateController;
use App\Http\Controllers\Admin\SystemLogController as AdminSystemLogController;

// ── Public: unauthenticated routes (welcome page, registration)
Route::get('/', function () { return view('welcome'); });

Route::get('register', [RegistrationController::class, 'show'])->name('register');
Route::post('register', [RegistrationController::class, 'store']);

// ── Shared: authenticated routes available to all roles
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Ping: session keepalive called by JS heartbeat every 30 min to prevent CSRF expiry
    Route::get('/ping', fn() => response()->json(['ok' => true, 'csrf' => csrf_token()]))->name('ping');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});

// ── Customer: routes scoped to parent role; check.agreements blocks access until all agreements are signed
Route::middleware(['auth', 'role:customer', 'check.agreements'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/calendar', [CustomerCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CustomerCalendarController::class, 'events'])->name('calendar.events');
    
    // ── Financials: credit purchase and balance routes
    Route::get('/credits', [CustomerCreditController::class, 'index'])->name('credits.index');
    Route::post('/credits/purchase', [CustomerCreditController::class, 'purchase'])->name('credits.purchase');
    Route::get('/credits/success', [CustomerCreditController::class, 'success'])->name('credits.success');

    // ── Cancellation: client-initiated session cancel
    Route::delete('/calendar/sessions/{session}', [CustomerCalendarController::class, 'cancel'])->name('calendar.cancel');

    // ── Agreements: excluded from CheckAgreements middleware (self-exempt route)
    Route::get('/agreements', [CustomerAgreementController::class, 'index'])->name('agreements.index');
    Route::post('/agreements/sign', [CustomerAgreementController::class, 'sign'])->name('agreements.sign');

    Route::resource('/students', CustomerStudentController::class)->except(['show']);
});

// ── Tutor: routes scoped to tutor role
Route::middleware(['auth', 'role:tutor'])->prefix('tutor')->name('tutor.')->group(function () {
    Route::get('/calendar', [TutorCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [TutorCalendarController::class, 'events'])->name('calendar.events');
    
    Route::resource('sessions', TutorSessionController::class);
    Route::get('/students', [TutorStudentController::class, 'index'])->name('students.index');
    
    Route::get('/timesheets', [TutorTimesheetController::class, 'index'])->name('timesheets.index');
    Route::post('/timesheets/log', [TutorTimesheetController::class, 'store'])->name('timesheets.store');
    Route::post('/timesheets/session-log', [TutorTimesheetController::class, 'log'])->name('timesheets.log');
});

// ── Admin: routes scoped to admin role
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // ── Users: user management CRUD
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // ── Calendar: admin calendar and session management
    Route::get('/calendar', [AdminCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [AdminCalendarController::class, 'events'])->name('calendar.events');
    Route::resource('sessions', AdminSessionController::class);

    // ── Financials: business reports and subject rate configuration
    Route::get('/financials', [AdminFinancialController::class, 'index'])->name('financials.index');
    Route::post('/subject-rates', [AdminSubjectRateController::class, 'store'])->name('subject-rates.store');
    Route::delete('/subject-rates/{rate}', [AdminSubjectRateController::class, 'destroy'])->name('subject-rates.destroy');

    // ── Compliance: agreement management and user assignment
    Route::get('/agreements', [AdminAgreementController::class, 'index'])->name('agreements.index');
    Route::post('/agreements', [AdminAgreementController::class, 'store'])->name('agreements.store');
    Route::post('/agreements/assign', [AdminAgreementController::class, 'assign'])->name('agreements.assign');

    // ── Logs: system event log with read/unread management
    Route::get('/system-logs', [AdminSystemLogController::class, 'index'])->name('system-logs.index');
    Route::post('/system-logs/mark-read', [AdminSystemLogController::class, 'markRead'])->name('system-logs.mark-read');
    Route::post('/system-logs/mark-all-read', [AdminSystemLogController::class, 'markAllRead'])->name('system-logs.mark-all-read');
});

require __DIR__.'/auth.php';
