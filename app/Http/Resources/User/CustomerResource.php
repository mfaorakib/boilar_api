<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Child\ChildResource;
use Illuminate\Support\Facades\Gate;

class CustomerResource extends JsonResource
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
            'created_at' => $this->created_at->toDayDateTimeString(),
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'status' => $this->status,
            'avatar' => $this->avatar,
            'current_latitude' => $this->current_latitude,
            'current_longitude' => $this->current_longitude,
            'current_location_name' => $this->current_location_name,
            'childrens' => ChildResource::collection($this->children)->first(),
            'total_order' => count($this->order)?:0,
            'total_order_amount' => $this->getTotalOrderAmount($this->order)
        ];
    }

    /**
     * @param $orders
     * @return float
     */
    private function getTotalOrderAmount($orders): float
    {
        $total_amount = 0;

        foreach ($orders as $order) {
           if(isset($order) && $order['total_amount']) {
                $total_amount = $total_amount+$order['total_amount'];
           }
        }
        return $total_amount?:0;
    }
}
