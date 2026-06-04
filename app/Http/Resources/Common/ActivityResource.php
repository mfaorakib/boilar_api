<?php

namespace App\Http\Resources\Common;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class ActivityResource extends JsonResource
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
            'subject_type' => $this->subject_type ? class_basename($this->subject_type) : '-',
            'subject_id' => $this->subject_id ? $this->subject_id : '-',
            'user_name' => $this->causer ? $this->causer->name: '-',
            'description' => $this->description,
            'created_at' => $this->created_at->toDayDateTimeString(),
            'updated_at' => $this->updated_at->toDayDateTimeString(),
            'user_id' => $this->causer ? $this->causer->id:'-',
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
            'delete' => Gate::allows('delete', $this->resource),
        ];
    }
}
