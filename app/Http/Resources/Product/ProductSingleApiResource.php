<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\Common\TagApiResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class ProductSingleApiResource extends JsonResource
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
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'is_in_stock' => $this->quantity? $this->quantity>0:false,
            'sold_quantity' => $this->sales_count,
            'min_quantity' => $this->min,
            'max_quantity' => $this->max,
            'weight' => $this->weight,
            'image' => $this->image,
            'special_price' => $special_price,
            'special_start_date' => $this->special_start_date,
            'special_end_date' => $this->special_end_date,
            'excerpt' => $this->excerpt,
            'images' => $this->images,
            'tabs' => $this->tabs,
            'tags' => $this->tags ? TagApiResource::collection($this->tags) : collect(),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_image' => $this->meta_image,
            'meta_keyword' => $this->meta_keyword,
            'warranty_note' => $this->warranty_note,
            'delivery_note' => $this->delivery_note,
            'payment_note' => $this->payment_note,
            'shortLink' => $this->shortLink,
        ];
    }
}
