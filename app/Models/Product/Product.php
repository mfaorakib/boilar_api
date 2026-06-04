<?php

namespace App\Models\Product;

use App\Models\Common\Filter;
use App\Models\Common\Tag;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia, Feedable
{
    use Translatable, SoftDeletes, LogsActivity, InteractsWithMedia;

    /**
     * Set the translated fields.
     * @var array
     */
    public $translatedAttributes = [
        'name', 'slug', 'excerpt', 'warranty_note', 'delivery_note',
        'payment_note', 'meta_title', 'meta_description', 'meta_keyword'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'status', 'approved_status', 'image', 'datetime', 'meta_image', 'is_featured',
        'sku', 'weight', 'width', 'height', 'quantity', 'sale_count', 'min', 'max', 'purchased_price',
        'price', 'special_price', 'special_start_date', 'special_end_date',
        'video_url', 'shortLink', 'previewLink'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'datetime',
        'special_start_date',
        'special_end_date'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'status' => 'boolean',
        'datetime' => 'datetime',
        'special_start_date' => 'datetime',
        'special_end_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Return published post
     * @param $query
     * @return mixed
     */
    public function scopePublished($query): mixed
    {
        return $query->where('status', '=', 1);
    }

    /**
     * Return published post
     * @param $query
     * @return mixed
     */
    public function scopeOrderByDate($query): mixed
    {
        return $query->orderBy('datetime', 'desc');
    }

    /**
     * Return date passed post
     * @param $query
     * @return mixed
     */
    public function scopeDatePassed($query): mixed
    {
        return $query->where('datetime', '<=', now());
    }

    /**
     * A product has many tabs.
     *
     * @return HasMany
     */
    public function tabs(): HasMany
    {
        return $this->hasMany(ProductTab::class);
    }

    /**
     * A product has many images.
     *
     * @return HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * A product has many categories.
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category_product');
    }

    /**
     * A product has many filters.
     *
     * @return BelongsToMany
     */
    public function filters(): BelongsToMany
    {
        return $this->belongsToMany(Filter::class, 'product_filter_product');
    }

    /**
     * Each product has many tags.
     *
     * @return MorphToMany
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }


    /**
     * @return BelongsToMany
     */
    public function categoryNames(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category_product')->with(['translations', 'ancestors', 'ancestors.translations']);
    }

    /**
     * Convert image in different sizes.
     *
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('products')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this
                    ->addMediaConversion('featured_one')
                    ->width(308)
                    ->height(173)
                    ->sharpen(10);
            });
    }

    /**
     * @return FeedItem
     */
    public function toFeedItem(): FeedItem
    {
        $url = $this->shortLink ?? config('helper.url') . '/products/' . $this->slug;
        $stock = (int)$this->quantity > 0 ? 'in stock' : 'out of stock';
        $price = (float)$this->price . ' BDT';
        $special_price = (float)$this->special_price . ' BDT';
        $has_offer = (float)$this->special_price > 0.00;
        $special_start_date = $this->special_start_date ? $this->special_start_date->toIso8601String() : '';
        $special_end_date = $this->special_end_date ? $this->special_end_date->toIso8601String() : '';
        $images = collect($this->images)->pluck('src');
        $categoryNames = collect($this->categoryNames)->pluck('name')->join(' > ');

        return FeedItem::create([
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'excerpt' => $this->excerpt,
            'url' => $url,
            'image' => $this->image,
            'brand' => 'Spark',
            'condition' => 'new',
            'availability' => $stock,
            'price' => $price,
            'updated' => $this->updated_at,
            'title' => 'Title',
            'summary' => 'Summary',
            'authorName' => 'Spark',
            'link' => 'feed/products',
            'special_price' => $special_price,
            'has_offer' => $has_offer,
            'special_start_date' => $special_start_date,
            'special_end_date' => $special_end_date,
            'images' => $images,
            'categoryNames' => $categoryNames,
            'android_app_name' => 'Spark App',
            'android_package' => 'com.togumogu',
            'android_url' => 'https://play.google.com/store/apps/details?id=com.togumogu',
        ]);
    }

    /**
     * @return Builder[]|Collection
     */
    public static function getFeedItems(): Collection|array
    {
        return self::with(['translations', 'images', 'categoryNames'])
            ->where('status', '=', 1)
            ->where('approved_status', '=', 'approved')
            ->where('datetime', '<=', now()->toDateTimeString())
            ->get();
    }
}
