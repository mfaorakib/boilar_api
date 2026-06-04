<?php

namespace App\Http\Resources\Shipping;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class ShippingCostResource extends JsonResource
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
            'area' => $this->area ? $this->area->name : '-',
            'cost' => $this->cost,
            'cart_amount' => $this->cart_amount,
            'special_delivery_cost' => $this->special_delivery_cost,
            'area_id' => $this->area_id,
        ];
    }
}
