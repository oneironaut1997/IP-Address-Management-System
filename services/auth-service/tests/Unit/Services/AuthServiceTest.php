<?php

namespace Tests\Unit\Services;

use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Models\User;
use App\Models\UserSession;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class AuthServiceTest
 *
 * Unit tests for AuthService business logic.
 */
class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /**
     * Test user registration creates a user with correct attributes.
     */
    public function test_register_creates_user_with_correct_attributes(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ];

        $user = $this->authService->register($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('regular', $user->role);
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    /**
     * Test login returns error for invalid credentials.
     */
    public function test_login_returns_error_for_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $request = Request::create('/api/auth/login', 'POST');
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ];

        $result = $this->authService->login($credentials, $request);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_CREDENTIALS', $result['error']);
        $this->assertNull($result['user']);
        $this->assertNull($result['tokens']);
    }

    /**
     * Test login creates session and fires event for valid credentials.
     */
    public function test_login_creates_session_and_fires_event_for_valid_credentials(): void
    {
        Event::fake([UserLoggedIn::class]);
        Redis::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $request = Request::create('/api/auth/login', 'POST');
        $request->headers->set('X-Forwarded-For', '192.168.1.1');
        $request->headers->set('User-Agent', 'TestAgent/1.0');

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ];

        $result = $this->authService->login($credentials, $request);

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertNotNull($result['tokens']);
        $this->assertArrayHasKey('access_token', $result['tokens']);
        $this->assertArrayHasKey('refresh_token', $result['tokens']);

        // Assert session was created
        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
        ]);

        // Assert event was fired
        Event::assertDispatched(UserLoggedIn::class);
    }

    /**
     * Test logout removes refresh tokens and fires event.
     */
    public function test_logout_removes_refresh_tokens_and_fires_event(): void
    {
        Event::fake([UserLoggedOut::class]);
        Redis::fake();

        $user = User::factory()->create();

        // Mock Redis keys
        Redis::shouldReceive('keys')
            ->with('refresh:*')
            ->andReturn(['refresh:test-jti']);

        Redis::shouldReceive('get')
            ->with('refresh:test-jti')
            ->andReturn($user->id);

        Redis::shouldReceive('del')
            ->with('refresh:test-jti')
            ->andReturn(1);

        $this->authService->logout($user, 'test-jti');

        Event::assertDispatched(UserLoggedOut::class);
    }

    /**
     * Test refresh token returns error for invalid token type.
     */
    public function test_refresh_token_returns_error_for_invalid_token_type(): void
    {
        $user = User::factory()->create();

        // Mock JWT payload with wrong type
        JWTAuth::shouldReceive('setToken')
            ->andReturnSelf();

        JWTAuth::shouldReceive('getPayload')
            ->andReturn(new class {
                public function get($key)
                {
                    return $key === 'type' ? 'access' : 'test-jti';
                }
            });

        $result = $this->authService->refreshToken('some-token');

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_TOKEN_TYPE', $result['error']['code']);
    }

    /**
     * Test refresh token returns error for expired token.
     */
    public function test_refresh_token_returns_error_for_expired_token(): void
    {
        JWTAuth::shouldReceive('setToken')
            ->andReturnSelf();

        JWTAuth::shouldReceive('getPayload')
            ->andReturn(new class {
                public function get($key)
                {
                    return match ($key) {
                        'type' => 'refresh',
                        'jti' => 'test-jti',
                    };
                }
            });

        Redis::shouldReceive('get')
            ->with('refresh:test-jti')
            ->andReturn(null);

        $result = $this->authService->refreshToken('some-token');

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_REFRESH_TOKEN', $result['error']['code']);
    }

    /**
     * Test get user profile returns correct data.
     */
    public function test_get_user_profile_returns_correct_data(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => 'super_admin',
        ]);

        $profile = $this->authService->getUserProfile($user);

        $this->assertEquals($user->id, $profile['id']);
        $this->assertEquals('test@example.com', $profile['email']);
        $this->assertEquals('super_admin', $profile['role']);
        $this->assertArrayHasKey('created_at', $profile);
        $this->assertArrayHasKey('updated_at', $profile);
    }

    /**
     * Test extract bearer token from valid header.
     */
    public function test_extract_bearer_token_from_valid_header(): void
    {
        $request = Request::create('/api/auth/refresh', 'POST');
        $request->headers->set('Authorization', 'Bearer test-token-string');

        $token = $this->authService->extractBearerToken($request);

        $this->assertEquals('test-token-string', $token);
    }

    /**
     * Test extract bearer token returns null for missing header.
     */
    public function test_extract_bearer_token_returns_null_for_missing_header(): void
    {
        $request = Request::create('/api/auth/refresh', 'POST');

        $token = $this->authService->extractBearerToken($request);

        $this->assertNull($token);
    }

    /**
     * Test extract bearer token returns null for invalid header format.
     */
    public function test_extract_bearer_token_returns_null_for_invalid_format(): void
    {
        $request = Request::create('/api/auth/refresh', 'POST');
        $request->headers->set('Authorization', 'Basic test-token-string');

        $token = $this->authService->extractBearerToken($request);

        $this->assertNull($token);
    }
}