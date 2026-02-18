<?php

namespace Tests\Feature\Auth;

use App\Events\UserLoggedIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class TokenRefreshTest
 *
 * Feature tests for token refresh endpoint.
 */
class TokenRefreshTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful token refresh.
     */
    public function test_user_can_refresh_token_with_valid_refresh_token(): void
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

        // Login to get tokens
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        // Mock Redis for refresh - return user ID for validation
        Redis::shouldReceive('get')->once()->andReturn($user->id);
        Redis::shouldReceive('del')->andReturn(1);
        Redis::shouldReceive('srem')->andReturn(1);

        // Refresh the token
        $response = $this->postJson('/api/auth/refresh', [], [
            'Authorization' => 'Bearer '.$refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Token refreshed successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                ],
            ]);

        // Verify new tokens are different
        $this->assertNotEquals(
            $refreshToken,
            $response->json('data.refresh_token')
        );
    }

    /**
     * Test token refresh with invalid token fails.
     */
    public function test_refresh_fails_with_invalid_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', [], [
            'Authorization' => 'Bearer invalid_token',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_REFRESH_FAILED',
                ],
            ]);
    }

    /**
     * Test token refresh without authorization header fails.
     */
    public function test_refresh_requires_authorization_header(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'REFRESH_TOKEN_REQUIRED',
                ],
            ]);
    }

    /**
     * Test access token cannot be used for refresh.
     */
    public function test_access_token_cannot_be_used_for_refresh(): void
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'regular',
        ]);

        $accessToken = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/auth/refresh', [], [
            'Authorization' => 'Bearer '.$accessToken,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN_TYPE',
                ],
            ]);
    }

    /**
     * Test old refresh token is invalidated after refresh.
     */
    public function test_old_refresh_token_is_invalidated_after_refresh(): void
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

        // Login to get tokens
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $oldRefreshToken = $loginResponse->json('data.refresh_token');

        // Mock Redis for first refresh - return user ID for validation
        Redis::shouldReceive('get')->once()->andReturn($user->id);
        Redis::shouldReceive('del')->andReturn(1);
        Redis::shouldReceive('srem')->andReturn(1);

        // Refresh the token
        $this->postJson('/api/auth/refresh', [], [
            'Authorization' => 'Bearer '.$oldRefreshToken,
        ]);

        // Mock Redis for second refresh - return null to simulate invalidated token
        Redis::shouldReceive('get')->once()->andReturn(null);

        // Try to use old refresh token again
        $response = $this->postJson('/api/auth/refresh', [], [
            'Authorization' => 'Bearer '.$oldRefreshToken,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REFRESH_TOKEN',
                ],
            ]);
    }
}
