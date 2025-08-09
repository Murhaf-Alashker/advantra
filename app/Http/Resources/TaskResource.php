<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'taskable_type' => class_basename($this->taskable_type),
            'taskable_id' => $this->taskable_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,



        ];
    }
}
