<?php

namespace App\Http\Resources\Shipping;

use Illuminate\Http\Resources\Json\JsonResource;

class DivisionTreeResource extends JsonResource
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
            'id' => uniqid('division'),
            'name' => $this->name,
            'children' => DivisionChildTreeResource::collection($this->children)
        ];
    }
}
