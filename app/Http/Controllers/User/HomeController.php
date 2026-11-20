<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\User\StorefrontService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    /**
     * Display the public storefront home page.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $categorySlug = trim((string) $request->input('category', ''));
        $sort = (string) $request->input('sort', 'latest');

        $recommendedProducts = $this->storefrontService->baseProductQuery()
            ->orderByDesc('average_rating')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (Product $product) => $this->storefrontService->formatProduct($product))
            ->values();

        $bestSellerProducts = $this->storefrontService->baseProductQuery()
            ->orderByDesc('sold_count')
            ->orderByDesc('reviews_count')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (Product $product) => $this->storefrontService->formatProduct($product))
            ->values();

        $catalogQuery = $this->storefrontService->applyProductFilters(
            $this->storefrontService->baseProductQuery(),
            [
                'search' => $search,
                'category' => $categorySlug,
                'sort' => $sort,
            ]
        );

        $products = $catalogQuery
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => $this->storefrontService->formatProduct($product));

        return Inertia::render('user/Home', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'banners' => $this->storefrontService->getActiveBanners(),
            'categories' => $this->storefrontService->getActiveCategories(),
            'recommendedProducts' => $recommendedProducts,
            'bestSellerProducts' => $bestSellerProducts,
            'products' => $products,
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'sort' => $sort,
            ],
        ]);
    }
}
