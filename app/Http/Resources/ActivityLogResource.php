<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id,
            'action_type' => $this->action_type,
            'description' => $this->description,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'old_data' => $this->old_data,
            'new_data' => $this->new_data,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
