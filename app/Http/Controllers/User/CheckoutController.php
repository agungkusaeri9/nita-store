<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerAddress\StoreCustomerAddressRequest;
use App\Http\Requests\Api\CustomerAddress\UpdateCustomerAddressRequest;
use App\Http\Requests\User\CheckoutRequest;
use App\Models\AppConfig;
use App\Models\Cart;
use App\Models\City;
use App\Models\Courier;
use App\Models\CustomerAddress;
use App\Models\PaymentMethod;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\CustomerAddressService;
use App\Services\RajaOngkirService;
use App\Services\User\StorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService,
        protected CustomerAddressService $addressService,
        protected CheckoutService $checkoutService,
        protected RajaOngkirService $rajaOngkirService,
    ) {}

    /**
     * Display the checkout page with addresses, couriers, payment methods, and cart items.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $this->ensureCustomerProfile($user);

        $cartItems = Cart::query()
            ->where('user_id', $user->id)
            ->with('product.category:id,name,slug')
            ->latest('id')
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()
                ->route('user.cart.index')
                ->with('info', 'Keranjang belanja Anda masih kosong.');
        }

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
                'weight' => (int) $item->product->weight,
                'stock' => (int) $item->product->stock,
                'primary_image_url' => $item->product->primary_image_url,
            ] : null,
        ])->values()->all();

        $totalItems = (int) $cartItems->sum('quantity');
        $totalWeight = (int) $cartItems->sum(
            fn (Cart $item): int => (int) ($item->product?->weight ?? 0) * (int) $item->quantity
        );
        $subtotal = (float) $cartItems->sum(
            fn (Cart $item): float => (float) ($item->product?->price ?? 0) * (int) $item->quantity
        );

        $addresses = $this->addressService
            ->listForUser($user)
            ->map(fn (CustomerAddress $address): array => $this->formatAddress($address))
            ->values()
            ->all();

        $provinces = Province::query()
            ->orderBy('province_name')
            ->get(['province_id', 'province_name'])
            ->map(fn (Province $province): array => [
                'id' => (int) $province->province_id,
                'name' => (string) $province->province_name,
            ])
            ->values()
            ->all();

        $couriers = Courier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'description'])
            ->map(fn (Courier $courier): array => [
                'id' => $courier->id,
                'name' => $courier->name,
                'code' => strtolower((string) $courier->code),
                'description' => $courier->description,
            ])
            ->values()
            ->all();

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (PaymentMethod $method): array => [
                'id' => $method->id,
                'name' => $method->name,
                'logo_url' => $method->logo_url,
                'account_number' => $method->account_number,
                'account_name' => $method->account_name,
            ])
            ->values()
            ->all();

        return Inertia::render('user/Checkout', [
            'store' => $this->storefrontService->resolveStoreIdentity(),
            'items' => $items,
            'summary' => [
                'total_items' => $totalItems,
                'total_weight' => $totalWeight,
                'subtotal' => $subtotal,
            ],
            'addresses' => $addresses,
            'provinces' => $provinces,
            'couriers' => $couriers,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Store a new shipping address from checkout.
     */
    public function storeAddress(StoreCustomerAddressRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCustomerProfile($user);

        $this->addressService->createForUser($user, $request->validated());

        return back()->with('success', 'Alamat pengiriman berhasil ditambahkan.');
    }

    /**
     * Update an existing shipping address from checkout.
     */
    public function updateAddress(UpdateCustomerAddressRequest $request, int $address): RedirectResponse
    {
        $this->addressService->updateForUser($request->user(), $address, $request->validated());

        return back()->with('success', 'Alamat pengiriman berhasil diperbarui.');
    }

    /**
     * Set an address as primary from checkout.
     */
    public function setPrimaryAddress(Request $request, int $address): RedirectResponse
    {
        $this->addressService->setPrimaryForUser($request->user(), $address);

        return back()->with('success', 'Alamat utama berhasil diubah.');
    }

    /**
     * Delete a shipping address from checkout.
     */
    public function destroyAddress(Request $request, int $address): RedirectResponse
    {
        $this->addressService->deleteForUser($request->user(), $address);

        return back()->with('success', 'Alamat pengiriman berhasil dihapus.');
    }

    /**
     * Fetch cities for a selected province.
     */
    public function cities(Request $request): JsonResponse
    {
        $provinceId = $request->integer('province_id');

        if ($provinceId <= 0) {
            return response()->json([]);
        }

        $cities = City::query()
            ->where('province_id', $provinceId)
            ->orderBy('city_name')
            ->get(['city_id', 'province_id', 'city_name', 'postal_code'])
            ->map(fn (City $city): array => [
                'id' => (int) $city->city_id,
                'province_id' => (int) $city->province_id,
                'name' => (string) $city->city_name,
                'postal_code' => $city->postal_code,
            ])
            ->values();

        return response()->json($cities);
    }

    /**
     * Fetch subdistricts for a selected city.
     */
    public function subdistricts(Request $request): JsonResponse
    {
        $cityId = $request->integer('city_id');

        if ($cityId <= 0) {
            return response()->json([]);
        }

        $subdistricts = Subdistrict::query()
            ->where('city_id', $cityId)
            ->orderBy('subdistrict_name')
            ->get(['subdistrict_id', 'city_id', 'subdistrict_name'])
            ->map(fn (Subdistrict $subdistrict): array => [
                'id' => (int) $subdistrict->subdistrict_id,
                'city_id' => (int) $subdistrict->city_id,
                'name' => (string) $subdistrict->subdistrict_name,
            ])
            ->values();

        return response()->json($subdistricts);
    }

    /**
     * Calculate available shipping services and rates for an address + courier.
     */
    public function shippingCost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', 'integer'],
            'courier_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $address = $this->addressService->findForUser($user, (int) $validated['address_id']);
        $courier = Courier::query()->where('is_active', true)->find($validated['courier_id']);

        if (! $courier) {
            return response()->json([
                'services' => [],
                'message' => 'Kurir pengiriman tidak ditemukan atau tidak aktif.',
            ], 422);
        }

        $totalWeight = (int) Cart::query()
            ->where('user_id', $user->id)
            ->with('product:id,weight')
            ->get()
            ->sum(fn (Cart $item): int => (int) ($item->product?->weight ?? 0) * (int) $item->quantity);

        $origin = AppConfig::getValue('rajaongkir_origin') ?: config('services.rajaongkir.origin', 6164);
        $destination = $address->subdistrict_id ?: $address->city_id;

        if (! $destination || ! $courier->code) {
            return response()->json([
                'services' => [],
                'message' => 'Data kecamatan tujuan atau kode kurir belum lengkap.',
            ]);
        }

        try {
            $result = $this->rajaOngkirService->calculateDomesticCost([
                'origin' => $origin,
                'destination' => $destination,
                'weight' => max(1, $totalWeight),
                'courier' => strtolower($courier->code),
                'price' => 'lowest',
            ]);

            $rawServices = $result['data'] ?? [];
            $services = collect($rawServices)
                ->map(fn (array $item): array => [
                    'service' => (string) ($item['service'] ?? $courier->name),
                    'description' => (string) ($item['description'] ?? ''),
                    'cost' => (float) ($item['cost'] ?? 0),
                    'etd' => (string) ($item['etd'] ?? ''),
                    'shipping_name' => trim(strtoupper($courier->code).' - '.($item['service'] ?? $courier->name)),
                ])
                ->values()
                ->all();

            return response()->json([
                'services' => $services,
                'message' => empty($services) ? 'Tidak ada layanan kurir tersedia untuk tujuan ini.' : null,
            ]);
        } catch (Throwable) {
            return response()->json([
                'services' => [],
                'message' => 'Perhitungan ongkos kirim otomatis belum tersedia saat ini.',
            ]);
        }
    }

    /**
     * Place a new order from the authenticated user's cart.
     */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCustomerProfile($user);

        $order = $this->checkoutService->checkout($user, [
            ...$request->validated(),
            'from_cart' => true,
        ]);

        return redirect()
            ->route('user.orders.show', ['orderNumber' => $order->order_number])
            ->with('success', "Pesanan #{$order->order_number} berhasil dibuat!");
    }

    /**
     * Ensure the authenticated user has an active Customer record for checkout/address actions.
     */
    private function ensureCustomerProfile(User $user): void
    {
        $user->unsetRelation('customer');

        if (! $user->customer) {
            $user->customer()->create(['is_active' => true]);
            $user->unsetRelation('customer');
        }
    }

    /**
     * Format a CustomerAddress model for frontend props.
     *
     * @return array<string, mixed>
     */
    private function formatAddress(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'full_address' => $address->full_address,
            'province_id' => $address->province_id ? (int) $address->province_id : null,
            'province_name' => $address->province?->province_name,
            'city_id' => $address->city_id ? (int) $address->city_id : null,
            'city_name' => $address->city?->city_name,
            'subdistrict_id' => $address->subdistrict_id ? (int) $address->subdistrict_id : null,
            'subdistrict_name' => $address->subdistrict?->subdistrict_name,
            'postal_code' => $address->postal_code,
            'is_primary' => (bool) $address->is_primary,
        ];
    }
}
