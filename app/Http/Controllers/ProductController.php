<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $query = Product::with('category')
            ->where('is_active', true);

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sort = $request->query('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('id'),
        };

        $products = $query->paginate($perPage)->withQueryString();

        return $this->paginatedSuccess(ProductResource::collection($products));
    }

    public function show(Product $product): JsonResponse
    {
        if (! $product->is_active) {
            return $this->error('Product not found.', 404);
        }

        $product->load('category');

        return $this->success(new ProductDetailResource($product));
    }
}
