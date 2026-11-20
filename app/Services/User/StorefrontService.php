<?php

namespace App\Services\User;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Builder;

class StorefrontService
{
    /**
     * Build the base active product query with eager-loaded relations and aggregates.
     */
    public function baseProductQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->with([
                'category:id,name,slug',
                'images:id,product_id,image_path,sort_order',
                'variantValues.variantType:id,name',
            ])
            ->withCount([
                'reviews as reviews_count' => fn (Builder $query) => $query->where('is_visible', true),
            ])
            ->withAvg([
                'reviews as average_rating' => fn (Builder $query) => $query->where('is_visible', true),
            ], 'rating')
            ->withSum([
                'orderDetails as sold_count' => fn (Builder $query) => $query->whereHas(
                    'order',
                    fn (Builder $orderQuery) => $orderQuery->whereIn('status', ['processing', 'success', 'completed'])
                ),
            ], 'quantity');
    }

    /**
     * Apply search, category, price, stock, rating, variant, and sort filters to a product query.
     *
     * @param  array<string, mixed>  $filters
     */
    public function applyProductFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $categorySlug = trim((string) ($filters['category'] ?? ''));
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $inStock = filter_var($filters['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $minRating = $filters['min_rating'] ?? null;
        $variantValues = array_values(array_filter(array_map('intval', (array) ($filters['variant_values'] ?? []))));
        $sort = (string) ($filters['sort'] ?? 'latest');

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($categorySlug !== '' && $categorySlug !== 'all') {
            $query->whereHas('category', function (Builder $q) use ($categorySlug): void {
                $q->where('slug', $categorySlug);
            });
        }

        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $query->where('price', '>=', (float) $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        if ($inStock) {
            $query->where('stock', '>', 0);
        }

        if ($minRating !== null && $minRating !== '' && is_numeric($minRating) && (float) $minRating > 0) {
            $query->whereRaw(
                '(select coalesce(avg(rating), 0) from product_reviews where product_reviews.product_id = products.id and product_reviews.is_visible = 1) >= ?',
                [(float) $minRating]
            );
        }

        if ($variantValues !== []) {
            $query->whereHas('variantValues', function (Builder $q) use ($variantValues): void {
                $q->whereIn('variant_values.id', $variantValues);
            });
        }

        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'best_selling' => $query->orderByDesc('sold_count')->orderByDesc('id'),
            'highest_rated' => $query->orderByDesc('average_rating')->orderByDesc('reviews_count')->orderByDesc('id'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->orderByDesc('id'),
        };

        return $query;
    }

    /**
     * Format a product model for storefront pages.
     *
     * @return array<string, mixed>
     */
    public function formatProduct(Product $product): array
    {
        $variants = $product->variantValues
            ->groupBy('variant_type_id')
            ->map(function ($values): array {
                $first = $values->first();

                return [
                    'type' => $first?->variantType?->name ?? 'Varian',
                    'values' => $values->pluck('value')->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'sku' => $product->sku,
            'weight' => (int) $product->weight,
            'is_active' => (bool) $product->is_active,
            'primary_image_url' => $product->primary_image_url,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'gallery_images' => $product->images->map(fn ($image): array => [
                'id' => $image->id,
                'image_url' => $image->image_url,
                'sort_order' => $image->sort_order,
            ])->values()->all(),
            'variants' => $variants,
            'average_rating' => round((float) ($product->getAttribute('average_rating') ?? 0), 1),
            'reviews_count' => (int) ($product->getAttribute('reviews_count') ?? 0),
            'sold_count' => (int) ($product->getAttribute('sold_count') ?? 0),
        ];
    }

    /**
     * Fetch active promotional banners ordered for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveBanners(): array
    {
        return Banner::query()
            ->active()
            ->with([
                'product:id,name,slug',
                'category:id,name,slug',
            ])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Banner $banner): array => [
                'id' => $banner->id,
                'title' => $banner->title,
                'description' => $banner->description,
                'image_url' => $banner->image_url,
                'target_type' => $banner->target_type,
                'target_url' => $banner->target_url,
                'product_id' => $banner->product_id,
                'product_name' => $banner->product?->name,
                'product_slug' => $banner->product?->slug,
                'category_id' => $banner->category_id,
                'category_name' => $banner->category?->name,
                'category_slug' => $banner->category?->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * Fetch active categories with active product counts.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveCategories(): array
    {
        return Category::query()
            ->active()
            ->withCount([
                'products' => fn (Builder $query) => $query->where('is_active', true),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image_url' => $category->image_url,
                'products_count' => (int) $category->products_count,
            ])
            ->values()
            ->all();
    }

    /**
     * Resolve store identity and policies from store settings.
     *
     * @return array<string, mixed>
     */
    public function resolveStoreIdentity(): array
    {
        $setting = StoreSetting::query()->first();

        return [
            'name' => $setting?->name ?: config('app.name', 'Nita Store'),
            'logo_url' => $setting?->logo_url,
            'description' => $setting?->description,
            'address' => $setting?->address,
            'phone' => $setting?->phone,
            'whatsapp' => $setting?->whatsapp,
            'email' => $setting?->email,
            'website' => $setting?->website,
            'facebook_url' => $setting?->facebook_url,
            'twitter_url' => $setting?->twitter_url,
            'instagram_url' => $setting?->instagram_url,
            'privacy_policy' => $setting?->privacy_policy,
            'policy' => $setting?->policy,
            'cancellation_policy' => $setting?->cancellation_policy,
        ];
    }
}
