<?php

namespace Tests\Feature\Auth;

use App\Events\UserLoggedIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Class LoginTest
 *
 * Feature tests for user login endpoint.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Fake events to avoid Spatie Activity Log issues
        Event::fake([UserLoggedIn::class]);

        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'regular',
        ]);

        // Mock Redis for login
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('pipeline')->andReturnUsing(function ($callback) {
            $mockPipe = new class
            {
                public function sadd($key, $value)
                {
                    return 1;
                }

                public function expire($key, $ttl)
                {
                    return true;
                }
            };
            $callback($mockPipe);
        });

        $response = $this->postJson('/api/v1/auth/login', [
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
     */
    public function test_login_fails_with_invalid_password(): void
    {
        User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'regular',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
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
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
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
     */
    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test login validation requires password.
     */
    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test login creates user session record.
     */
    public function test_login_creates_user_session(): void
    {
        // Fake events to avoid Spatie Activity Log issues
        Event::fake([UserLoggedIn::class]);

        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'regular',
        ]);

        // Mock Redis for login
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('pipeline')->andReturnUsing(function ($callback) {
            $mockPipe = new class
            {
                public function sadd($key, $value)
                {
                    return 1;
                }

                public function expire($key, $ttl)
                {
                    return true;
                }
            };
            $callback($mockPipe);
        });

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test super admin user can login.
     */
    public function test_super_admin_can_login(): void
    {
        // Fake events to avoid Spatie Activity Log issues
        Event::fake([UserLoggedIn::class]);

        $user = User::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'super_admin',
        ]);

        // Mock Redis for login
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('pipeline')->andReturnUsing(function ($callback) {
            $mockPipe = new class
            {
                public function sadd($key, $value)
                {
                    return 1;
                }

                public function expire($key, $ttl)
                {
                    return true;
                }
            };
            $callback($mockPipe);
        });

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'AdminPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.role', 'super_admin');
    }
}
