<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cart\AddToCartRequest;
use App\Http\Requests\Api\Cart\UpdateCartRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Services\User\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    /**
     * Display the authenticated user's shopping cart.
     */
    public function index(Request $request): Response
    {
        $cartItems = Cart::query()
            ->where('user_id', $request->user()->id)
            ->with(['product.category:id,name,slug'])
            ->latest('id')
            ->get();

        $items = $cartItems->map(fn (Cart $item): array => [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'variant_info' => $item->variant_info,
            'quantity' => (int) $item->quantity,
            'subtotal' => (float) ($item->product?->price ?? 0) * (int) $item->quantity,
            'product' => $item->product ? [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'price' => (float) $item->product->price,
                'stock' => (int) $item->product->stock,
                'weight' => (int) $item->product->weight,
                'is_active' => (bool) $item->product->is_active,
                'primary_image_url' => $item->product->primary_image_url,
                'category_name' => $item->product->category?->name,
            ] : null,
        ])->values()->all();

        $totalItems = (int) $cartItems->sum('quantity');
        $totalWeight = (int) $cartItems->sum(
            fn (Cart $item): int => (int) ($item->product?->weight ?? 0) * (int) $item->quantity
        );
        $totalPrice = (float) $cartItems->sum(
            fn (Cart $item): float => (float) ($item->product?->price ?? 0) * (int) $item->quantity
        );

        return Inertia::render('user/Cart', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'items' => $items,
            'summary' => [
                'total_items' => $totalItems,
                'total_weight' => $totalWeight,
                'total_price' => $totalPrice,
            ],
        ]);
    }

    /**
     * Add a product to the authenticated user's cart.
     */
    public function store(AddToCartRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $quantityToAdd = (int) ($validated['quantity'] ?? 1);
        $variantInfo = $validated['variant_info'] ?? null;

        $product = Product::find($validated['product_id']);

        if (! $product || ! $product->is_active) {
            return back()->with('error', 'Produk tidak tersedia atau sedang dinonaktifkan.');
        }

        $cartItem = Cart::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('variant_info', $variantInfo)
            ->first();

        $currentQty = $cartItem ? (int) $cartItem->quantity : 0;
        $newQty = $currentQty + $quantityToAdd;

        if ($newQty > 20) {
            return back()->with('error', 'Maksimal jumlah barang per varian/produk di keranjang adalah 20.');
        }

        if ($product->stock < $newQty) {
            return back()->with('error', "Stok produk tidak mencukupi (stok tersisa: {$product->stock}).");
        }

        if ($cartItem) {
            $cartItem->update(['quantity' => $newQty]);
        } else {
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'variant_info' => $variantInfo,
                'quantity' => $newQty,
            ]);
        }

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    /**
     * Update the quantity of a cart item.
     */
    public function update(UpdateCartRequest $request, Cart $cart): RedirectResponse
    {
        abort_if($cart->user_id !== $request->user()->id, 403);

        $newQty = (int) $request->validated('quantity');
        $product = $cart->product;

        if (! $product || ! $product->is_active) {
            return back()->with('error', 'Produk tidak tersedia atau sedang dinonaktifkan.');
        }

        if ($product->stock < $newQty) {
            return back()->with('error', "Stok produk tidak mencukupi (stok tersisa: {$product->stock}).");
        }

        $cart->update(['quantity' => $newQty]);

        return back()->with('success', 'Jumlah item keranjang diperbarui.');
    }

    /**
     * Remove an item from the authenticated user's cart.
     */
    public function destroy(Request $request, Cart $cart): RedirectResponse
    {
        abort_if($cart->user_id !== $request->user()->id, 403);

        $cart->delete();

        return back()->with('success', 'Item berhasil dihapus dari keranjang.');
    }
}
