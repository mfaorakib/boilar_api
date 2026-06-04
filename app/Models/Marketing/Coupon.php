<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Coupon extends Model
{
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'code', 'type', 'discount', 'area',
        'platforms', 'total_amount', 'start_date', 'end_date',
        'uses_per_coupon', 'uses_per_customer', 'status'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['start_date', 'end_date'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'area' => 'array',
        'platforms' => 'array',
    ];


}
