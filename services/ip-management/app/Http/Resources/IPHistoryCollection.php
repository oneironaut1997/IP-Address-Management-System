<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class IPHistoryCollection
 *
 * Transforms a collection of IP history records into API response format.
 * Includes metadata for pagination and total counts.
 */
class IPHistoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => IPHistoryResource::collection($this->collection),
        ];
    }
}