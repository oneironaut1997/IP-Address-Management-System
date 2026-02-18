<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * InteractsWithUserContext Trait
 *
 * Provides consistent methods for extracting user context from requests.
 * Uses Laravel's Auth guard which is populated by the auth:api middleware.
 *
 * This trait eliminates duplicate code across controllers for user extraction.
 */
trait InteractsWithUserContext
{
    /**
     * Extract user context from the request.
     *
     * Gets user from Laravel Auth guard (populated by auth:api middleware).
     *
     * @param  Request  $request  The HTTP request
     * @return array{user_id: string|null, role: string, email: string|null}
     */
    protected function getUserContext(Request $request): array
    {
        // Get user from Laravel Auth guard
        $authUser = \Illuminate\Support\Facades\Auth::user();
        if ($authUser) {
            return [
                'user_id' => $authUser->id,
                'role' => $authUser->role ?? 'regular',
                'email' => $authUser->email ?? null,
            ];
        }

        // Fallback to request->user() method
        $requestUser = $request->user();
        if ($requestUser) {
            return [
                'user_id' => $requestUser->id,
                'role' => $requestUser->role ?? 'regular',
                'email' => $requestUser->email ?? null,
            ];
        }

        // No authenticated user found
        return [
            'user_id' => null,
            'role' => 'regular',
            'email' => null,
        ];
    }

    /**
     * Require user to be authenticated.
     *
     * Returns unauthorized error if no user context is found.
     *
     * @param  Request  $request  The HTTP request
     * @return array{user_id: string, role: string, email: string|null}|null
     *                                                                       Returns user context or null if unauthorized
     */
    protected function requireUserContext(Request $request): ?array
    {
        $context = $this->getUserContext($request);

        if (empty($context['user_id'])) {
            return null;
        }

        return $context;
    }

    /**
     * Build unauthorized response.
     */
    protected function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'User not authenticated.',
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Build forbidden response.
     *
     * @param  string  $message  Custom error message
     */
    protected function forbiddenResponse(string $message = 'You do not have permission to perform this action.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => $message,
            ],
        ], Response::HTTP_FORBIDDEN);
    }

    /**
     * Build not found response.
     *
     * @param  string  $resource  Resource name
     */
    protected function notFoundResponse(string $resource = 'Resource'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => "{$resource} not found.",
            ],
        ], Response::HTTP_NOT_FOUND);
    }
}
