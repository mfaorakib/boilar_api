<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\User\CustomerAuthResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'subtotal' => 0,
            'total' => 0,
            'totalItems' => 0,
            'discount' => 0,
            'products' => CartProductResource::collection($this->products)
        ];
    }
}
