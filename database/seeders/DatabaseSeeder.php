<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tutor;
use App\Models\Student;
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
        \Schema::disableForeignKeyConstraints();
        User::truncate();
        Credit::truncate();
        CreditPurchase::truncate();
        TutoringSession::truncate();

        // --- 1. ADMINS ---
        $admins = [
            ['first_name' => 'Valery', 'last_name' => 'Krukov', 'email' => 'valery.v.krukov@gmail.com'],
            ['first_name' => 'Sofi', 'last_name' => 'Admin', 'email' => 'sofi@smartcookie.com'],
        ];

        foreach ($admins as $a) {
            User::create(array_merge($a, [
                'password' => \Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]));
        }

        // --- 2. TUTORS (with different rates) ---
        $tutors = [];
        $tutorData = [
            ['first_name' => 'Alex', 'last_name' => 'Math-Pro', 'email' => 'alex@tutor.com'],
            ['first_name' => 'Maria', 'last_name' => 'English-Expert', 'email' => 'maria@tutor.com'],
        ];

        foreach ($tutorData as $t) {
            $tutors[] = Tutor::create(array_merge($t, [
                'password' => \Hash::make('password123'),
                'role' => 'tutor',
                'blurb' => fake()->paragraph(),
                'hourly_rate' => rand(30, 50), // Random hourly rate between $30 and $50
                'is_subscribed' => true,
            ]));
        }

        // --- 3. PARENTS AND CHILDREN (Families) ---
        $families = [
            [
                'parent' => ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'email' => 'sarah@parent.com'],
                'children' => [
                    ['first_name' => 'Leo', 'last_name' => 'Johnson', 'student_grade' => '5th Grade', 'blurb' => fake()->paragraph()],
                    ['first_name' => 'Emma', 'last_name' => 'Johnson', 'student_grade' => '8th Grade', 'blurb' => fake()->paragraph()],
                ],
                'balance' => 450.00,
                'payments' => [200.00, 250.00]
            ],
            [
                'parent' => ['first_name' => 'Michael', 'last_name' => 'Smith', 'email' => 'mike@parent.com'],
                'children' => [
                    ['first_name' => 'Chris', 'last_name' => 'Smith', 'student_grade' => '10th Grade', 'blurb' => fake()->paragraph(),],
                ],
                'balance' => 120.00,
                'payments' => [120.00]
            ]
        ];

        foreach ($families as $f) {
            $parent = User::create(array_merge($f['parent'], [
                'password' => \Hash::make('password123'),
                'role' => 'customer',
            ]));

            // Balance and payment history for the parent
            Credit::create(['user_id' => $parent->id, 'credit_balance' => $f['balance']]);
            foreach ($f['payments'] as $amt) {
                CreditPurchase::create([
                    'user_id' => $parent->id,
                    'amount' => $amt,
                    'total_paid' => $amt,
                    'type' => 'deposit'
                ]);
            }

            // Making students and assigning random tutors
            foreach ($f['children'] as $child) {
                $student = Student::create(array_merge($child, [
                    'parent_id' => $parent->id,
                    'role' => 'student',
                    'email' => strtolower($child['first_name'].'.'.$child['last_name'].'@smartcookie.local'),
                    'password' => \Hash::make('password123'),
                ]));
                $student->assignedTutors()->attach($tutors[array_rand($tutors)]->id, [
                    'hourly_payout' => rand(25, 40) // Random payout between $25 and $40
                ]);
            }
        }

        // --- 4. HISTORY OF SESSIONS (For Net Profit) ---
        // Creating several completed sessions to see "expenses" in Financials
        $subjects = ['Math', 'English', 'Science', 'History'];

        $allStudents = User::where('role', 'student')->get();
        $lastDateTime = null;
        
        foreach (($subjects * 5) as $subject) {
            $session = TutoringSession::create([
                'student_id' => $allStudents->random()->id,
                'tutor_id' => $tutors[array_rand($tutors)]->id,
                'subject' => array_rand(array_flip($subjects)),
                'date' => now()->subDays(2),
                'start_time' => random_int(8, 20) . ':00',
                'duration' => '1:' . array_rand(['00', '30']),
                'status' => 'completed',
                'tutor_rate' => rand(30, 50)
            ]);

        }

        \Schema::enableForeignKeyConstraints();
    }

}
