<?php

namespace App\Http\Resources\Shipping;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingProviderEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'has_api' => $this->has_api,
            'code' => $this->code,
            'status' => $this->status,
            'package_option' => $this->package_option,
            'delivery_option' => $this->delivery_option,
        ];
    }
}
