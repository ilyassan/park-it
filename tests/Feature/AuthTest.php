<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    // use RefreshDatabase;

    /** @test */
    public function a_user_can_signup_with_valid_data()
    {
        $response = $this->postJson('/api/auth/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'created_at',
                         'updated_at',
                     ],
                     'token',
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function a_user_cannot_signup_with_missing_fields()
    {
        $response = $this->postJson('/api/auth/signup', [
            'name' => '',
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(401)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function a_user_cannot_signup_with_invalid_email()
    {
        $response = $this->postJson('/api/auth/signup', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function a_user_cannot_signup_with_an_existing_email()
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->postJson('/api/auth/signup', [
            'name' => 'John Doe',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function a_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'created_at',
                         'updated_at',
                     ],
                     'token',
                 ]);
    }

    /** @test */
    public function a_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'invalid@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                    'status' => false,
                    'message' => 'Email or Password does not match with our record.'
                ]);
    }

    /** @test */
    public function a_user_cannot_login_with_missing_fields()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(401)
                 ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function a_user_cannot_login_with_invalid_email_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
                 ->assertJsonValidationErrors(['email']);
    }
}