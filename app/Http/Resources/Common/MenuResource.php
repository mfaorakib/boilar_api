<?php

namespace App\Http\Resources\Common;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class MenuResource extends JsonResource
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
            'title' => $this->title,
            'icon' => $this->icon,
            'link' => $this->link,
            'status' => $this->status,
            'roles' => $this->roles->pluck('id'),
            'children' => MenuChildrenResource::collection($this->whenLoaded('children')),
            'can' => $this->permissions()
        ];
    }

    /**
     * Return all permissions.
     *
     * @return array
     */
    public function permissions()
    {
        return [
            'view' => Gate::allows('view', $this->resource),
            'create' => Gate::allows('create', $this->resource),
            'update' => Gate::allows('update', $this->resource),
            'delete' => Gate::allows('delete', $this->resource),
        ];
    }
}
