<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductTranslation extends Model
{
    use Searchable, LogsActivity;

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'name' => $this->name,
            'excerpt' => $this->excerpt
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'name', 'slug', 'excerpt', 'warranty_note', 'delivery_note',
        'payment_note', 'meta_title', 'meta_description', 'meta_keyword'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'excerpt']);
    }

    /**
     * Set timestamps false.
     *
     * @var bool
     */
    public $timestamps = false;
}
