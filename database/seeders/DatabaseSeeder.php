<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        \Schema::disableForeignKeyConstraints();
        \DB::table('tutor_student_assignments')->truncate();
        TutoringSession::truncate();
        CreditPurchase::truncate();
        Credit::truncate();
        User::truncate();
        \Schema::enableForeignKeyConstraints();

        // ── 1. Admins: create fixed admin accounts
        $admins = [
            ['first_name' => 'Valery', 'last_name' => 'Krukov', 'email' => 'valery.v.krukov@gmail.com'],
            ['first_name' => 'Sofi', 'last_name' => 'Admin', 'email' => 'sofi@smartcookie.com'],
        ];

        foreach ($admins as $admin) {
            User::factory()->admin()->create(array_merge($admin, [
                'email_verified_at' => now(),
            ]));
        }

        // ── 2. Tutors: generate tutor accounts
        $tutors = User::factory()->count(8)->tutor()->create();

        // ── 3. Customers: generate customer accounts with credits and purchase history
        $customers = User::factory()->count(14)->customer()->create();

        $customers->each(function (User $customer) use ($faker) {
            Credit::create([
                'user_id' => $customer->id,
                'credit_balance' => $faker->randomFloat(2, 0, 800),
                'dollar_cost_per_credit' => $faker->randomFloat(2, 0.50, 5),
            ]);

            foreach (range(1, rand(1, 3)) as $index) {
                $amount = $faker->randomFloat(2, 20, 250);

                CreditPurchase::create([
                    'user_id' => $customer->id,
                    'amount' => $amount,
                    'total_paid' => $amount,
                ]);
            }
        });

        // ── 4. Students: generate students linked to customers and assign tutors
        $students = $customers->flatMap(function (User $customer) use ($faker) {
            return User::factory()
                ->count(rand(1, 3))
                ->student()
                ->create([
                    'parent_id' => $customer->id,
                    'email_verified_at' => now(),
                ]);
        });

        $students->each(function (User $student) use ($tutors, $faker) {
            $tutor = $tutors->random();

            $tutor->assignedStudents()->syncWithoutDetaching($student->id, [
                'hourly_payout' => $faker->randomFloat(2, 25, 50),
            ]);
        });

        // ── 5. Sessions: generate tutoring sessions for each tutor
        $subjects = ['Math', 'English', 'Science', 'History', 'Physics', 'Chemistry'];

        $sessions = collect();

        $tutors->each(function (User $tutor) use ($faker, $students, $subjects, $sessions) {
            $count = rand(5, 10);

            foreach (range(1, $count) as $i) {
                $student = $students->random();

                $session = TutoringSession::create([
                    'tutor_id' => $tutor->id,
                    'student_id' => $student->id,
                    'subject' => $faker->randomElement($subjects),
                    'date' => now()->subDays(rand(0, 90)),
                    'start_time' => sprintf('%02d:%02d:00', rand(8, 18), rand(0, 1) * 30),
                    'duration' => $faker->randomElement(['0:30', '1:00', '1:30', '2:00']),
                    'location' => $faker->optional()->streetAddress(),
                    'is_initial' => $faker->boolean(20),
                    'recurs_weekly' => $faker->boolean(10),
                    'status' => $faker->randomElement(['Scheduled', 'Completed', 'Canceled']),
                    'tutor_rate' => $faker->randomFloat(2, 25, 80),
                ]);

                $sessions->push($session);
            }
        });

        // ── 6. Timesheets: generate billing records for all sessions
        $sessions->each(function (TutoringSession $session) use ($faker) {
            Timesheet::create([
                'tutoring_session_id' => $session->id,
                'tutor_id' => $session->tutor_id,
                'parent_id' => $session->student->parent_id,
                'credits_spent' => Timesheet::calculateCredits($session->duration),
                'tutor_payout' => $faker->randomFloat(2, 20, 60),
                'period' => $session->date->format('Y-m'),
            ]);
        });

        // ── 7. Agreements: generate agreement documents
        $agreements = collect();

        foreach (range(1, 6) as $i) {
            $agreement = Agreement::create([
                'name' => 'Agreement ' . $i,
                'pdf_path' => 'agreements/agreement-' . $i . '.pdf',
            ]);

            $agreements->push($agreement);
        }

        // ── 8. Agreement requests: assign agreements to a random subset of users
        $usersForAgreements = $customers->concat($tutors)->shuffle();

        foreach ($usersForAgreements->take(20) as $user) {
            $isSigned = $faker->boolean(60);
            AgreementRequest::create([
                'agreement_id' => $agreements->random()->id,
                'user_id' => $user->id,
                'status' => $isSigned ? 'Signed' : 'Awaiting signature',
                'signed_full_name' => $isSigned ? $user->first_name . ' ' . $user->last_name : null,
                'signed_date_manual' => $isSigned ? now()->subDays(rand(0, 30))->toDateString() : null,
                'signed_at' => $isSigned ? now()->subDays(rand(0, 30)) : null,
            ]);
        }
    }
}
