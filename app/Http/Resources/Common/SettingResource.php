<?php

namespace App\Http\Resources\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'site_name' => $this['site_name'],
            'footer_text' => $this['footer_text'],
            'phone' => $this['phone'],
            'email' => $this['email'],
            'favicon' => $this['favicon'],
            'logo' => $this['logo'],
            'small_logo' => $this['small_logo'],
            // 'gr_v2' => $this['gr_v2'],
            // 'ga_id' => $this['ga_id'],
            // 'ga_view_id' => $this['ga_view_id'],
            // 'ga_api_key' => $this['ga_api_key'],
            // 'ga_credential' => $this['ga_credential'],
            'password_edit_enabled' => filter_var($this['password_edit_enabled'], FILTER_VALIDATE_BOOLEAN)
        ];
    }
}
