<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Class LoginTest
 *
 * Feature tests for user login endpoint.
 *
 * @package Tests\Feature\Auth
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login with valid credentials.
     *
     * @return void
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'regular',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'email',
                        'role',
                    ],
                ],
            ]);
    }

    /**
     * Test login fails with invalid password.
     *
     * @return void
     */
    public function test_login_fails_with_invalid_password(): void
    {
        User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'regular',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                ],
            ]);
    }

    /**
     * Test login fails with non-existent email.
     *
     * @return void
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                ],
            ]);
    }

    /**
     * Test login validation requires email.
     *
     * @return void
     */
    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test login validation requires password.
     *
     * @return void
     */
    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test login creates user session record.
     *
     * @return void
     */
    public function test_login_creates_user_session(): void
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'regular',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test super admin user can login.
     *
     * @return void
     */
    public function test_super_admin_can_login(): void
    {
        $user = User::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'super_admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'AdminPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.role', 'super_admin');
    }
}
