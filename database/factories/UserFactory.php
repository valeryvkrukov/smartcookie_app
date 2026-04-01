<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The model that the factory creates.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'time_zone' => $this->faker->randomElement(['UTC', 'Europe/Moscow', 'America/New_York', 'Europe/London']),
            'is_subscribed' => $this->faker->boolean(80),
            'role' => 'customer',
            'is_admin' => false,
            'parent_id' => null,
            'student_grade' => null,
            'student_school' => null,
            'tutoring_subject' => null,
            'tutoring_goals' => null,
            'photo' => null,
            'blurb' => null,
            'can_tutor' => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'is_admin' => true,
            'can_tutor' => false,
            'is_subscribed' => true,
        ]);
    }

    public function tutor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'tutor',
            'is_admin' => false,
            'can_tutor' => true,
            'is_subscribed' => $this->faker->boolean(90),
            'blurb' => $this->faker->paragraph(),
            'photo' => 'https://i.pravatar.cc/400?img=' . $this->faker->numberBetween(1, 70),
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'customer',
            'is_admin' => false,
            'can_tutor' => false,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'student',
            'is_admin' => false,
            'can_tutor' => false,
            'is_subscribed' => false,
            'student_grade' => $this->faker->randomElement(['3rd Grade', '4th Grade', '5th Grade', '6th Grade', '7th Grade', '8th Grade', '9th Grade', '10th Grade']),
            'student_school' => $this->faker->company() . ' School',
            'tutoring_goals' => $this->faker->sentence(),
            'tutoring_subject' => $this->faker->randomElement(['Math', 'English', 'Science', 'History', 'Physics', 'Chemistry']),
        ]);
    }
}
