<?php

namespace App\Http\Resources\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class FileManagerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $type = explode('.', $this->url);
        $imageExtensions = ['jpg', 'jpeg', 'gif', 'png', 'bmp', 'svg', 'webp'];
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'image' => in_array(end($type), $imageExtensions)
        ];
    }
}
