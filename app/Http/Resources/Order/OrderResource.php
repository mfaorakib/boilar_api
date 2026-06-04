<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\User\CustomerAuthResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class OrderResource extends JsonResource
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
            'order_no' => $this->order_no,
            'customer' => $this->customer ? new CustomerAuthResource($this->customer) : null,
            'invoice_no' => $this->invoice_no,
            'total_amount' => $this->total_amount,
            'total_quantity' => $this->total_quantity,
            'order_status' => $this->orderStatus ? new StatusResource($this->orderStatus) : null,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->paymentStatus ? new StatusResource($this->paymentStatus) : null,
            'payment_type' => $this->payment_type,
            'showroom_id' => $this->showroom_id,
            'delivery_mobile' => $this->delivery_mobile,
            'delivery_email' => $this->delivery_email,
            'created_at' => $this->created_at->toDayDateTimeString(),
        ];
    }

}
