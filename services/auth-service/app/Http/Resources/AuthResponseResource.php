<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AuthResponseResource
 *
 * Combines user and token data for authentication responses.
 * Used for login and register endpoints to provide complete auth data.
 */
class AuthResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this['user'] ?? null;
        $tokens = $this['tokens'] ?? null;

        $response = [
            'success' => true,
            'message' => $this['message'] ?? 'Authentication successful',
        ];

        // Add token data if present
        if ($tokens) {
            $response['data'] = array_merge(
                (new AuthTokenResource($tokens))->toArray($request),
                [
                    'user' => $user ? (new UserResource($user))->toArray($request) : null,
                ]
            );
        } else {
            // Registration response without tokens
            $response['data'] = [
                'user' => $user ? (new UserResource($user))->toArray($request) : null,
            ];
        }

        return $response;
    }
}
