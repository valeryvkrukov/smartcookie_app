<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Student, Client, Tutor, Credit, Agreement};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TutoringSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ── Admin: create default admin account
        User::create([
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@tutor.com', 'password' => Hash::make('password'),
            'role' => 'admin', 'is_admin' => true,
        ]);

        // ── Tutor: create default tutor account
        $tutor = User::create([
            'first_name' => 'John', 'last_name' => 'Tutor',
            'email' => 'tutor@tutor.com', 'password' => Hash::make('password'),
            'role' => 'tutor', 'blurb' => 'Expert in Mathematics and Physics with 5 years experience.',
        ]);

        // ── Customer: create default parent account
        $parent = User::create([
            'first_name' => 'Sarah', 'last_name' => 'Parent',
            'email' => 'parent@tutor.com', 'password' => Hash::make('password'),
            'role' => 'customer', 'address' => '123 Education St, NY', 'phone' => '555-0101',
        ]);

        // ── Credits: set initial credit balance and rate for the parent
        Credit::create([
            'user_id' => $parent->id,
            'credit_balance' => 10.0, // 10 credits for tests
            'dollar_cost_per_credit' => 45.00, // Admin set rate
        ]);

        // ── Students: create two students linked to the parent
        $student1 = User::create([
            'first_name' => 'Kevin', 'last_name' => 'Parent',
            'email' => 'kevin@school.com', 'password' => Hash::make(Str::random(16)),
            'parent_id' => $parent->id, 'role' => 'student',
            'student_grade' => '10th Grade', 'student_school' => 'Lincoln High',
        ]);

        $student2 = User::create([
            'first_name' => 'Lucy', 'last_name' => 'Parent',
            'email' => 'lucy@school.com', 'password' => Hash::make(Str::random(16)),
            'parent_id' => $parent->id, 'role' => 'student',
            'student_grade' => '8th Grade', 'student_school' => 'Lincoln Middle',
        ]);

        // ── Assignments: link tutor to both students
        \DB::table('tutor_student_assignments')->insert([
            ['tutor_id' => $tutor->id, 'student_id' => $student1->id, 'hourly_payout' => 30.00],
            ['tutor_id' => $tutor->id, 'student_id' => $student2->id, 'hourly_payout' => 25.00],
        ]);

        // ── Agreement: create a sample agreement document
        Agreement::create([
            'name' => 'General Tutoring Agreement 2026',
            'pdf_path' => 'agreements/test.pdf',
        ]);
    }
}
