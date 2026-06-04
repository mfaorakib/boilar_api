<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use Searchable;
}
