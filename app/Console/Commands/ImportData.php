<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Credit;
use App\Models\Agreement;
use App\Models\TutoringSession;
use App\Models\Timesheet;
use App\Models\SubjectRate;
use Carbon\Carbon;

class ImportData extends Command
{
    protected $signature = 'app:import-db-data
                            {--fresh              : Truncate all app data tables before importing}
                            {--fake               : Generate fake data instead of migrating legacy DB}
                            {--admins=1           : [fake] Number of admin accounts}
                            {--tutors=3           : [fake] Number of tutor accounts}
                            {--customers=5        : [fake] Number of customer (parent) accounts}
                            {--students=2         : [fake] Students per customer}
                            {--sessions=5         : [fake] Sessions per student (mix of past/future)}
                            {--password=password  : [fake] Default password for all generated accounts}
                            {--dry-run            : Preview what would be migrated without writing}';

    protected $description = 'Migrate legacy DB → new schema  OR  seed fake data. See --help for options.';

    // ── FK resolution maps  (legacy_id → new_id)
    private array $userMap      = []; // legacy users.id      → new users.id
    private array $studentMap   = []; // legacy students.student_id → new users.id
    private array $agreementMap = []; // legacy aggreements.aggreement_id → new agreements.id
    private array $sessionMap   = []; // legacy sessions.session_id → new tutoring_sessions.id

    // ── Legacy timezone label → PHP timezone identifier
    private const TIMEZONE_MAP = [
        'Eastern Time'              => 'America/New_York',
        'Central Time'              => 'America/Chicago',
        'Mountain Time'             => 'America/Denver',
        'Pacific Time'              => 'America/Los_Angeles',
        'Alaska'                    => 'America/Anchorage',
        'Hawaii'                    => 'Pacific/Honolulu',
        'Arizona'                   => 'America/Phoenix',
        'Indiana (East)'            => 'America/Indiana/Indianapolis',
        'Atlantic Time (Canada)'    => 'America/Halifax',
        'UTC'                       => 'UTC',
    ];

    // ── Legacy session status → new status
    private const STATUS_MAP = [
        'Confirm'             => 'Scheduled',
        'End'                 => 'Completed',
        'Cancel'              => 'Cancelled',
        'Canceled'            => 'Cancelled',
        'Cancelled'           => 'Cancelled',
        'Insufficient Credit' => 'Cancelled',
    ];

    // ── Tables to clear (in FK-safe order, innermost first)
    private const TRUNCATE_ORDER = [
        'timesheets',
        'credit_purchases',
        'tutoring_sessions',
        'subject_rates',
        'tutor_student_assignments',
        'agreement_requests',
        'agreements',
        'credits',
        'notifications',
        'sessions',          // Laravel sessions table
        'cache',
        'jobs',
        'users',
    ];

    // ── Fake data subjects pool
    private const SUBJECTS = [
        'SAT Math', 'Algebra I', 'Algebra II', 'Geometry',
        'Pre-Calculus', 'Calculus', 'ACT Math', 'English',
        'Writing', 'Reading Comprehension', 'Biology',
        'Chemistry', 'Physics', 'History', 'Spanish',
    ];

    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ── ENTRY POINT
    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function handle(): int
    {
        $this->newLine();

        if ($this->option('fresh')) {
            if (! $this->confirm('⚠  This will TRUNCATE all application data tables. Continue?', false)) {
                $this->warn('Aborted.');
                return 1;
            }
            if (! $this->option('dry-run')) {
                $this->truncateTables();
            } else {
                $this->warn('[dry-run] Would truncate all tables.');
            }
        }

        return $this->option('fake')
            ? $this->runFakeMode()
            : $this->runLegacyMigration();
    }

    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ── LEGACY MIGRATION
    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function runLegacyMigration(): int
    {
        try {
            DB::connection('legacy_mysql')->getPdo();
        } catch (\Exception $e) {
            $this->error('Cannot connect to legacy DB. Add the following to .env:');
            $this->line('  LEGACY_DB_HOST=127.0.0.1');
            $this->line('  LEGACY_DB_DATABASE=old_database_name');
            $this->line('  LEGACY_DB_USERNAME=root');
            $this->line('  LEGACY_DB_PASSWORD=secret');
            return 1;
        }

        $this->line('╔══════════════════════════════════════╗');
        $this->line('║   Legacy → New  Migration            ║');
        $this->line('╚══════════════════════════════════════╝');
        $this->newLine();

        $dry = $this->option('dry-run');

        Model::unguard();

        $this->migrateUsers($dry);
        $this->migrateStudents($dry);
        $this->migrateCredits($dry);
        $this->migrateAgreements($dry);
        $this->migrateAgreementRequests($dry);
        $this->migrateTutorAssignments($dry);
        $this->migrateSessions($dry);
        $this->migrateTimesheets($dry);
        $this->migrateSubjectRates($dry);

        Model::reguard();

        $this->newLine();
        $this->line($dry
            ? '<fg=yellow>✓ Dry-run complete — no data was written.</>'
            : '<fg=green>✓ Migration complete.</>');

        return 0;
    }

    // ── 1. Users (admin / customer / tutor) ─────────────────────

    private function migrateUsers(bool $dry): void
    {
        $this->line('<fg=yellow>→ [1/9] Users (admin / customer / tutor)…</>');

        $rows = DB::connection('legacy_mysql')->table('users')->get();
        $bar  = $this->output->createProgressBar($rows->count());
        $bar->start();

        $inserted = $updated = 0;

        foreach ($rows as $old) {
            $address = $this->buildAddress($old);
            $tz      = $this->mapTimezone($old->time_zone ?? '');

            $payload = [
                'first_name'        => $old->first_name,
                'last_name'         => $old->last_name,
                'role'              => $old->role,
                'password'          => $old->password,
                'address'           => $address,
                'phone'             => $old->phone,
                'time_zone'         => $tz,
                'blurb'             => $old->description,
                'photo'             => $old->image,
                //'stripe_id'         => $old->stripe_id,
                'is_subscribed'     => ($old->automated_email === 'Subscribe'),
                'is_admin'          => ($old->role === 'admin'),
                'can_tutor'         => ($old->role === 'tutor'),
                'email_verified_at' => now(),
                'updated_at'        => now(),
            ];

            if (! $dry) {
                $existing = DB::table('users')->where('email', $old->email)->first();

                if ($existing) {
                    DB::table('users')->where('id', $existing->id)->update($payload);
                    $newId = $existing->id;
                    $updated++;
                } else {
                    $newId = DB::table('users')->insertGetId(array_merge($payload, [
                        'email'      => $old->email,
                        'created_at' => $old->created_at ?? now(),
                    ]));
                    $inserted++;
                }

                $this->userMap[$old->id] = $newId;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$rows->count()} users (inserted: {$inserted}, updated: {$updated})</>");
    }

    // ── 2. Students (legacy `students` → users with role='student') ──

    private function migrateStudents(bool $dry): void
    {
        $this->line('<fg=yellow>→ [2/9] Students…</>');

        $rows    = DB::connection('legacy_mysql')->table('students')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $inserted = $updated = $skipped = 0;

        foreach ($rows as $old) {
            $parentNewId = $this->userMap[$old->user_id] ?? null;

            if (! $parentNewId) {
                $this->newLine();
                $this->warn("  ⚠ Student #{$old->student_id} ({$old->student_name}): parent legacy_id={$old->user_id} not found. Skipping.");
                $skipped++;
                $bar->advance();
                continue;
            }

            [$firstName, $lastName] = User::splitName($old->student_name);

            // Use provided email or a stable placeholder
            $email = $old->email ?: ('student_' . $old->student_id . '@migrated.local');

            $payload = [
                'first_name'       => $firstName,
                'last_name'        => $lastName,
                'role'             => 'student',
                'parent_id'        => $parentNewId,
                'student_grade'    => $old->grade,
                'student_school'   => $old->college,
                'tutoring_subject' => $old->subject,
                'tutoring_goals'   => $old->goal,
                'time_zone'        => 'America/New_York',
                'is_subscribed'    => false,
                'email_verified_at' => now(),
                'updated_at'       => now(),
            ];

            if (! $dry) {
                $existing = DB::table('users')->where('email', $email)->first();

                if ($existing) {
                    DB::table('users')->where('id', $existing->id)->update($payload);
                    $newId = $existing->id;
                    $updated++;
                } else {
                    $newId = DB::table('users')->insertGetId(array_merge($payload, [
                        'email'      => $email,
                        'password'   => Hash::make(Str::random(16)),
                        'created_at' => $old->created_at ?? now(),
                    ]));
                    $inserted++;
                }

                $this->studentMap[$old->student_id] = $newId;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$rows->count()} students (inserted: {$inserted}, updated: {$updated}, skipped: {$skipped})</>");
    }

    // ── 3. Credits ───────────────────────────────────────────────

    private function migrateCredits(bool $dry): void
    {
        $this->line('<fg=yellow>→ [3/9] Credits…</>');

        $rows    = DB::connection('legacy_mysql')->table('credits')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            $newUserId = $this->userMap[$old->user_id] ?? null;

            if (! $newUserId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Credits are only for customer accounts
            $role = DB::table('users')->where('id', $newUserId)->value('role');
            if ($role !== 'customer') {
                $bar->advance();
                continue;
            }

            // Clamp negative legacy balances to 0 (debt context doesn't map to new system)
            $balance = max(0.0, (float)($old->credit_balance ?? 0));
            $rate    = (float)($old->credit_cost ?? 0) ?: null;

            if (! $dry) {
                Credit::updateOrCreate(
                    ['user_id' => $newUserId],
                    [
                        'credit_balance'         => $balance,
                        'dollar_cost_per_credit' => $rate,
                        'created_at'             => $old->created_at ?? now(),
                        'updated_at'             => now(),
                    ]
                );
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} credits (skipped: {$skipped})</>");
    }

    // ── 4. Agreements (aggreements → agreements) ─────────────────

    private function migrateAgreements(bool $dry): void
    {
        $this->line('<fg=yellow>→ [4/9] Agreements…</>');

        $rows = DB::connection('legacy_mysql')->table('aggreements')->get();
        $migrated = 0;

        foreach ($rows as $old) {
            // Store just the filename, admin will copy PDFs to storage/agreements/
            $pdfPath = 'agreements/' . basename($old->file);

            if (! $dry) {
                $newId = DB::table('agreements')
                    ->where('name', $old->aggreement_name)
                    ->value('id');

                if (! $newId) {
                    $newId = DB::table('agreements')->insertGetId([
                        'name'       => $old->aggreement_name,
                        'pdf_path'   => $pdfPath,
                        'created_at' => $old->created_at ?? now(),
                        'updated_at' => $old->updated_at ?? now(),
                    ]);
                }

                $this->agreementMap[$old->aggreement_id] = $newId;
            }

            $migrated++;
        }

        $this->line(" <fg=green>✓ {$migrated} agreements</>");
    }

    // ── 5. Agreement requests (signed_aggreements) ───────────────

    private function migrateAgreementRequests(bool $dry): void
    {
        $this->line('<fg=yellow>→ [5/9] Agreement requests…</>');

        $rows    = DB::connection('legacy_mysql')->table('signed_aggreements')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            $newUserId     = $this->userMap[$old->user_id] ?? null;
            $newAgreementId = $this->agreementMap[$old->aggreement_id] ?? null;

            if (! $newUserId || ! $newAgreementId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Map legacy status to new enum
            $status = match ($old->status) {
                'Signed'  => 'Signed',
                'Pending' => 'Awaiting signature',
                default   => 'Awaiting signature',
            };

            if (! $dry) {
                DB::table('agreement_requests')->updateOrInsert(
                    ['user_id' => $newUserId, 'agreement_id' => $newAgreementId],
                    [
                        'status'           => $status,
                        'signed_full_name' => $old->user_name ?? null,
                        'signed_at'        => $this->parseDate($old->date),
                        'created_at'       => $old->created_at ?? now(),
                        'updated_at'       => $old->updated_at ?? now(),
                    ]
                );
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} request records (skipped: {$skipped})</>");
    }

    // ── 6. Tutor assignments (tutor_assign → tutor_student_assignments) ──

    private function migrateTutorAssignments(bool $dry): void
    {
        $this->line('<fg=yellow>→ [6/9] Tutor assignments…</>');

        $rows    = DB::connection('legacy_mysql')->table('tutor_assign')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            // Legacy student_id references students.student_id (NOT users.id)
            $newTutorId   = $this->userMap[$old->tutor_id] ?? null;
            $newStudentId = $this->studentMap[$old->student_id] ?? null;

            if (! $newTutorId || ! $newStudentId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (! $dry) {
                // Ensure the tutor is flagged can_tutor regardless of their primary role
                DB::table('users')->where('id', $newTutorId)->update(['can_tutor' => true]);

                // Check for existing assignment (unique constraint tutor_id + student_id)
                $exists = DB::table('tutor_student_assignments')
                    ->where('tutor_id', $newTutorId)
                    ->where('student_id', $newStudentId)
                    ->exists();

                if (! $exists) {
                    DB::table('tutor_student_assignments')->insert([
                        'tutor_id'       => $newTutorId,
                        'student_id'     => $newStudentId,
                        'hourly_payout'  => (float)($old->hourly_pay_rate ?? 25),
                        'created_at'     => $old->created_at ?? now(),
                        'updated_at'     => now(),
                    ]);
                }
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} assignments (skipped: {$skipped})</>");
    }

    // ── 7. Sessions ──────────────────────────────────────────────

    private function migrateSessions(bool $dry): void
    {
        $this->line('<fg=yellow>→ [7/9] Tutoring sessions…</>');

        $rows    = DB::connection('legacy_mysql')->table('sessions')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            // Legacy tutor_id → users.id directly
            $newTutorId   = $this->userMap[$old->tutor_id] ?? null;
            // Legacy student_id → students.student_id (separate lookup)
            $newStudentId = $this->studentMap[$old->student_id] ?? null;

            if (! $newTutorId || ! $newStudentId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Normalise start_time: "09:00" → "09:00:00"
            $startTime = $old->time ?? '00:00:00';
            if (strlen($startTime) === 5) {
                $startTime .= ':00';
            }

            // Normalise status
            $status = self::STATUS_MAP[$old->status] ?? 'Scheduled';

            // session_type → is_initial
            $isInitial = ($old->session_type === 'First Session');

            // recurs_weekly enum: 'Yes'/'No' → boolean
            $recurringWeekly = in_array(strtolower($old->recurs_weekly ?? ''), ['yes', '1', 'true', 'on'], true);

            // Reason stored as tutor_notes for cancelled sessions
            $tutorNotes = ($status === 'Cancelled' && $old->reason) ? $old->reason : null;

            if (! $dry) {
                // Idempotent: match on the business key
                $existing = DB::table('tutoring_sessions')
                    ->where('tutor_id', $newTutorId)
                    ->where('student_id', $newStudentId)
                    ->where('date', $this->parseDate($old->date))
                    ->where('start_time', $startTime)
                    ->where('subject', $old->subject)
                    ->first();

                if ($existing) {
                    $newId = $existing->id;
                } else {
                    $newId = DB::table('tutoring_sessions')->insertGetId([
                        'tutor_id'       => $newTutorId,
                        'student_id'     => $newStudentId,
                        'subject'        => $old->subject,
                        'date'           => $this->parseDate($old->date),
                        'start_time'     => $startTime,
                        'duration'       => $old->duration,
                        'location'       => $old->location ?: null,
                        'is_initial'     => $isInitial,
                        'recurs_weekly'  => $recurringWeekly,
                        'status'         => $status,
                        'tutor_notes'    => $tutorNotes,
                        'created_at'     => $old->created_at ?? now(),
                        'updated_at'     => now(),
                    ]);
                }

                $this->sessionMap[$old->session_id] = $newId;
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} sessions (skipped: {$skipped})</>");
    }

    // ── 8. Timesheets ────────────────────────────────────────────
    // Legacy timesheets are ad-hoc tutor logs; new timesheets are billing
    // records linked 1:1 to a completed session.
    // Strategy: match by (tutor, student, date, duration) → find migrated session.

    private function migrateTimesheets(bool $dry): void
    {
        $this->line('<fg=yellow>→ [8/9] Timesheets…</>');

        $rows    = DB::connection('legacy_mysql')->table('timesheets')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            $newTutorId   = $this->userMap[$old->tutor_id] ?? null;
            $newStudentId = $this->studentMap[$old->student_id] ?? null;

            if (! $newTutorId || ! $newStudentId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Find the corresponding migrated session by business key
            $session = DB::table('tutoring_sessions')
                ->where('tutor_id', $newTutorId)
                ->where('student_id', $newStudentId)
                ->where('date', $this->parseDate($old->date))
                ->where('duration', $old->duration)
                ->where('status', 'Completed')
                ->first();

            if (! $session) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Avoid duplicate (unique tutoring_session_id constraint)
            $alreadyExists = DB::table('timesheets')
                ->where('tutoring_session_id', $session->id)
                ->exists();

            if ($alreadyExists) {
                $bar->advance();
                continue;
            }

            // Resolve billing party: student's parent, or student themselves
            $student      = DB::table('users')->where('id', $newStudentId)->first();
            $billedUserId = $student->parent_id ?? $newStudentId;

            // Calculate credits spent from duration (e.g. "1:00" → 1.0, "0:30" → 0.5)
            $creditsSpent = Timesheet::calculateCredits($old->duration);
            $tutorPayout  = $creditsSpent * (float)($old->hourly_pay_rate ?? 0);
            $period       = Carbon::parse($old->date)->format('F Y');

            if (! $dry) {
                DB::table('timesheets')->insert([
                    'tutoring_session_id' => $session->id,
                    'tutor_id'            => $newTutorId,
                    'parent_id'           => $billedUserId,
                    'credits_spent'       => $creditsSpent,
                    'tutor_payout'        => $tutorPayout,
                    'period'              => $period,
                    'created_at'          => $old->created_at ?? now(),
                    'updated_at'          => now(),
                ]);
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} timesheets (skipped/unmatched: {$skipped})</>");
    }

    // ── 9. Subject rates ─────────────────────────────────────────
    // Derive from unique (student, subject) pairs seen in sessions,
    // using that student's credit cost as the per-credit rate.

    private function migrateSubjectRates(bool $dry): void
    {
        $this->line('<fg=yellow>→ [9/9] Subject rates…</>');

        $pairs = DB::table('tutoring_sessions')
            ->select('student_id', 'subject')
            ->distinct()
            ->get();

        $migrated = 0;

        foreach ($pairs as $pair) {
            // Find the rate for this student from their parent's credit record
            $student  = DB::table('users')->where('id', $pair->student_id)->first();
            $parentId = $student?->parent_id ?? $pair->student_id;
            $rate     = DB::table('credits')->where('user_id', $parentId)->value('dollar_cost_per_credit');

            if (! $rate) {
                continue;
            }

            if (! $dry) {
                $exists = DB::table('subject_rates')
                    ->where('student_id', $pair->student_id)
                    ->where('subject', $pair->subject)
                    ->exists();

                if (! $exists) {
                    DB::table('subject_rates')->insert([
                        'student_id'  => $pair->student_id,
                        'subject'     => $pair->subject,
                        'rate'        => $rate,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            $migrated++;
        }

        $this->line(" <fg=green>✓ {$migrated} subject rates</>");
    }

    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ── FAKE DATA MODE
    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function runFakeMode(): int
    {
        $admins   = (int) $this->option('admins');
        $tutors   = (int) $this->option('tutors');
        $customers = (int) $this->option('customers');
        $studentsPerCustomer = (int) $this->option('students');
        $sessionsPerStudent  = (int) $this->option('sessions');
        $password = (string) $this->option('password');
        $dry      = $this->option('dry-run');

        $this->line('╔══════════════════════════════════════╗');
        $this->line('║   Fake Data Seeder                   ║');
        $this->line('╚══════════════════════════════════════╝');
        $this->newLine();
        $this->line("  Admins:    {$admins}");
        $this->line("  Tutors:    {$tutors}");
        $this->line("  Customers: {$customers}  (× {$studentsPerCustomer} students each)");
        $this->line("  Sessions:  {$sessionsPerStudent} per student");
        $this->line("  Password:  {$password}");
        $this->newLine();

        if ($dry) {
            $totalStudents = $customers * $studentsPerCustomer;
            $totalSessions = $totalStudents * $sessionsPerStudent;
            $this->warn("[dry-run] Would create: {$admins} admins, {$tutors} tutors, {$customers} customers, {$totalStudents} students, {$totalSessions} sessions.");
            return 0;
        }

        $hashedPw = Hash::make($password);

        $seededTutors    = $this->seedAdminsAndTutors($admins, $tutors, $hashedPw);
        $seededCustomers = $this->seedCustomers($customers, $studentsPerCustomer, $hashedPw);
        $this->seedAgreements();
        $this->seedSessionsAndTimesheets($seededCustomers, $seededTutors, $sessionsPerStudent);
        $this->seedCreditPurchases($seededCustomers);
        $this->seedNotifications($seededCustomers);

        $this->newLine();
        $this->line('<fg=green>✓ Fake data seeded.</>');
        $this->line("  Login password for all accounts: <fg=yellow>{$password}</>");

        return 0;
    }

    // ── Seed admins + tutors ──────────────────────────────────────

    private function seedAdminsAndTutors(int $adminCount, int $tutorCount, string $hashedPw): array
    {
        $faker  = \Faker\Factory::create();
        $tutors = [];

        $this->line('<fg=yellow>→ Seeding admins…</>');
        for ($i = 0; $i < $adminCount; $i++) {
            DB::table('users')->insert([
                'first_name'        => $faker->firstName(),
                'last_name'         => $faker->lastName(),
                'email'             => $faker->unique()->safeEmail(),
                'password'          => $hashedPw,
                'role'              => 'admin',
                'is_admin'          => true,
                'is_subscribed'     => true,
                'can_tutor'         => false,
                'time_zone'         => 'America/New_York',
                'phone'             => $faker->phoneNumber(),
                'address'           => $faker->address(),
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
        $this->line("  <fg=green>✓ {$adminCount} admins</>");

        $this->line('<fg=yellow>→ Seeding tutors…</>');
        for ($i = 0; $i < $tutorCount; $i++) {
            $subjects = array_slice(self::SUBJECTS, rand(0, 5), rand(2, 4));

            $id = DB::table('users')->insertGetId([
                'first_name'        => $faker->firstName(),
                'last_name'         => $faker->lastName(),
                'email'             => $faker->unique()->safeEmail(),
                'password'          => $hashedPw,
                'role'              => 'tutor',
                'is_admin'          => false,
                'is_subscribed'     => true,
                'can_tutor'         => true,
                'time_zone'         => $faker->randomElement(['America/New_York', 'America/Chicago', 'America/Los_Angeles']),
                'phone'             => $faker->phoneNumber(),
                'address'           => $faker->address(),
                'blurb'             => $faker->paragraph(3),
                'tutoring_subject'  => implode(', ', $subjects),
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $tutors[] = $id;
        }
        $this->line("  <fg=green>✓ {$tutorCount} tutors</>");

        return $tutors;
    }

    // ── Seed customers + their students + credits ─────────────────

    private function seedCustomers(int $customerCount, int $studentsPerCustomer, string $hashedPw): array
    {
        $faker   = \Faker\Factory::create();
        $seeded  = []; // [customer_id => [student_ids]]

        $this->line('<fg=yellow>→ Seeding customers + students + credits…</>');
        $bar = $this->output->createProgressBar($customerCount);
        $bar->start();

        for ($i = 0; $i < $customerCount; $i++) {
            // Create customer
            $customerId = DB::table('users')->insertGetId([
                'first_name'        => $faker->firstName(),
                'last_name'         => $faker->lastName(),
                'email'             => $faker->unique()->safeEmail(),
                'password'          => $hashedPw,
                'role'              => 'customer',
                'is_admin'          => false,
                'is_subscribed'     => $faker->boolean(80),
                'can_tutor'         => false,
                'time_zone'         => $faker->randomElement(['America/New_York', 'America/Chicago', 'America/Los_Angeles', 'America/Denver']),
                'phone'             => $faker->phoneNumber(),
                'address'           => $faker->address(),
                'email_verified_at' => now(),
                'created_at'        => now()->subMonths(rand(1, 24)),
                'updated_at'        => now(),
            ]);

            // Credit record for customer
            $ratePerCredit = $faker->randomElement([39, 45, 49, 50, 54, 59, 64, 70, 75, 79, 85, 89, 125]);
            DB::table('credits')->insert([
                'user_id'                => $customerId,
                'credit_balance'         => $faker->randomFloat(1, 0, 10),
                'dollar_cost_per_credit' => $ratePerCredit,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            // Create students
            $studentIds = [];
            for ($j = 0; $j < $studentsPerCustomer; $j++) {
                $grade    = $faker->randomElement(['6', '7', '8', '9', '10', '11', '12', 'College Freshman', 'College Sophomore']);
                $subjects = array_slice(self::SUBJECTS, rand(0, 10), rand(1, 3));

                $studentId = DB::table('users')->insertGetId([
                    'first_name'        => $faker->firstName(),
                    'last_name'         => $faker->lastName(),
                    'email'             => $faker->unique()->safeEmail(),
                    'password'          => Hash::make(Str::random(16)),
                    'role'              => 'student',
                    'parent_id'         => $customerId,
                    'student_grade'     => $grade,
                    'student_school'    => $faker->company() . ' School',
                    'tutoring_subject'  => implode(', ', $subjects),
                    'tutoring_goals'    => $faker->sentence(12),
                    'time_zone'         => 'America/New_York',
                    'is_subscribed'     => false,
                    'email_verified_at' => now(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                // Subject rates per student
                foreach ($subjects as $subject) {
                    DB::table('subject_rates')->insert([
                        'student_id'  => $studentId,
                        'subject'     => $subject,
                        'rate'        => $ratePerCredit,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }

                $studentIds[] = $studentId;
            }

            $seeded[$customerId] = $studentIds;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  <fg=green>✓ {$customerCount} customers, " . ($customerCount * $studentsPerCustomer) . " students</>");

        return $seeded;
    }

    // ── Seed agreements ───────────────────────────────────────────

    private function seedAgreements(): void
    {
        $this->line('<fg=yellow>→ Seeding agreements…</>');

        $docs = [
            ['name' => 'Tutoring Services Agreement',   'pdf_path' => 'agreements/services-agreement.pdf'],
            ['name' => 'Tutoring Contract (Standard)',  'pdf_path' => 'agreements/tutoring-contract.pdf'],
            ['name' => 'Services and Policies',         'pdf_path' => 'agreements/policies.pdf'],
        ];

        foreach ($docs as $doc) {
            DB::table('agreements')->updateOrInsert(
                ['name' => $doc['name']],
                array_merge($doc, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $this->line('  <fg=green>✓ 3 agreements</>');
    }

    // ── Seed sessions + timesheets ────────────────────────────────

    private function seedSessionsAndTimesheets(array $customerStudentMap, array $tutorIds, int $sessionsPerStudent): void
    {
        if (empty($tutorIds)) {
            $this->warn('  ⚠ No tutors found — sessions skipped.');
            return;
        }

        $faker        = \Faker\Factory::create();
        $totalStudents = array_sum(array_map('count', $customerStudentMap));

        $this->line('<fg=yellow>→ Seeding sessions + timesheets…</>');
        $bar = $this->output->createProgressBar($totalStudents);
        $bar->start();

        $totalSessions   = 0;
        $totalTimesheets = 0;

        foreach ($customerStudentMap as $customerId => $studentIds) {
            $parentCredit = DB::table('credits')->where('user_id', $customerId)->first();
            $ratePerCredit = (float)($parentCredit?->dollar_cost_per_credit ?? 65);

            foreach ($studentIds as $studentId) {
                $subjects     = DB::table('subject_rates')->where('student_id', $studentId)->pluck('subject')->toArray();
                $tutorId      = $tutorIds[array_rand($tutorIds)];
                $tutorPayout  = DB::table('tutor_student_assignments')
                    ->where('tutor_id', $tutorId)->where('student_id', $studentId)
                    ->value('hourly_payout') ?? 50;

                // Assign tutor to student
                $exists = DB::table('tutor_student_assignments')
                    ->where('tutor_id', $tutorId)->where('student_id', $studentId)->exists();
                if (! $exists) {
                    DB::table('tutor_student_assignments')->insert([
                        'tutor_id'      => $tutorId,
                        'student_id'    => $studentId,
                        'hourly_payout' => $faker->randomElement([25, 30, 35, 40, 45, 50]),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }

                $tutorPayout = DB::table('tutor_student_assignments')
                    ->where('tutor_id', $tutorId)->where('student_id', $studentId)
                    ->value('hourly_payout');

                for ($s = 0; $s < $sessionsPerStudent; $s++) {
                    $subject     = $subjects ? $subjects[array_rand($subjects)] : $faker->randomElement(self::SUBJECTS);
                    $duration    = $faker->randomElement(['0:30', '1:00', '1:30', '2:00']);
                    $daysOffset  = rand(-180, 30);                  // spread over -6 months to +1 month
                    $date        = now()->addDays($daysOffset)->format('Y-m-d');
                    $hour        = rand(9, 19);
                    $minute      = $faker->randomElement(['00', '30']);
                    $startTime   = sprintf('%02d:%s:00', $hour, $minute);

                    $isPast = $daysOffset < 0;

                    $status = match (true) {
                        $isPast && rand(1, 10) <= 7 => 'Completed',     // 70% completed
                        $isPast && rand(1, 10) <= 9 => 'Cancelled',     // 20% cancelled
                        $isPast                    => 'Scheduled',      // 10% still scheduled
                        default                    => 'Scheduled',      // future → scheduled
                    };

                    $sessionId = DB::table('tutoring_sessions')->insertGetId([
                        'tutor_id'       => $tutorId,
                        'student_id'     => $studentId,
                        'subject'        => $subject,
                        'date'           => $date,
                        'start_time'     => $startTime,
                        'duration'       => $duration,
                        'location'       => $faker->boolean(70) ? 'Online' : $faker->city(),
                        'is_initial'     => ($s === 0),
                        'recurs_weekly'  => $faker->boolean(30),
                        'status'         => $status,
                        'tutor_rate'     => $ratePerCredit,
                        'tutor_notes'    => $status === 'Completed' ? $faker->paragraph(2) : null,
                        'created_at'     => now()->addDays($daysOffset)->subDays(3),
                        'updated_at'     => now(),
                    ]);

                    $totalSessions++;

                    // Create timesheet for completed sessions
                    if ($status === 'Completed') {
                        $creditsSpent = Timesheet::calculateCredits($duration);
                        $payout       = $creditsSpent * $tutorPayout;
                        $period       = Carbon::parse($date)->format('F Y');

                        DB::table('timesheets')->insert([
                            'tutoring_session_id' => $sessionId,
                            'tutor_id'            => $tutorId,
                            'parent_id'           => $customerId,
                            'credits_spent'       => $creditsSpent,
                            'tutor_payout'        => $payout,
                            'period'              => $period,
                            'created_at'          => now()->addDays($daysOffset),
                            'updated_at'          => now(),
                        ]);

                        $totalTimesheets++;
                    }
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->line("  <fg=green>✓ {$totalSessions} sessions, {$totalTimesheets} timesheets</>");
    }

    // ── Seed credit purchases (financials) ──────────────────────

    private function seedCreditPurchases(array $customerStudentMap): void
    {
        $this->line('<fg=yellow>→ Seeding credit purchases…</>');

        $faker = \Faker\Factory::create();
        $total = 0;

        foreach (array_keys($customerStudentMap) as $customerId) {
            $credit = DB::table('credits')->where('user_id', $customerId)->first();
            $rate   = (float)($credit?->dollar_cost_per_credit ?? 65);

            // 2–5 historical purchases per customer
            $count = rand(2, 5);
            for ($i = 0; $i < $count; $i++) {
                $creditsBought = $faker->randomElement([5, 10, 15, 20]);
                $daysAgo       = rand(10, 540);

                DB::table('credit_purchases')->insert([
                    'user_id'           => $customerId,
                    'amount'            => $creditsBought,
                    'credits_purchased' => $creditsBought,
                    'total_paid'        => round($creditsBought * $rate, 2),
                    'stripe_session_id' => 'fake_' . \Illuminate\Support\Str::random(24),
                    'type'              => 'stripe',
                    'created_at'        => now()->subDays($daysAgo),
                    'updated_at'        => now()->subDays($daysAgo),
                ]);

                $total++;
            }
        }

        $this->line("  <fg=green>✓ {$total} credit purchases</>");
    }

    // ── Seed notifications (system logs) ─────────────────────────

    private function seedNotifications(array $customerStudentMap): void
    {
        $this->line('<fg=yellow>→ Seeding notifications (system logs)…</>');

        $faker   = \Faker\Factory::create();
        $adminId = DB::table('users')->where('is_admin', true)->value('id');

        if (! $adminId) {
            $this->warn('  ⚠ No admin found — notifications skipped.');
            return;
        }

        $total = 0;

        foreach (array_keys($customerStudentMap) as $customerId) {
            $customer = DB::table('users')->where('id', $customerId)->first();
            if (! $customer) {
                continue;
            }

            $students = DB::table('users')->where('parent_id', $customerId)->get();

            // NewClientRegistered
            DB::table('notifications')->insert([
                'id'              => \Illuminate\Support\Str::uuid(),
                'type'            => \App\Notifications\NewClientRegistered::class,
                'notifiable_type' => \App\Models\User::class,
                'notifiable_id'   => $adminId,
                'data'            => json_encode([
                    'type'          => 'new_client_registered',
                    'parent_id'     => $customer->id,
                    'parent_name'   => trim($customer->first_name . ' ' . $customer->last_name),
                    'student_name'  => $students->first()?->first_name . ' ' . $students->first()?->last_name,
                    'student_grade' => $students->first()?->student_grade ?? null,
                    'student_school'=> $students->first()?->student_school ?? null,
                    'message'       => 'A new client registered on the portal.',
                ]),
                'read_at'         => $faker->boolean(60) ? now()->subDays(rand(1, 30)) : null,
                'created_at'      => now()->subDays(rand(30, 365)),
                'updated_at'      => now()->subDays(rand(1, 29)),
            ]);
            $total++;

            // CreditsPurchased — one notification per customer
            $tutorId = DB::table('tutor_student_assignments')
                ->join('users', 'tutor_student_assignments.student_id', '=', 'users.id')
                ->where('users.parent_id', $customerId)
                ->value('tutor_student_assignments.tutor_id');

            if ($tutorId) {
                DB::table('notifications')->insert([
                    'id'              => \Illuminate\Support\Str::uuid(),
                    'type'            => \App\Notifications\CreditsPurchased::class,
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id'   => $tutorId,
                    'data'            => json_encode([
                        'type'              => 'credits_purchased',
                        'client_id'         => $customer->id,
                        'client_name'       => trim($customer->first_name . ' ' . $customer->last_name),
                        'credits_purchased' => $faker->randomElement([5, 10, 15, 20]),
                        'message'           => 'Client purchased credits.',
                    ]),
                    'read_at'         => $faker->boolean(50) ? now()->subDays(rand(1, 20)) : null,
                    'created_at'      => now()->subDays(rand(1, 180)),
                    'updated_at'      => now()->subDays(rand(0, 10)),
                ]);
                $total++;
            }

            // SessionScheduled — one per customer's first student
            $session = DB::table('tutoring_sessions')
                ->join('users', 'tutoring_sessions.student_id', '=', 'users.id')
                ->where('users.parent_id', $customerId)
                ->select('tutoring_sessions.*')
                ->first();

            if ($session) {
                DB::table('notifications')->insert([
                    'id'              => \Illuminate\Support\Str::uuid(),
                    'type'            => \App\Notifications\SessionScheduled::class,
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id'   => $customerId,
                    'data'            => json_encode([
                        'type'       => 'session_scheduled',
                        'session_id' => $session->id,
                        'subject'    => $session->subject,
                        'date'       => $session->date,
                        'message'    => 'A session has been scheduled.',
                    ]),
                    'read_at'         => $faker->boolean(70) ? now()->subDays(rand(1, 30)) : null,
                    'created_at'      => now()->subDays(rand(1, 90)),
                    'updated_at'      => now()->subDays(rand(0, 5)),
                ]);
                $total++;
            }
        }

        $this->line("  <fg=green>✓ {$total} notifications</>");
    }

    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ── HELPERS
    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function truncateTables(): void
    {
        $this->line('<fg=red>→ Truncating tables…</>');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::TRUNCATE_ORDER as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->line('  <fg=green>✓ Done</>');
    }

    private function mapTimezone(string $legacy): string
    {
        $legacy = trim($legacy);

        if (isset(self::TIMEZONE_MAP[$legacy])) {
            return self::TIMEZONE_MAP[$legacy];
        }

        // Already a valid PHP timezone (e.g. "America/New_York")
        if ($legacy && in_array($legacy, \DateTimeZone::listIdentifiers(), true)) {
            return $legacy;
        }

        return 'America/New_York'; // Safe default for US-based tutoring platform
    }

    private function buildAddress(object $old): ?string
    {
        $parts = array_filter([
            $old->address ?? null,
            $old->city    ?? null,
            $old->state   ?? null,
            $old->zip     ?? null,
        ]);

        if (empty($parts)) {
            return null;
        }

        // If address already contains city, just return address
        if (($old->address ?? null) && ($old->city ?? null) && str_contains($old->address, $old->city)) {
            return $old->address;
        }

        return implode(', ', $parts);
    }

    private function parseDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            // MM/DD/YY — 2-digit year (e.g. 11/21/21)
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2}$/', $date)) {
                return Carbon::createFromFormat('m/d/y', $date)->toDateString();
            }

            // MM/DD/YYYY — 4-digit year (e.g. 12/02/2020)
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)) {
                return Carbon::createFromFormat('m/d/Y', $date)->toDateString();
            }

            // Fallback: YYYY-MM-DD or anything Carbon can parse
            return Carbon::parse($date)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
