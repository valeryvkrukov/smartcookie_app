<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditPurchase;
use App\Models\TutoringSession;
use App\Models\Timesheet;
use App\Models\Agreement;
use App\Models\AgreementRequest;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // ── Wipe all tables in safe order
        \Schema::disableForeignKeyConstraints();
        \DB::table('agreement_requests')->truncate();
        \DB::table('subject_rates')->truncate();
        \DB::table('tutor_student_assignments')->truncate();
        Timesheet::truncate();
        TutoringSession::truncate();
        CreditPurchase::truncate();
        Credit::truncate();
        \DB::table('agreements')->truncate();
        User::truncate();
        \Schema::enableForeignKeyConstraints();

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 1. ADMINS (fixed accounts)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        foreach ([
            ['first_name' => 'Valery',  'last_name' => 'Krukov', 'email' => 'valery.v.krukov@gmail.com'],
            ['first_name' => 'Sofi',    'last_name' => 'Admin',  'email' => 'sofi@smartcookie.com'],
        ] as $data) {
            User::factory()->admin()->create(array_merge($data, [
                'password' => Hash::make('password'),
            ]));
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 2. TUTORS (3 fixed + 5 random)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        $subjects = ['Math', 'English', 'Science', 'History', 'Physics', 'Chemistry', 'Biology', 'French'];

        $fixedTutors = [
            ['first_name' => 'Ericka',  'last_name' => 'Mills',    'email' => 'mills.ericka@example.com',
             'blurb' => 'Specialises in Mathematics and Science. 8 years classroom experience.'],
            ['first_name' => 'Brody',   'last_name' => 'Von Rueden','email' => 'vonrueden.brody@example.com',
             'blurb' => 'English Literature & History tutor. Oxford graduate.'],
            ['first_name' => 'Hilma',   'last_name' => 'Mueller',  'email' => 'mueller.hilma@example.com',
             'blurb' => 'Physics and Chemistry specialist. Former university lecturer.'],
        ];

        $tutors = collect();

        foreach ($fixedTutors as $data) {
            $tutors->push(User::factory()->tutor()->create(array_merge($data, [
                'password' => Hash::make('password'),
            ])));
        }

        $tutors = $tutors->concat(User::factory()->count(5)->tutor()->create());

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 3. CUSTOMERS (3 fixed + 8 random)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        $fixedCustomers = [
            ['first_name' => 'Arvilla',   'last_name' => 'McLaughlin', 'email' => 'mclaughlin.arvilla@example.com'],
            ['first_name' => 'Cathrine',  'last_name' => 'Bartoletti', 'email' => 'bartoletti.cathrine@example.org'],
            ['first_name' => 'Dangelo',   'last_name' => 'Smith',      'email' => 'dangelo80@example.net'],
        ];

        $customers = collect();

        foreach ($fixedCustomers as $data) {
            $customers->push(User::factory()->customer()->create(array_merge($data, [
                'password' => Hash::make('password'),
            ])));
        }

        $customers = $customers->concat(User::factory()->count(8)->customer()->create());

        // Credits for every customer
        $customers->each(function (User $customer) use ($faker) {
            Credit::create([
                'user_id'                => $customer->id,
                'credit_balance'         => $faker->randomFloat(2, 5, 600),
                'dollar_cost_per_credit' => $faker->randomElement([35, 40, 45, 50, 55]),
            ]);

            foreach (range(1, rand(2, 5)) as $_) {
                $amount = $faker->randomFloat(2, 50, 400);
                CreditPurchase::create([
                    'user_id'   => $customer->id,
                    'amount'    => $amount,
                    'total_paid'=> $amount,
                    'type'      => 'stripe',
                    'stripe_session_id' => 'sess_fake_' . \Illuminate\Support\Str::random(16),
                ]);
            }
        });

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 4. STUDENTS (1–3 per customer) + subject rates
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        $students = $customers->flatMap(function (User $customer) use ($faker) {
            return User::factory()
                ->count(rand(1, 3))
                ->student()
                ->create([
                    'parent_id'         => $customer->id,
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);
        });

        // Assign each student to 1–2 tutors + give them subject rates
        $students->each(function (User $student) use ($tutors, $subjects, $faker) {
            $assignedTutors = $tutors->random(rand(1, 2));

            foreach ($assignedTutors as $tutor) {
                \DB::table('tutor_student_assignments')->insertOrIgnore([[
                    'tutor_id'      => $tutor->id,
                    'student_id'    => $student->id,
                    'hourly_payout' => $faker->randomFloat(2, 25, 55),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]]);
            }

            // 1–3 subjects with hourly rates
            $studentSubjects = $faker->randomElements($subjects, rand(1, 3));
            foreach ($studentSubjects as $subject) {
                \DB::table('subject_rates')->insert([
                    'student_id' => $student->id,
                    'subject'    => $subject,
                    'rate'       => $faker->randomFloat(2, 40, 90),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 5. TUTORING SESSIONS + TIMESHEETS
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        $statuses = ['Scheduled', 'Completed', 'Canceled'];
        $durations = ['0:30', '1:00', '1:30', '2:00'];

        $sessions = collect();

        $tutors->each(function (User $tutor) use ($faker, $students, $subjects, $durations, $statuses, $sessions) {
            $assignedStudentIds = \DB::table('tutor_student_assignments')
                ->where('tutor_id', $tutor->id)->pluck('student_id');

            if ($assignedStudentIds->isEmpty()) {
                return;
            }

            foreach (range(1, rand(8, 16)) as $_) {
                $studentId = $assignedStudentIds->random();
                $date = now()->subDays(rand(0, 120));
                $duration = $faker->randomElement($durations);
                $status = $faker->randomElement($statuses);

                $session = TutoringSession::create([
                    'tutor_id'      => $tutor->id,
                    'student_id'    => $studentId,
                    'subject'       => $faker->randomElement($subjects),
                    'date'          => $date->toDateString(),
                    'start_time'    => sprintf('%02d:%02d:00', rand(8, 18), rand(0, 1) * 30),
                    'duration'      => $duration,
                    'location'      => $faker->optional(0.6)->streetAddress(),
                    'is_initial'    => $faker->boolean(15),
                    'recurs_weekly' => $faker->boolean(20),
                    'status'        => $status,
                    'tutor_rate'    => $faker->randomFloat(2, 30, 80),
                    'tutor_notes'   => $status === 'Completed' ? $faker->optional(0.7)->sentence() : null,
                ]);

                $sessions->push($session);
            }
        });

        $sessions->where('status', 'Completed')->each(function (TutoringSession $session) use ($faker) {
            $student = User::find($session->student_id);
            if (!$student?->parent_id) {
                return;
            }

            Timesheet::create([
                'tutoring_session_id' => $session->id,
                'tutor_id'            => $session->tutor_id,
                'parent_id'           => $student->parent_id,
                'credits_spent'       => Timesheet::calculateCredits($session->duration),
                'tutor_payout'        => $faker->randomFloat(2, 20, 65),
                'period'              => \Carbon\Carbon::parse($session->date)->format('Y-m'),
            ]);
        });

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 6. AGREEMENTS + REQUESTS
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        $agreementNames = [
            'General Tutoring Agreement 2026',
            'NDIS Tutoring Services Agreement',
            'Media Consent & Photo Release Form',
            'Code of Conduct Agreement',
            'Emergency Contact & Medical Disclosure',
        ];

        $agreements = collect();
        foreach ($agreementNames as $i => $name) {
            $agreements->push(Agreement::create([
                'name'     => $name,
                'pdf_path' => 'agreements/agreement-' . ($i + 1) . '.pdf',
                'is_active'=> true,
            ]));
        }

        // Assign 2–4 agreements to each customer and most tutors
        $usersForAgreements = $customers->concat($tutors)->shuffle();

        $usersForAgreements->each(function (User $user) use ($agreements, $faker) {
            $selected = $agreements->random(rand(2, 4));

            foreach ($selected as $agreement) {
                $isSigned = $faker->boolean(65);
                $signedAt = $isSigned ? now()->subDays(rand(1, 60)) : null;

                AgreementRequest::create([
                    'agreement_id'       => $agreement->id,
                    'user_id'            => $user->id,
                    'status'             => $isSigned ? 'Signed' : 'Awaiting signature',
                    'signed_full_name'   => $isSigned ? $user->first_name . ' ' . $user->last_name : null,
                    'signed_date_manual' => $signedAt?->toDateString(),
                    'signed_at'          => $signedAt,
                    'pdf_filename'       => $isSigned ? basename($agreement->pdf_path) : null,
                    'ip_address'         => $isSigned ? $faker->ipv4() : null,
                ]);
            }
        });
    }
}

