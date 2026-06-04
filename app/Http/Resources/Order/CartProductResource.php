<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\User\CustomerAuthResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class CartProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $special_price = now()->betweenIncluded($this->product->special_start_date, $this->product->special_end_date) ? (float)$this->product->special_price : 0;

        return [
            'id' => $this->product_id,
            'name' => $this->product ? $this->product->name : '',
            'slug' => $this->product ? $this->product->slug : '',
            'sku' => $this->product ? $this->product->sku : '',
            'price' => $this->product ? (float)$this->product->price : 0,
            'quantity' => $this->product ? $this->product->quantity : '',
            'min_quantity' => $this->product ? $this->product->min : '',
            'max_quantity' => $this->product ? $this->product->max : '',
            'weight' => $this->product ? $this->product->weight : '',
            'image' => $this->product ? $this->product->image : '',
            'special_price' => $this->product ? $special_price : 0,
            'qty' => $this->quantity,
        ];
    }
}
