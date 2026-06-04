<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Http\Resources\Child\ChildResource;

class CustomerAuthResource extends JsonResource
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
            'email' => $this->email,
            'mobile' => $this->mobile,
            'avatar' => $this->avatar,
            'blood_group' => $this->blood_group,
            'gender' => $this->gender ?? 'Not Given',
            'createdAt' => $this->created_at,
            'parent_type' => $this->parent_type,
            'primary_language' => $this->primary_language,
            'religion' => $this->religion,
            'education' => $this->education,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->getAge($this->date_of_birth),
            'company' => $this->company?->name ?: '',
            'position' => $this->position,
            'profession' => $this->profession,
            'location' => $this->location?->name ?: '',
            'area_id' => $this->area_id,
            'shipping_email' => $this->shipping_email,
            'current_latitude' => $this->current_latitude,
            'current_longitude' => $this->current_longitude,
            'current_location_name' => $this->current_location_name,
            'childrens' => ChildResource::collection($this->children)
        ];
    }

    /**
     * @param $date_of_birth
     * @return string
     */
    private function getAge($date_of_birth): string
    {
        $years = Carbon::parse($date_of_birth)->age;
        $age_unite = $years > 1 ? 'years' : 'year';

        return "$years $age_unite";
    }
}
