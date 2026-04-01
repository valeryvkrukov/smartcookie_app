<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditPurchase;
use App\Models\TutoringSession;

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

        // --- 1. ADMINS ---
        $admins = [
            ['first_name' => 'Valery', 'last_name' => 'Krukov', 'email' => 'valery.v.krukov@gmail.com'],
            ['first_name' => 'Sofi', 'last_name' => 'Admin', 'email' => 'sofi@smartcookie.com'],
        ];

        foreach ($admins as $admin) {
            User::factory()->admin()->create(array_merge($admin, [
                'email_verified_at' => now(),
            ]));
        }

        // --- 2. TUTORS ---
        $tutors = User::factory()->count(8)->tutor()->create();

        // --- 3. CUSTOMERS ---
        $customers = User::factory()->count(14)->customer()->create();

        $customers->each(function (User $customer) use ($faker) {
            Credit::create([
                'user_id' => $customer->id,
                'credit_balance' => $faker->randomFloat(2, 0, 800),
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

        // --- 4. STUDENTS ---
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

        // --- 5. TUTORING SESSIONS ---
        $subjects = ['Math', 'English', 'Science', 'History', 'Physics', 'Chemistry'];

        foreach (range(1, 30) as $i) {
            $tutor = $tutors->random();
            $student = $students->random();

            TutoringSession::create([
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
        }
    }
}
