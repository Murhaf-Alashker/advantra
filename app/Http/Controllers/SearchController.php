<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Libraries\SearchBuilder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function search(SearchRequest $request)
    {
        $validated = $request->validated();
        return (new SearchBuilder())
                ->whereType($validated['type'])
                ->whereContain($validated['search'])
                ->withRange($validated['min'], $validated['max'])
                ->search();
    }
}
