<?php

namespace App\Http\Resources\Shipping;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class ShippingProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'hasApi' => $this->has_api,
            'code' => $this->code,
            'status' => $this->status,
            'packageOption' => $this->package_option,
            'deliveryOption' => $this->delivery_option,
        ];
    }
}
