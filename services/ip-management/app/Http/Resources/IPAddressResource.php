<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class IPAddressResource
 *
 * Transforms IPAddress model data into API response format.
 * Ensures consistent IP address data structure across all API endpoints.
 */
class IPAddressResource extends JsonResource
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
            'id' => $this->id,
            'user_id' => $this->user_id,
            'ip_address' => $this->ip_address,
            'label' => $this->label,
            'comment' => $this->comment,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'history' => IPHistoryResource::collection($this->whenLoaded('history')),
        ];
    }
}