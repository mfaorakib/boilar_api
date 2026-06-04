<?php

namespace App\Http\Resources\Common;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Common\AssetCategoryResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $asset_media = $this->getFileMedia($this->id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'src' => $this->src,
            'srcset' => $this->srcset,
            'type' => $this->type,
            'url' => $asset_media->getUrl(),
            'media_id' => $asset_media->id,
            'mime_type' => $asset_media->mime_type,
            'media_type' => Str::ucfirst(Str::plural(Str::beforeLast($asset_media->mime_type, '/'))),
            'size' => $asset_media->human_readable_size,
            'lazy' => $asset_media->responsiveImages()->getPlaceholderSvg(),
            'categories' => $this->categories,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }

    private function getFileMedia($asset_id)
    {
        return Media::query()->where('model_id', '=', $asset_id)->first();
    }
}
