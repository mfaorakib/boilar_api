<?php

namespace App\Http\Resources\Shipping;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ShippingMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $provider = DB::table('shipping_provider_translations')
            ->where('shipping_provider_id', '=', $this->shipping_provider_id)
            ->where('locale', '=', app()->getLocale())
            ->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'status' => $this->status,
            '_lft' => $this->_lft,
            '_rgt' => $this->_rgt,
            'provider' => $provider ? $provider->name : '-'
        ];
    }
}
