<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class UnifiedActivityLogResource
 *
 * Transforms unified activity log data into a consistent API format.
 * Combines auth-service and ip-management activity logs.
 */
class UnifiedActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $log = $this->resource;

        // Determine the source type
        $type = $log['type'] ?? 'unknown';

        // Normalize the event field
        $event = $log['event_type'] ?? $log['event'] ?? 'unknown';

        // Normalize the user ID field
        $userId = $log['user_id'] ?? $log['causer_id'] ?? null;

        // Normalize the subject/entity field
        $subjectType = $log['entity_type'] ?? $log['subject_type'] ?? null;
        $subjectId = $log['entity_id'] ?? $log['subject_id'] ?? null;

        return [
            'id' => $log['id'] ?? null,
            'type' => $type,
            'event' => $event,
            'user_id' => $userId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $log['metadata'] ?? $log['properties'] ?? [],
            'created_at' => $log['created_at'] ?? null,
            'updated_at' => $log['updated_at'] ?? null,
        ];
    }
}
