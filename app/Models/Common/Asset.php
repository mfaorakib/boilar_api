<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\Common\AssetCategory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model implements HasMedia
{
    use LogsActivity, Searchable, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'src', 'srcset', 'name', 'type'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    /**
     * Register media collection.
     * @param Media|null $media
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
//    public function registerMediaConversions(Media $media = null): void
//    {
//        $this->addMediaConversion('featured')
//            ->width(225)
//            ->height(227);
//    }
//    public function registerMediaCollections(): void
//    {
//        $this->addMediaCollection('images')
//            ->acceptsFile(function (File $file) {
//                return $file->mimeType === 'image/jpeg'
//                    || $file->mimeType === 'image/png'
//                    || $file->mimeType === 'image/gif'
//                    || $file->mimeType === 'image/svg+xml'
//                    || $file->mimeType === 'image/webp'
//                    || $file->mimeType === 'image/bmp';
//            });
//        $this->addMediaCollection('files')
//            ->acceptsFile(function (File $file) {
//                return $file->mimeType === 'text/csv'
//                    || $file->mimeType === 'application/octet-stream'
//                    || $file->mimeType === 'application/msword'
//                    || $file->mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
//                    || $file->mimeType === 'audio/mpeg'
//                    || $file->mimeType === 'video/mpeg'
//                    || $file->mimeType === 'audio/ogg'
//                    || $file->mimeType === 'video/ogg'
//                    || $file->mimeType === 'application/pdf'
//                    || $file->mimeType === 'application/vnd.ms-powerpoint'
//                    || $file->mimeType === 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
//                    || $file->mimeType === 'application/x-rar-compressed'
//                    || $file->mimeType === 'application/x-tar'
//                    || $file->mimeType === 'application/vnd.ms-excel'
//                    || $file->mimeType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
//                    || $file->mimeType === 'application/zip'
//                    || $file->mimeType === 'application/x-7z-compressed'
//                    || $file->mimeType === 'video/3gpp'
//                    || $file->mimeType === 'video/3gpp2'
//                    || $file->mimeType === 'audio/3gpp'
//                    || $file->mimeType === 'audio/3gpp2'
//                    || $file->mimeType === 'audio/wav'
//                    || $file->mimeType === 'audio/webm'
//                    || $file->mimeType === 'video/webm'
//                    || $file->mimeType === 'audio/amr'
//                    || $file->mimeType === 'audio/mp4'
//                    || $file->mimeType === 'video/mp4';
//            });
//    },

    /**
     * A asset has many categories.
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(AssetCategory::class, 'asset_category_asset');
    }
}
