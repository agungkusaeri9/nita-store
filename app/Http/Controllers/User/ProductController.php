<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\VariantType;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use App\Services\User\StorefrontService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService,
        protected ProductReviewRepositoryInterface $reviewRepository
    ) {}

    /**
     * Display the public product catalog page with comprehensive filters.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $category = trim((string) $request->input('category', ''));
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $inStock = filter_var($request->input('in_stock', false), FILTER_VALIDATE_BOOLEAN);
        $minRating = $request->input('min_rating');
        $variantValues = array_values(array_filter(array_map('intval', (array) $request->input('variant_values', []))));
        $sort = (string) $request->input('sort', 'latest');
        $perPage = (int) $request->input('per_page', 21);

        if (! in_array($perPage, [21, 42, 63], true)) {
            $perPage = 21;
        }

        $query = $this->storefrontService->applyProductFilters(
            $this->storefrontService->baseProductQuery(),
            [
                'search' => $search,
                'category' => $category,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'in_stock' => $inStock,
                'min_rating' => $minRating,
                'variant_values' => $variantValues,
                'sort' => $sort,
            ]
        );

        $products = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $product) => $this->storefrontService->formatProduct($product));

        $priceStats = Product::query()
            ->where('is_active', true)
            ->selectRaw('COALESCE(MIN(price), 0) as min_price, COALESCE(MAX(price), 0) as max_price')
            ->first();

        $variantTypes = VariantType::query()
            ->active()
            ->with('values:id,variant_type_id,value')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (VariantType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'values' => $type->values->map(fn ($value): array => [
                    'id' => $value->id,
                    'value' => $value->value,
                ])->values()->all(),
            ])
            ->filter(fn (array $type): bool => $type['values'] !== [])
            ->values()
            ->all();

        return Inertia::render('user/Products', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'categories' => $this->storefrontService->getActiveCategories(),
            'variantTypes' => $variantTypes,
            'priceBounds' => [
                'min' => (float) ($priceStats?->min_price ?? 0),
                'max' => (float) ($priceStats?->max_price ?? 0),
            ],
            'products' => $products,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'min_price' => $minPrice !== null && $minPrice !== '' ? (string) $minPrice : '',
                'max_price' => $maxPrice !== null && $maxPrice !== '' ? (string) $maxPrice : '',
                'in_stock' => $inStock,
                'min_rating' => $minRating !== null && $minRating !== '' ? (string) $minRating : '',
                'variant_values' => $variantValues,
                'sort' => $sort,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Display the public product detail page with gallery, description, and paginated reviews.
     */
    public function show(Request $request, string $slug): Response
    {
        $product = $this->storefrontService->baseProductQuery()
            ->where('slug', $slug)
            ->firstOrFail();

        $ratingFilter = $request->filled('rating') ? (int) $request->input('rating') : null;
        if ($ratingFilter !== null && ($ratingFilter < 1 || $ratingFilter > 5)) {
            $ratingFilter = null;
        }

        $ratingStats = $this->reviewRepository->getProductRatingStats($product->id);

        $reviews = $this->reviewRepository
            ->getPaginatedReviewsForProduct($product->id, 10, $ratingFilter, true)
            ->withQueryString()
            ->through(fn ($review): array => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'customer_name' => $review->customer?->user?->name ?? 'Pelanggan',
                'customer_avatar' => $review->customer?->avatar_url,
                'is_verified_purchase' => $review->order_id !== null,
                'created_at' => $review->created_at?->translatedFormat('d M Y'),
            ]);

        $relatedQuery = $this->storefrontService->baseProductQuery()
            ->where('id', '!=', $product->id);

        if ($product->category_id) {
            $relatedQuery->where('category_id', $product->category_id);
        }

        $relatedProducts = $relatedQuery
            ->orderByDesc('sold_count')
            ->orderByDesc('id')
            ->limit(4)
            ->get()
            ->map(fn (Product $item) => $this->storefrontService->formatProduct($item))
            ->values();

        return Inertia::render('user/ProductDetail', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'product' => $this->storefrontService->formatProduct($product),
            'ratingStats' => $ratingStats,
            'reviews' => $reviews,
            'reviewRatingFilter' => $ratingFilter ? (string) $ratingFilter : '',
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
