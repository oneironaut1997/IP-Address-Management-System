<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AuthTokenResource
 *
 * Transforms authentication token data into API response format.
 * Used for login, register, and token refresh responses.
 */
class AuthTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this['access_token'],
            'refresh_token' => $this['refresh_token'],
            'token_type' => $this['token_type'] ?? 'bearer',
            'expires_in' => $this['expires_in'],
        ];
    }
}