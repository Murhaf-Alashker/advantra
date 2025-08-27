<?php

namespace App\Services;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class CategoryService
{
    public function index()
    {
        if(Auth::guard('api-admin')->check()){
            return CategoryResource::collection(Category::paginate(10));
        }
        else{
            return CategoryResource::collection(Category::all());
        }
    }

    public function show(Category $category)
    {
        $category->load([
            'events' => fn ($query) => $query->where('status', '=', 'active')->eventWithRate()->limit(5),
            'guides' => fn ($query) => $query->where('status', '=', 'active')->guideWithRate()->limit(5),]);
        return new CategoryResource($category);
    }

    public function store(array $data)
    {
       $category =  Category::create($data);
        return $category;
    }

    public function update(array $data, Category $category)
    {

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
