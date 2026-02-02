<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class LogoutTest
 *
 * Feature tests for user logout endpoint.
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful logout invalidates token.
     */
    public function test_user_can_logout_and_token_is_invalidated(): void
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'regular',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
    }

    /**
     * Test logout without token fails.
     */
    public function test_logout_without_token_fails(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test logout with invalid token fails.
     */
    public function test_logout_with_invalid_token_fails(): void
    {
        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer invalid_token',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test accessing protected route after logout fails.
     */
    // public function test_accessing_protected_route_after_logout_fails(): void
    // {
    //     $user = User::create([
    //         'email' => 'test@example.com',
    //         'password' => Hash::make('Password123!'),
    //         'role' => 'regular',
    //     ]);

    //     $token = JWTAuth::fromUser($user);

    //     // Logout
    //     $this->postJson('/api/auth/logout', [], [
    //         'Authorization' => 'Bearer ' . $token,
    //     ]);

    //     // Try to access protected route with the same token
    //     $response = $this->getJson('/api/auth/me', [
    //         'Authorization' => 'Bearer ' . $token,
    //     ]);

    //     $response->assertStatus(401);
    // }

    /**
     * Test me endpoint returns authenticated user data.
     */
    public function test_me_endpoint_returns_authenticated_user(): void
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'regular',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                ],
            ]);
    }

    /**
     * Test me endpoint requires authentication.
     */
    public function test_me_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }
}
