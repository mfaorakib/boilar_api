<?php

namespace App\Models\Home;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MainSlider extends Model
{
    use Translatable, NodeTrait, LogsActivity, Searchable {
        Searchable::usesSoftDelete insteadof NodeTrait;
    }

    /**
     * Set the translated fields.
     *
     * @var array
     */
    public $translatedAttributes = [
        'title', 'subtitle',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status', 'url', 'link', 'type'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status']);
    }
}
