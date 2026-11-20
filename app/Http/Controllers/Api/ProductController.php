<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Product\IndexProductApiRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ProductReviewRepositoryInterface $reviewRepository
    ) {}

    /**
     * Display a paginated listing of products.
     */
    public function index(IndexProductApiRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 12);
        $search = $request->input('search');
        $status = $request->input('status', 'active');
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $category = $request->input('category_slug')
            ?? $request->input('category_id')
            ?? $request->input('category');

        $products = $this->productRepository->getPaginatedProducts(
            perPage: $perPage,
            search: $search,
            status: $status,
            sortColumn: $sort,
            sortDirection: $direction,
            category: $category,
        );

        return ApiResponse::paginate(
            paginator: $products,
            data: ProductResource::collection($products)->resolve($request),
            message: 'Berhasil mengambil daftar produk.',
        );
    }

    /**
     * Display the specified product.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->findById($id);

        if (! $product || ! $product->is_active) {
            return ApiResponse::error(
                message: 'Produk tidak ditemukan.',
                code: 404,
            );
        }

        $ratingStats = $this->reviewRepository->getProductRatingStats($product->id);

        $productData = ProductResource::make($product)->resolve($request);
        $productData['rating_stats'] = $ratingStats;

        return ApiResponse::success(
            data: $productData,
            message: 'Berhasil mengambil detail produk.',
        );
    }

    /**
     * Display the specified product by its slug.
     */
    public function showBySlug(string $slug, Request $request): JsonResponse
    {
        $product = $this->productRepository->findBySlug($slug);

        if (! $product || ! $product->is_active) {
            return ApiResponse::error(
                message: 'Produk tidak ditemukan.',
                code: 404,
            );
        }

        $ratingStats = $this->reviewRepository->getProductRatingStats($product->id);

        $productData = ProductResource::make($product)->resolve($request);
        $productData['rating_stats'] = $ratingStats;

        return ApiResponse::success(
            data: $productData,
            message: 'Berhasil mengambil detail produk.',
        );
    }

    /**
     * Display a listing of recommended products.
     */
    public function recommendations(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        $products = Product::query()
            ->where('is_active', true)
            ->with(['category', 'images', 'variantValues.variantType'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return ApiResponse::success(
            data: ProductResource::collection($products)->resolve($request),
            message: 'Berhasil mengambil produk rekomendasi.',
        );
    }

    /**
     * Display a listing of best seller products.
     */
    public function bestSellers(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        $products = Product::query()
            ->where('is_active', true)
            ->with(['category', 'images', 'variantValues.variantType'])
            ->withCount('reviews')
            ->orderBy('stock', 'asc') // or sold count / reviews
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        return ApiResponse::success(
            data: ProductResource::collection($products)->resolve($request),
            message: 'Berhasil mengambil produk terlaris.',
        );
    }
}
