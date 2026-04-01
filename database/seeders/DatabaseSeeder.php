<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditTransaction;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'valery.v.krukov@gmail.com'],
            [
                'first_name' => 'Valery',
                'last_name' => 'Krukov',
                'password' => \Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::factory(10)->create(['role' => 'tutor']);

        $parent = User::create([
            'first_name' => 'Sarah2',
            'last_name' => 'Parent2',
            'email' => 'sarah2@tutor.com',
            'password' => \Hash::make('password'),
            'role' => 'customer',
        ]);

        $credit = Credit::create([
            'user_id' => $parent->id,
            'credit_balance' => 500.00,
        ]);

        $transactions = [
            ['amount' => 250.00, 'type' => 'deposit', 'created_at' => now()->subDays(10)],
            ['amount' => 500.00, 'type' => 'deposit', 'created_at' => now()->subDays(5)],
            ['amount' => 100.00, 'type' => 'withdrawal', 'created_at' => now()->subDays(2)],
            ['amount' => 150.00, 'type' => 'deposit', 'created_at' => now()->subMinutes(30)],
        ];

        foreach ($transactions as $data) {
            CreditTransaction::create([
                'user_id' => $parent->id,
                'amount' => $data['amount'],
                'type' => $data['type'],
                'status' => 'completed',
                'created_at' => $data['created_at'],
            ]);
        }
    }
}
