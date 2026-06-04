<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class ProductApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $special_price = now()->betweenIncluded($this->special_start_date, $this->special_end_date) ? (float)$this->special_price : 0;
        return [
            'id' => $this->id,
            'datetime' => $this->datetime,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => (float)$this->price,
            'quantity' => $this->quantity,
            'is_in_stock' => $this->quantity? $this->quantity>0:false,
            'image' => $this->image,
            'special_price' => $special_price,
            'special_start_date' => $this->special_start_date,
            'special_end_date' => $this->special_end_date,
        ];
    }
}
