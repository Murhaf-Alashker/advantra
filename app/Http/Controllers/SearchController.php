<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Libraries\SearchManager;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function search(SearchRequest $request)
    {
        $validated = $request->validated();
        return (new SearchManager())
                ->whereType($validated['type'])
                ->whereContain($validated['search'])
                ->withRange($validated['min'], $validated['max'])
                ->search();
    }
}
