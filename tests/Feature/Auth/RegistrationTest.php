<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_customers_can_register_as_a_self_student(): void
    {
        $response = $this->post('/register', [
            'parent_name' => 'Test Parent User',
            'parent_email' => 'test-self-student@test-example.com',
            // ── Required parent fields
            'address' => '123 Main-test St',
            'phone' => '555-1234',
            'is_self_student' => true,

            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login', absolute: false));

        $this->assertDatabaseHas('users', [
            'role' => 'customer',
            'email' => 'test-self-student@test-example.com',
        ]);
    }

    public function test_new_customers_can_register_as_parent_with_student(): void
    {
        $response = $this->post('/register', [
            'parent_name' => 'Test Parent User',
            'parent_email' => 'test-parent@test-example.com',
            // ── Required parent fields
            'address' => '123 Main-test St',
            'phone' => '555-1234',
            'is_self_student' => false,
            // ── Student fields should be optional when registering as parent (not self-student)
            'student_name' => 'Test Student',
            'student_grade' => '5',
            'student_school' => 'Test School',
            'student_email' => 'test-student@test-example.com',

            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login', absolute: false));
        
        $this->assertDatabaseHas('users', [
            'role' => 'student',
            'email' => 'test-student@test-example.com',
        ]);
        
        $this->assertDatabaseHas('users', [
            'role' => 'customer',
            'email' => 'test-parent@test-example.com',
        ]);
        
    }
}