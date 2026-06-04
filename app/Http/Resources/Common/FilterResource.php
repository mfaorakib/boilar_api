<?php

namespace App\Http\Resources\Common;

use App\Http\Resources\CategoryTreeChildResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class FilterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'children' => CategoryTreeChildResource::collection($this->children),
        ];
    }
}
