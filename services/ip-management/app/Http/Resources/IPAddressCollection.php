<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class IPAddressCollection
 *
 * Transforms a collection of IP addresses into API response format.
 * Includes metadata for pagination and total counts.
 */
class IPAddressCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle paginated results
        if ($this->resource instanceof LengthAwarePaginator) {
            return [
                'success' => true,
                'data' => IPAddressResource::collection($this->collection),
                'meta' => [
                    'current_page' => $this->resource->currentPage(),
                    'per_page' => $this->resource->perPage(),
                    'total' => $this->resource->total(),
                    'last_page' => $this->resource->lastPage(),
                    'from' => $this->resource->firstItem(),
                    'to' => $this->resource->lastItem(),
                ],
                'links' => [
                    'first' => $this->resource->url(1),
                    'last' => $this->resource->url($this->resource->lastPage()),
                    'prev' => $this->resource->previousPageUrl(),
                    'next' => $this->resource->nextPageUrl(),
                ],
            ];
        }

        // Handle non-paginated collections
        return [
            'success' => true,
            'data' => IPAddressResource::collection($this->collection),
        ];
    }
}
