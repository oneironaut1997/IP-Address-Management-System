<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ActivityLogResource
 *
 * Transforms Spatie Activity data into unified API response format.
 * Ensures consistent activity log data structure across all API endpoints.
 */
class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $properties = $this->properties ?? [];

        // Extract user_id from properties or causer
        $userId = $this->causer_id ?? $properties['causer_id'] ?? null;
        $metadata = [];

        // Flatten properties for metadata
        if (isset($properties['ip'])) {
            $metadata['ip'] = $properties['ip'];
        }
        if (isset($properties['old'])) {
            $metadata['old_values'] = $properties['old'];
        }
        if (isset($properties['new'])) {
            $metadata['new_values'] = $properties['new'];
        }
        if (isset($properties['causer_id'])) {
            $metadata['causer_id'] = $properties['causer_id'];
        }

        return [
            'id' => $this->id,
            'type' => 'ip',
            'event_type' => $this->event,
            'entity_type' => $this->extractEntityType($this->subject_type),
            'entity_id' => $this->subject_id,
            'user_id' => $userId,
            'metadata' => $metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Extract entity type from the subject_type class name.
     *
     * @param  string|null  $subjectType  The fully qualified class name
     * @return string The simplified entity type
     */
    protected function extractEntityType(?string $subjectType): string
    {
        if (empty($subjectType)) {
            return 'Unknown';
        }

        // Extract just the class name from the full namespace
        $parts = explode('\\', $subjectType);

        return end($parts);
    }
}
