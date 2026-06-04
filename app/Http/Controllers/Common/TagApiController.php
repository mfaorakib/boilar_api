<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Resources\Common\TagApiResource;
use App\Models\Common\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class TagApiController extends Controller
{
    public function getAllWithArticles($locale)
    {
        App::setLocale($locale);

        $tags = Tag::with('translations')
            ->has('articles')
            ->withCount('articles')
            ->where('status', '=', 'active')
            ->get();

        return TagApiResource::collection($tags);
    }

    public function getTagsByArticle($locale, $id)
    {
        App::setLocale($locale);

        $tags = Tag::with('translations')
            ->whereHas('articles', function (Builder $query) use ($id) {
                $query->where('taggable_id', '=', $id);
            })
            ->where('status', '=', 'active')
            ->get();

        return TagApiResource::collection($tags);
    }
}
