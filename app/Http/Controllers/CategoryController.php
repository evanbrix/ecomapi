<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return $this->success(CategoryResource::collection($categories));
    }

    public function show(Category $category): JsonResponse
    {
        if (! $category->is_active) {
            return $this->error('Category not found.', 404);
        }

        $category->loadCount(['products' => fn ($q) => $q->where('is_active', true)]);

        return $this->success(new CategoryResource($category));
    }
}
