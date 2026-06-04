<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class MessageCode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mobile', 'code', 'created_at'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at'];

    /**
     * Set timestamps false.
     *
     * @var bool
     */
    public $timestamps = false;
}
