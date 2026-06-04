<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Setting extends Model implements HasMedia
{
    use LogsActivity, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'category', 'label', 'value', 'status', 'type'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    /**
     * Register media collection name.
     *
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('favicon')
            ->singleFile();
        $this->addMediaCollection('logo')
            ->singleFile();
        $this->addMediaCollection('small_logo')
            ->singleFile();
        $this->addMediaCollection('ga_credential')
            ->singleFile();
    }
}
