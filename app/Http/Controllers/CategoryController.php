<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = new CategoryService();
    }
    public function index(){
       return $this->categoryService->index();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([

            'name' => 'required|string|unique:categories,name',
         
            'name_ar' => 'required|string',
        ]);
        $categoryData = collect($validated)->except('name_ar')->all();
        $category= $this->categoryService->store($categoryData);
        $category->translations()->create(['key' => 'category.name',
            'translation' => $validated['name_ar'],
        ]);

        return response()->json([new CategoryResource($category),201]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = request()->validate([

            'name' => 'nullable|string,unique:categories,name',

           
            'name_ar' => 'nullable|string',
        ]);
        $categoryData = collect($validated)->except('name_ar')->all();
         $category->update($categoryData);
        if($validated['name_ar']){
            $category->translations()->updateOrCreate(
                ['key' => 'category.name'],
                ['translation' => $validated['name_ar'],]
            );
        }
        return response()->json([new CategoryResource($category),201]);
    }
}
