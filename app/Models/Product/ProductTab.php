<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class ProductTab extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'locale', 'title', 'content',
    ];

    /**
     * Set timestamps false.
     *
     * @var bool
     */
    public $timestamps = false;
}
