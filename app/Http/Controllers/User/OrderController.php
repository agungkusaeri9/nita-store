<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Order\UploadPaymentProofRequest;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\User\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    /**
     * Display the authenticated user's orders in the storefront.
     */
    public function index(Request $request): Response
    {
        $customerId = $request->user()->customer?->id;

        $orders = Order::query()
            ->where('customer_id', $customerId ?? 0)
            ->with([
                'paymentMethod:id,name',
                'courier:id,name',
                'transaction',
                'details.product:id,name,slug,primary_image',
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'subtotal' => (float) $order->subtotal,
                'shipping_fee' => (float) $order->shipping_fee,
                'total_amount' => (float) $order->total_amount,
                'payment_method' => $order->paymentMethod?->name,
                'courier' => $order->courier?->name,
                'has_payment_proof' => (bool) $order->transaction?->payment_proof,
                'created_at' => $order->created_at?->translatedFormat('d M Y, H:i'),
                'items_count' => $order->details->sum('quantity'),
                'items' => $order->details->map(fn ($detail): array => [
                    'id' => $detail->id,
                    'quantity' => (int) $detail->quantity,
                    'price' => (float) $detail->price,
                    'total_price' => (float) $detail->total_price,
                    'product_name' => $detail->product?->name ?? 'Produk',
                    'product_slug' => $detail->product?->slug,
                    'product_image_url' => $detail->product?->primary_image_url,
                ])->values()->all(),
            ]);

        return Inertia::render('user/Orders', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'orders' => $orders,
        ]);
    }

    /**
     * Display the detail page for a customer order.
     */
    public function show(Request $request, string $orderNumber): Response
    {
        $customerId = $request->user()->customer?->id;

        $order = Order::query()
            ->where('customer_id', $customerId ?? 0)
            ->where('order_number', $orderNumber)
            ->with([
                'paymentMethod',
                'courier:id,name,code',
                'province:province_id,province_name',
                'city:city_id,city_name,postal_code',
                'subdistrict:subdistrict_id,subdistrict_name',
                'transaction',
                'details.product:id,name,slug,primary_image',
            ])
            ->firstOrFail();

        return Inertia::render('user/OrderDetail', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'created_at' => $order->created_at?->translatedFormat('d M Y, H:i'),
                'subtotal' => (float) $order->subtotal,
                'shipping_fee' => (float) $order->shipping_fee,
                'total_amount' => (float) $order->total_amount,
                'address' => $order->address,
                'region' => collect([
                    $order->subdistrict?->subdistrict_name,
                    $order->city?->city_name,
                    $order->province?->province_name,
                    $order->city?->postal_code,
                ])->filter()->implode(', '),
                'shipping_name' => $order->shipping_name,
                'tracking_number' => $order->tracking_number,
                'courier' => $order->courier ? [
                    'name' => $order->courier->name,
                    'code' => $order->courier->code,
                ] : null,
                'payment_method' => $order->paymentMethod ? [
                    'name' => $order->paymentMethod->name,
                    'account_number' => $order->paymentMethod->account_number,
                    'account_name' => $order->paymentMethod->account_name,
                    'logo_url' => $order->paymentMethod->logo_url,
                ] : null,
                'transaction' => $order->transaction ? [
                    'status' => $order->transaction->status,
                    'payment_proof_url' => $order->transaction->payment_proof_url,
                ] : null,
                'items' => $order->details->map(fn ($detail): array => [
                    'id' => $detail->id,
                    'quantity' => (int) $detail->quantity,
                    'price' => (float) $detail->price,
                    'total_price' => (float) $detail->total_price,
                    'variant_info' => $detail->variant_info,
                    'product_name' => $detail->product?->name ?? 'Produk',
                    'product_slug' => $detail->product?->slug,
                    'product_image_url' => $detail->product?->primary_image_url,
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Upload payment proof image for the customer's order.
     */
    public function uploadPaymentProof(UploadPaymentProofRequest $request, string $orderNumber): RedirectResponse
    {
        $customerId = $request->user()->customer?->id;

        $order = Order::query()
            ->where('customer_id', $customerId ?? 0)
            ->where('order_number', $orderNumber)
            ->with('transaction')
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return back()->with('error', 'Bukti pembayaran hanya dapat diunggah untuk pesanan yang menunggu pembayaran.');
        }

        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        if ($order->transaction && $order->transaction->payment_proof) {
            if (Storage::disk('public')->exists($order->transaction->payment_proof)) {
                Storage::disk('public')->delete($order->transaction->payment_proof);
            }
        }

        if ($order->transaction) {
            $order->transaction->update(['payment_proof' => $path]);
        } else {
            Transaction::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'payment_proof' => $path,
                'status' => 'pending',
            ]);
        }

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Pesanan Anda akan segera kami verifikasi dan proses.');
    }
}
