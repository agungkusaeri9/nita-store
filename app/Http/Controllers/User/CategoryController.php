<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\User\StorefrontService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    /**
     * Display the public list of active product categories.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $categoriesQuery = Category::query()
            ->active()
            ->withCount([
                'products' => fn (Builder $query) => $query->where('is_active', true),
            ])
            ->orderBy('name');

        if ($search !== '') {
            $categoriesQuery->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $categoriesQuery
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

        return Inertia::render('user/Categories', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'categories' => $categories,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
