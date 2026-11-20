<?php

namespace App\Http\Controllers;

use App\Exports\ProductTemplateExport;
use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Imports\ProductsImport;
use App\Models\Product;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use App\Repositories\Contracts\VariantTypeRepositoryInterface;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ProductService $productService,
        protected CategoryRepositoryInterface $categoryRepository,
        protected VariantTypeRepositoryInterface $variantTypeRepository,
        protected ProductReviewRepositoryInterface $productReviewRepository
    ) {}

    public function index(IndexProductRequest $request)
    {
        $products = $this->productRepository->getPaginatedProducts(
            $request->input('per_page', 10),
            $request->input('search'),
            $request->input('status'),
            $request->input('sort', 'created_at'),
            $request->input('direction', 'desc')
        );

        return Inertia::render('products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'status', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create()
    {
        $categories = $this->categoryRepository->getAllActive();

        // Load all variant types with their values for selection
        $variantTypes = $this->variantTypeRepository->getAllActive();

        return Inertia::render('products/Create', [
            'categories' => $categories,
            'variantTypes' => $variantTypes,
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $variantValues = $validated['variant_values'] ?? [];
        $images = $validated['gallery_images'] ?? [];

        try {
            $this->productService->createProduct($validated, $variantValues, $images);

            return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Failed to create product: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menambahkan produk: '.$e->getMessage())->withInput();
        }
    }

    public function show(Request $request, Product $product)
    {
        $product->load(['category', 'images', 'variantValues.variantType']);

        $perPage = (int) $request->input('review_per_page', 10);
        if (! in_array($perPage, [5, 10, 15, 20, 50])) {
            $perPage = 10;
        }

        $ratingFilter = $request->filled('review_rating') ? (int) $request->input('review_rating') : null;

        $reviews = $this->productReviewRepository->getPaginatedReviewsForProduct(
            $product->id,
            $perPage,
            $ratingFilter,
            false // allow viewing all reviews in admin product detail
        );

        $ratingStats = $this->productReviewRepository->getProductRatingStats($product->id);

        return Inertia::render('products/Show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'primary_image_url' => $product->primary_image_url,
                'price' => $product->price,
                'stock' => $product->stock,
                'sku' => $product->sku,
                'weight' => $product->weight,
                'is_active' => (bool) $product->is_active,
                'created_at' => $product->created_at,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->image_url,
                    'sort_order' => $img->sort_order,
                ]),
                'variants' => $product->variantValues->groupBy('variant_type_id')->map(function ($values) {
                    $first = $values->first();

                    return [
                        'type' => $first->variantType->name,
                        'values' => $values->pluck('value')->toArray(),
                    ];
                })->values(),
            ],
            'reviews' => $reviews,
            'rating_stats' => $ratingStats,
            'review_filters' => [
                'review_per_page' => $perPage,
                'review_rating' => $ratingFilter,
            ],
        ]);
    }

    public function edit(Product $product)
    {
        $product->load(['category', 'images', 'variantValues']);

        $categories = $this->categoryRepository->getAllActive();
        $variantTypes = $this->variantTypeRepository->getAllActive();

        return Inertia::render('products/Edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category_id' => $product->category_id,
                'description' => $product->description,
                'primary_image_url' => $product->primary_image_url,
                'price' => $product->price,
                'stock' => $product->stock,
                'sku' => $product->sku,
                'weight' => $product->weight,
                'is_active' => (bool) $product->is_active,
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->image_url,
                    'sort_order' => $img->sort_order,
                ]),
                'variant_values' => $product->variantValues->pluck('id'),
            ],
            'categories' => $categories,
            'variantTypes' => $variantTypes,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $variantValues = $validated['variant_values'] ?? [];
        $newImages = $validated['gallery_images'] ?? [];
        $deletedImages = $validated['deleted_images'] ?? [];

        try {
            $this->productService->updateProduct($product, $validated, $variantValues, $newImages, $deletedImages);

            return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Failed to update product: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal memperbarui produk: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(Product $product)
    {
        try {
            $this->productService->deleteProduct($product);

            return back()->with('success', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Failed to delete product: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal menghapus produk: '.$e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $import = new ProductsImport;
            Excel::import($import, $request->file('file'));

            return back()->with('success', "Berhasil mengimpor {$import->getImportedCount()} produk dari Excel/CSV.");
        } catch (\Exception $e) {
            Log::error('Failed to import products: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal mengimpor produk: Pastikan format sesuai template. ('.$e->getMessage().')');
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new ProductTemplateExport, 'product_import_template.xlsx');
    }
}
