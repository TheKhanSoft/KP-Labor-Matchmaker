<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
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
            'type' => 'workers',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'skill' => $this->skill_category,
                'district' => $this->district,
                'available' => (bool)$this->is_available,
            ]
        ];
    }
}
