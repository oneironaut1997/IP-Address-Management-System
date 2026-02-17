<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class UnifiedAuditLogCollection
 *
 * Transforms a collection of unified audit logs into API response format.
 * Includes metadata for pagination and counts by source type.
 */
class UnifiedAuditLogCollection extends ResourceCollection
{
    /**
     * Additional meta data to include in the response.
     */
    protected array $meta;

    /**
     * Create a new resource collection.
     *
     * @param  mixed  $resource
     */
    public function __construct($resource, array $meta = [])
    {
        parent::__construct($resource);
        $this->meta = $meta;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => UnifiedAuditLogResource::collection($this->collection),
            'meta' => $this->meta,
        ];
    }
}
