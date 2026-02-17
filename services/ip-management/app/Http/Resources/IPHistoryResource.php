<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class IPHistoryResource
 *
 * Transforms IPHistory model data into API response format.
 * Ensures consistent history data structure across all API endpoints.
 */
class IPHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ip_address_id' => $this->ip_address_id,
            'modified_by' => $this->modified_by,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'action' => $this->action,
            'created_at' => $this->created_at,
        ];
    }
}
