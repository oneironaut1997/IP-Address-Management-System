<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set the currently logged in user for the application.
     * This override automatically uses JWT authentication.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string|null  $guard
     * @return $this
     */
    public function actingAs($user, $guard = null)
    {
        // Generate JWT token for JWT guard
        if ($user instanceof User) {
            $token = auth('api')->fromUser($user);
            parent::actingAs($user, 'api')->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ]);
        } else {
            parent::actingAs($user, $guard);
        }

        return $this;
    }
}
