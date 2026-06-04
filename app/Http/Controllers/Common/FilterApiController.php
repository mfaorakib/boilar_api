<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Resources\Common\FilterTreeResource;
use App\Models\Common\Filter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;

class FilterApiController extends Controller
{
    /**
     * Get all filters.
     *
     * @param $locale
     * @return AnonymousResourceCollection
     */
    public function getAllFilters($locale): AnonymousResourceCollection
    {
        App::setLocale($locale);

        $filters = Filter::with(['translations', 'children' => function ($query) {
            $query->with('translations')
                ->defaultOrder()
                ->where('status', '=', 'active');
        }])
            ->where('status', '=', 'active')
            ->whereIsRoot()->defaultOrder()->get();

        return FilterTreeResource::collection($filters);
    }
}
