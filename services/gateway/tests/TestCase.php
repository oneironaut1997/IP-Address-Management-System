<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Base Test Case for Gateway Service Tests
 *
 * Provides common functionality for all tests including JWT token generation.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Generate a valid JWT token for testing.
     *
     * @param array $userAttributes User attributes to override (role is not stored in DB)
     * @param array $additionalClaims Additional JWT claims
     * @return string The generated JWT token
     */
    protected function generateValidToken(array $userAttributes = [], array $additionalClaims = []): string
    {
        // Create a user in the database (role is stored in JWT claims, not DB)
        $user = User::factory()->create([
            'name' => $userAttributes['name'] ?? 'Test User',
            'email' => $userAttributes['email'] ?? 'test@example.com',
        ]);

        // Get role from attributes or use default
        $role = $userAttributes['role'] ?? 'regular';

        // Build the claims - role is in the JWT token
        $claims = array_merge([
            'sub' => $user->id,
            'role' => $role,
            'email' => $user->email,
            'iat' => now()->timestamp,
            'exp' => now()->addHour()->timestamp,
            'nbf' => now()->timestamp,
            'jti' => bin2hex(random_bytes(16)),
        ], $additionalClaims);

        return JWTAuth::fromUser($user, $claims);
    }

    /**
     * Get authentication headers with a valid JWT token.
     *
     * @param array $userAttributes User attributes for token generation
     * @return array The headers including Authorization
     */
    protected function withAuthHeaders(array $userAttributes = []): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->generateValidToken($userAttributes),
        ];
    }

    /**
     * Create a test user and generate a token for them.
     *
     * @param array $userAttributes User attributes
     * @return string The JWT token
     */
    protected function createAuthenticatedUser(array $userAttributes = []): string
    {
        return $this->generateValidToken(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ], $userAttributes));
    }

    /**
     * Generate a token for a specific user.
     *
     * @param User $user The user to generate a token for
     * @param array $additionalClaims Additional JWT claims
     * @return string The generated JWT token
     */
    protected function tokenForUser(User $user, array $additionalClaims = []): string
    {
        $claims = array_merge([
            'sub' => $user->id,
            'role' => $additionalClaims['role'] ?? 'regular',
            'email' => $user->email,
            'iat' => now()->timestamp,
            'exp' => now()->addHour()->timestamp,
            'nbf' => now()->timestamp,
            'jti' => bin2hex(random_bytes(16)),
        ], $additionalClaims);

        return JWTAuth::fromUser($user, $claims);
    }
}
