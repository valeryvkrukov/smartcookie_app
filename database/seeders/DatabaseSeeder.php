<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditPurchase;

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

        // 1. Create Admin User
        $admin = \App\Models\User::updateOrCreate(
            ['email' => 'valery.v.krukov@gmail.com'],
            [
                'first_name' => 'Valery',
                'last_name' => 'Krukov',
                'password' => \Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // 2. Create Test Parent
        $parent = \App\Models\User::create([
            'first_name' => 'Sarah',
            'last_name' => 'Parent',
            'email' => 'parent@example.com',
            'password' => \Hash::make('password123'),
            'role' => 'customer',
        ]);

        // Balance (Credits)
        \App\Models\Credit::create([
            'user_id' => $parent->id,
            'credit_balance' => 350.00,
        ]);

        // 3. Тестовые покупки (Revenue)
        $purchases = [
            ['amount' => 100.00, 'total_paid' => 100.00],
            ['amount' => 250.00, 'total_paid' => 240.00], // For example, with a discount or fees
        ];

        foreach ($purchases as $p) {
            \App\Models\CreditPurchase::create([
                'user_id' => $parent->id,
                'amount' => $p['amount'],
                'total_paid' => $p['total_paid'],
            ]);
        }

        \Schema::enableForeignKeyConstraints();
    }

}
