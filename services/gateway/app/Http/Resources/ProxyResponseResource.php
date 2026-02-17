<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProxyResponseResource
 *
 * Transforms proxy responses into a consistent API format.
 * Used for standardizing responses from backend services.
 */
class ProxyResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // If the resource is already an array with success key, return as-is
        if (is_array($this->resource) && isset($this->resource['success'])) {
            return $this->resource;
        }

        // Standard response format
        return [
            'success' => true,
            'data' => $this->resource,
        ];
    }
}
