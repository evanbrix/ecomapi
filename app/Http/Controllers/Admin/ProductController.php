<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $products = Product::with('category')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedSuccess(ProductResource::collection($products));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);

        $uploaded = [];
        $data['image'] = $this->trackUpload(
            $request->file('image')->store('products', 'public'),
            $uploaded
        );
        $data['gallery'] = $this->storeGallery($request, $uploaded);

        try {
            $product = DB::transaction(fn () => Product::create($data));
        } catch (Throwable $e) {
            $this->cleanup($uploaded);
            throw $e;
        }

        $product->load('category');

        return $this->success(new ProductDetailResource($product), 'Product created.', 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('category');

        return $this->success(new ProductDetailResource($product));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $uploaded = [];
        $filesToRemoveOnCommit = [];

        if ($request->hasFile('image')) {
            $data['image'] = $this->trackUpload(
                $request->file('image')->store('products', 'public'),
                $uploaded
            );
            if ($product->image) {
                $filesToRemoveOnCommit[] = $product->image;
            }
        }

        if ($request->hasFile('gallery')) {
            $data['gallery'] = $this->storeGallery($request, $uploaded);
            foreach ((array) $product->gallery as $old) {
                $filesToRemoveOnCommit[] = $old;
            }
        }

        try {
            DB::transaction(fn () => $product->update($data));
        } catch (Throwable $e) {
            $this->cleanup($uploaded);
            throw $e;
        }

        $this->cleanup($filesToRemoveOnCommit);

        $product->load('category');

        return $this->success(new ProductDetailResource($product->fresh('category')), 'Product updated.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $files = array_merge(
            array_filter([$product->image]),
            (array) $product->gallery
        );

        DB::transaction(fn () => $product->delete());

        $this->cleanup($files);

        return $this->success(null, 'Product deleted.');
    }

    private function storeGallery(Request $request, array &$uploaded): array
    {
        if (! $request->hasFile('gallery')) {
            return [];
        }

        $paths = [];
        foreach ((array) $request->file('gallery') as $file) {
            $paths[] = $this->trackUpload($file->store('products/gallery', 'public'), $uploaded);
        }

        return $paths;
    }

    private function trackUpload(string $path, array &$uploaded): string
    {
        $uploaded[] = $path;

        return $path;
    }

    private function cleanup(array $paths): void
    {
        foreach ($paths as $p) {
            Storage::disk('public')->delete($p);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
