<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Timesheet;

class FillWithFakeData extends Command
{
    protected $signature = 'app:fill-with-fake-data
                            {--fresh              : Truncate all app data tables before seeding}
                            {--admins=1           : Number of admin accounts}
                            {--tutors=3           : Number of tutor accounts}
                            {--customers=5        : Number of customer (parent) accounts}
                            {--students=2         : Students per customer}
                            {--sessions=5         : Sessions per student (mix of past/future)}
                            {--password=password  : Default password for all generated accounts}
                            {--dry-run            : Preview what would be seeded without writing}';

    protected $description = 'Seed the database with realistic fake data for development/testing.';

    private const SUBJECTS = [
        'SAT Math', 'Algebra I', 'Algebra II', 'Geometry',
        'Pre-Calculus', 'Calculus', 'ACT Math', 'English',
        'Writing', 'Reading Comprehension', 'Biology',
        'Chemistry', 'Physics', 'History', 'Spanish',
    ];

    // Tables to clear in FK-safe order (innermost first)
    private const TRUNCATE_ORDER = [
        'timesheets',
        'credit_purchases',
        'tutoring_sessions',
        'session_series',
        'subject_rates',
        'tutor_student_assignments',
        'agreement_requests',
        'agreements',
        'credits',
        'notifications',
        'tutor_profiles',
        'student_profiles',
        'sessions',
        'cache',
        'jobs',
        'users',
    ];

    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ── ENTRY POINT
    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function handle(): int
    {
        $this->newLine();

        $admins              = (int) $this->option('admins');
        $tutors              = (int) $this->option('tutors');
        $customers           = (int) $this->option('customers');
        $studentsPerCustomer = (int) $this->option('students');
        $sessionsPerStudent  = (int) $this->option('sessions');
        $password            = (string) $this->option('password');
        $dry                 = $this->option('dry-run');

        if ($this->option('fresh')) {
            if (! $this->confirm('⚠  This will TRUNCATE all application data tables. Continue?', false)) {
                $this->warn('Aborted.');
                return 1;
            }
            if (! $dry) {
                $this->truncateTables();
            } else {
                $this->warn('[dry-run] Would truncate all tables.');
            }
        }

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
                'is_subscribed'     => true,
                'can_tutor'         => true,
                'time_zone'         => $faker->randomElement(['America/New_York', 'America/Chicago', 'America/Los_Angeles']),
                'phone'             => $faker->phoneNumber(),
                'address'           => $faker->address(),
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            DB::table('tutor_profiles')->insert([
                'user_id'          => $id,
                'blurb'            => $faker->paragraph(3),
                'tutoring_subject' => implode(', ', $subjects),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            $tutors[] = $id;
        }
        $this->line("  <fg=green>✓ {$tutorCount} tutors</>");

        return $tutors;
    }

    // ── Seed customers + their students + credits ─────────────────

    private function seedCustomers(int $customerCount, int $studentsPerCustomer, string $hashedPw): array
    {
        $faker  = \Faker\Factory::create();
        $seeded = []; // [customer_id => [student_ids]]

        $this->line('<fg=yellow>→ Seeding customers + students + credits…</>');
        $bar = $this->output->createProgressBar($customerCount);
        $bar->start();

        for ($i = 0; $i < $customerCount; $i++) {
            $customerId = DB::table('users')->insertGetId([
                'first_name'        => $faker->firstName(),
                'last_name'         => $faker->lastName(),
                'email'             => $faker->unique()->safeEmail(),
                'password'          => $hashedPw,
                'role'              => 'customer',
                'is_subscribed'     => $faker->boolean(80),
                'can_tutor'         => false,
                'time_zone'         => $faker->randomElement(['America/New_York', 'America/Chicago', 'America/Los_Angeles', 'America/Denver']),
                'phone'             => $faker->phoneNumber(),
                'address'           => $faker->address(),
                'email_verified_at' => now(),
                'created_at'        => now()->subMonths(rand(1, 24)),
                'updated_at'        => now(),
            ]);

            $ratePerCredit = $faker->randomElement([39, 45, 49, 50, 54, 59, 64, 70, 75, 79, 85, 89, 125]);
            DB::table('credits')->insert([
                'user_id'                => $customerId,
                'credit_balance'         => $faker->randomFloat(1, 0, 10),
                'dollar_cost_per_credit' => $ratePerCredit,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

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
                    'time_zone'         => 'America/New_York',
                    'is_subscribed'     => false,
                    'email_verified_at' => now(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                DB::table('student_profiles')->insert([
                    'user_id'        => $studentId,
                    'student_grade'  => $grade,
                    'student_school' => $faker->company() . ' School',
                    'tutoring_goals' => $faker->sentence(12),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                foreach ($subjects as $subject) {
                    DB::table('subject_rates')->insert([
                        'student_id' => $studentId,
                        'subject'    => $subject,
                        'rate'       => $ratePerCredit,
                        'created_at' => now(),
                        'updated_at' => now(),
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
            ['name' => 'Tutoring Services Agreement',  'pdf_path' => 'agreements/services-agreement.pdf'],
            ['name' => 'Tutoring Contract (Standard)', 'pdf_path' => 'agreements/tutoring-contract.pdf'],
            ['name' => 'Services and Policies',        'pdf_path' => 'agreements/policies.pdf'],
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

        $faker         = \Faker\Factory::create();
        $totalStudents = array_sum(array_map('count', $customerStudentMap));

        $this->line('<fg=yellow>→ Seeding sessions + timesheets…</>');
        $bar = $this->output->createProgressBar($totalStudents);
        $bar->start();

        $totalSessions   = 0;
        $totalTimesheets = 0;

        foreach ($customerStudentMap as $customerId => $studentIds) {
            $parentCredit  = DB::table('credits')->where('user_id', $customerId)->first();
            $ratePerCredit = (float)($parentCredit?->dollar_cost_per_credit ?? 65);

            foreach ($studentIds as $studentId) {
                $subjects    = DB::table('subject_rates')->where('student_id', $studentId)->pluck('subject')->toArray();
                $tutorId     = $tutorIds[array_rand($tutorIds)];
                $tutorPayout = DB::table('tutor_student_assignments')
                    ->where('tutor_id', $tutorId)->where('student_id', $studentId)
                    ->value('hourly_payout') ?? 50;

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
                    $subject    = $subjects ? $subjects[array_rand($subjects)] : $faker->randomElement(self::SUBJECTS);
                    $duration   = $faker->randomElement([30, 60, 90, 120]);
                    $daysOffset = rand(-180, 30);
                    $date       = now()->addDays($daysOffset)->format('Y-m-d');
                    $hour       = rand(9, 19);
                    $minute     = $faker->randomElement(['00', '30']);
                    $startTime  = sprintf('%02d:%s:00', $hour, $minute);

                    $isPast = $daysOffset < 0;

                    $status = match (true) {
                        $isPast && rand(1, 10) <= 7 => 'Completed',
                        $isPast && rand(1, 10) <= 9 => 'Cancelled',
                        $isPast                     => 'Scheduled',
                        default                     => 'Scheduled',
                    };

                    $sessionId = DB::table('tutoring_sessions')->insertGetId([
                        'tutor_id'      => $tutorId,
                        'student_id'    => $studentId,
                        'subject'       => $subject,
                        'date'          => $date,
                        'start_time'    => $startTime,
                        'duration'      => $duration,
                        'location'      => $faker->boolean(70) ? 'Online' : $faker->city(),
                        'is_initial'    => ($s === 0),
                        'recurs_weekly' => $faker->boolean(30),
                        'status'        => $status,
                        'tutor_rate'    => $ratePerCredit,
                        'tutor_notes'   => $status === 'Completed' ? $faker->paragraph(2) : null,
                        'created_at'    => now()->addDays($daysOffset)->subDays(3),
                        'updated_at'    => now(),
                    ]);

                    $totalSessions++;

                    if ($status === 'Completed') {
                        $creditsSpent = Timesheet::calculateCredits($duration);
                        $payout       = $creditsSpent * $tutorPayout;

                        DB::table('timesheets')->insert([
                            'tutoring_session_id' => $sessionId,
                            'tutor_id'            => $tutorId,
                            'billed_user_id'      => $customerId,
                            'credits_spent'       => $creditsSpent,
                            'tutor_payout'        => $payout,
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

    // ── Seed credit purchases ─────────────────────────────────────

    private function seedCreditPurchases(array $customerStudentMap): void
    {
        $this->line('<fg=yellow>→ Seeding credit purchases…</>');

        $faker = \Faker\Factory::create();
        $total = 0;

        foreach (array_keys($customerStudentMap) as $customerId) {
            $credit = DB::table('credits')->where('user_id', $customerId)->first();
            $rate   = (float)($credit?->dollar_cost_per_credit ?? 65);

            $count = rand(2, 5);
            for ($i = 0; $i < $count; $i++) {
                $creditsBought = $faker->randomElement([5, 10, 15, 20]);
                $daysAgo       = rand(10, 540);

                DB::table('credit_purchases')->insert([
                    'user_id'           => $customerId,
                    'credits_purchased' => $creditsBought,
                    'total_paid'        => round($creditsBought * $rate, 2),
                    'stripe_session_id' => 'fake_' . Str::random(24),
                    'type'              => 'stripe',
                    'created_at'        => now()->subDays($daysAgo),
                    'updated_at'        => now()->subDays($daysAgo),
                ]);

                $total++;
            }
        }

        $this->line("  <fg=green>✓ {$total} credit purchases</>");
    }

    // ── Seed notifications ────────────────────────────────────────

    private function seedNotifications(array $customerStudentMap): void
    {
        $this->line('<fg=yellow>→ Seeding notifications…</>');

        $faker   = \Faker\Factory::create();
        $adminId = DB::table('users')->where('role', 'admin')->value('id');

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

            $students = DB::table('users')
                ->leftJoin('student_profiles', 'student_profiles.user_id', '=', 'users.id')
                ->where('users.parent_id', $customerId)
                ->select('users.id', 'users.first_name', 'users.last_name', 'student_profiles.student_grade', 'student_profiles.student_school')
                ->get();

            DB::table('notifications')->insert([
                'id'              => Str::uuid(),
                'type'            => \App\Notifications\NewClientRegistered::class,
                'notifiable_type' => \App\Models\User::class,
                'notifiable_id'   => $adminId,
                'data'            => json_encode([
                    'type'           => 'new_client_registered',
                    'parent_id'      => $customer->id,
                    'parent_name'    => trim($customer->first_name . ' ' . $customer->last_name),
                    'student_name'   => $students->first()?->first_name . ' ' . $students->first()?->last_name,
                    'student_grade'  => $students->first()?->student_grade ?? null,
                    'student_school' => $students->first()?->student_school ?? null,
                    'message'        => 'A new client registered on the portal.',
                ]),
                'read_at'    => $faker->boolean(60) ? now()->subDays(rand(1, 30)) : null,
                'created_at' => now()->subDays(rand(30, 365)),
                'updated_at' => now()->subDays(rand(1, 29)),
            ]);
            $total++;

            $tutorId = DB::table('tutor_student_assignments')
                ->join('users', 'tutor_student_assignments.student_id', '=', 'users.id')
                ->where('users.parent_id', $customerId)
                ->value('tutor_student_assignments.tutor_id');

            if ($tutorId) {
                DB::table('notifications')->insert([
                    'id'              => Str::uuid(),
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
                    'read_at'    => $faker->boolean(50) ? now()->subDays(rand(1, 20)) : null,
                    'created_at' => now()->subDays(rand(1, 180)),
                    'updated_at' => now()->subDays(rand(0, 10)),
                ]);
                $total++;
            }

            $session = DB::table('tutoring_sessions')
                ->join('users', 'tutoring_sessions.student_id', '=', 'users.id')
                ->where('users.parent_id', $customerId)
                ->select('tutoring_sessions.*')
                ->first();

            if ($session) {
                DB::table('notifications')->insert([
                    'id'              => Str::uuid(),
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
                    'read_at'    => $faker->boolean(70) ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => now()->subDays(rand(1, 90)),
                    'updated_at' => now()->subDays(rand(0, 5)),
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
}
