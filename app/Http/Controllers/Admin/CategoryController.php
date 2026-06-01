<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return $this->success(CategoryResource::collection($categories));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);

        $uploaded = null;
        if ($request->hasFile('image')) {
            $uploaded = $request->file('image')->store('categories', 'public');
            $data['image'] = $uploaded;
        }

        try {
            $category = DB::transaction(fn () => Category::create($data));
        } catch (Throwable $e) {
            if ($uploaded) {
                Storage::disk('public')->delete($uploaded);
            }
            throw $e;
        }

        return $this->success(new CategoryResource($category), 'Category created.', 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->loadCount('products');

        return $this->success(new CategoryResource($category));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }

        $oldImage = $category->image;
        $uploaded = null;

        if ($request->hasFile('image')) {
            $uploaded = $request->file('image')->store('categories', 'public');
            $data['image'] = $uploaded;
        }

        try {
            DB::transaction(fn () => $category->update($data));
        } catch (Throwable $e) {
            if ($uploaded) {
                Storage::disk('public')->delete($uploaded);
            }
            throw $e;
        }

        if ($uploaded && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return $this->success(new CategoryResource($category->fresh()), 'Category updated.');
    }

    public function destroy(Category $category): JsonResponse
    {
        $files = array_filter([$category->image]);

        DB::transaction(fn () => $category->delete());

        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }

        return $this->success(null, 'Category deleted.');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Category::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
