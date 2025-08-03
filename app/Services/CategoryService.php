<?php

namespace App\Services;

use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryService
{
    public function index()
    {
        return CategoryResource::collection(Category::paginate(10));
    }

    public function show(Category $category)
    {
        $category->load([
            'events' => fn ($query) => $query->where('status', '=', 'active')->eventWithRate()->limit(5),
            'guides' => fn ($query) => $query->where('status', '=', 'active')->guideWithRate()->limit(5),]);
        return new CategoryResource($category);
    }

    public function getAllCategoriesEvents()
    {
        return CategoryResource::collection(Category::with([
            'events' => fn ($query) => $query->eventWithRate()->limit(5)
        ])->get());
    }

    public function getAllCategoriesGuides()
    {
        return CategoryResource::collection(Category::with([
            'guides' => fn ($query) => $query->activeGuides()->limit(5)
        ])->get());
    }
}
