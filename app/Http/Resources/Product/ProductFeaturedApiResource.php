<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class ProductFeaturedApiResource extends JsonResource
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
            'sku' => $this->sku,
            'slug' => $this->slug,
            'type' => $this->type,
            'partNumber' => $this->model,
            'price' => $this->discount > 0 ? $this->selling_price - $this->discount : $this->selling_price,
            'compareAtPrice' => $this->discount > 0 ? $this->selling_price : null,
            'discount' => $this->discount,
            'additional_discount' => $this->additional_discount,
            'excerpt' => $this->excerpt,
            'description' => $this->excerpt,
            'stock' => 'in-stock',
            'ItemModelId' => $this->ItemModelId,
            'image' => $this->image,
            'images' => collect([$this->image]),
            'attributes' => collect([]),
            'badges' => $this->discount > 0 ? collect(['sale']) : collect([]),
        ];
    }
}
