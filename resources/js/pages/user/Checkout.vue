<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    ChevronRight,
    CreditCard,
    Loader2,
    MapPin,
    Package,
    Pencil,
    Plus,
    ShoppingBag,
    Star,
    Trash2,
    Truck,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import type { StoreIdentity } from '@/types/storefront';

interface CheckoutProduct {
    id: number;
    name: string;
    slug: string;
    price: number;
    weight: number;
    stock: number;
    primary_image_url: string | null;
}

interface CheckoutItem {
    id: number;
    product_id: number;
    variant_info: string | null;
    quantity: number;
    subtotal: number;
    product: CheckoutProduct | null;
}

interface CheckoutSummary {
    total_items: number;
    total_weight: number;
    subtotal: number;
}

interface CustomerAddressItem {
    id: number;
    label: string | null;
    recipient_name: string;
    phone: string;
    full_address: string;
    province_id: number | null;
    province_name: string | null;
    city_id: number | null;
    city_name: string | null;
    subdistrict_id: number | null;
    subdistrict_name: string | null;
    postal_code: string | null;
    is_primary: boolean;
}

interface LocationOption {
    id: number;
    name: string;
    postal_code?: string | null;
}

interface CourierOption {
    id: number;
    name: string;
    code: string;
    description: string | null;
}

interface ShippingServiceOption {
    service: string;
    description: string;
    cost: number;
    etd: string;
    shipping_name: string;
}

interface PaymentMethodOption {
    id: number;
    name: string;
    logo_url: string | null;
    account_number: string | null;
    account_name: string | null;
}

const props = defineProps<{
    store: StoreIdentity;
    items: CheckoutItem[];
    summary: CheckoutSummary;
    addresses: CustomerAddressItem[];
    provinces: LocationOption[];
    couriers: CourierOption[];
    paymentMethods: PaymentMethodOption[];
}>();

const formatRupiah = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatWeight = (grams: number): string => {
    if (grams >= 1000) {
        return `${(grams / 1000).toFixed(1).replace(/\.0$/, '')} kg`;
    }

    return `${grams} gr`;
};

// --- Selection State ---
const defaultAddressId = (): number | null => {
    const primary = props.addresses.find((a) => a.is_primary);
    return primary?.id ?? props.addresses[0]?.id ?? null;
};

const selectedAddressId = ref<number | null>(defaultAddressId());
const selectedCourierId = ref<number | null>(props.couriers[0]?.id ?? null);
const selectedPaymentMethodId = ref<number | null>(props.paymentMethods[0]?.id ?? null);

watch(
    () => props.addresses,
    (list) => {
        if (!selectedAddressId.value || !list.some((a) => a.id === selectedAddressId.value)) {
            selectedAddressId.value = list.find((a) => a.is_primary)?.id ?? list[0]?.id ?? null;
        }
    },
    { deep: true },
);

// --- Shipping Rates Calculation ---
const shippingServices = ref<ShippingServiceOption[]>([]);
const selectedService = ref<ShippingServiceOption | null>(null);
const loadingShipping = ref(false);
const shippingMessage = ref<string | null>(null);

const selectedCourier = computed(
    () => props.couriers.find((c) => c.id === selectedCourierId.value) ?? null,
);

const fetchShippingRates = async (): Promise<void> => {
    shippingServices.value = [];
    selectedService.value = null;
    shippingMessage.value = null;

    if (!selectedAddressId.value || !selectedCourierId.value) {
        return;
    }

    loadingShipping.value = true;

    try {
        const params = new URLSearchParams({
            address_id: String(selectedAddressId.value),
            courier_id: String(selectedCourierId.value),
        });

        const response = await fetch(`/checkout/shipping-cost?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        const payload = await response.json();

        if (Array.isArray(payload.services) && payload.services.length > 0) {
            shippingServices.value = payload.services;
            selectedService.value = payload.services[0];
        } else {
            shippingMessage.value =
                payload.message || 'Layanan ongkos kirim otomatis tidak tersedia.';

            if (selectedCourier.value) {
                selectedService.value = {
                    service: 'Reguler',
                    description: 'Estimasi ongkos kirim dikonfirmasi admin',
                    cost: 0,
                    etd: '-',
                    shipping_name: `${selectedCourier.value.name} - Reguler`,
                };
            }
        }
    } catch {
        shippingMessage.value = 'Gagal menghitung ongkos kirim otomatis.';
        if (selectedCourier.value) {
            selectedService.value = {
                service: 'Reguler',
                description: 'Estimasi ongkos kirim dikonfirmasi admin',
                cost: 0,
                etd: '-',
                shipping_name: `${selectedCourier.value.name} - Reguler`,
            };
        }
    } finally {
        loadingShipping.value = false;
    }
};

watch([selectedAddressId, selectedCourierId], () => {
    fetchShippingRates();
}, { immediate: true });

const shippingFee = computed(() => Number(selectedService.value?.cost ?? 0));
const totalPayable = computed(() => props.summary.subtotal + shippingFee.value);

// --- Address Management Modal ---
const isAddressDialogOpen = ref(false);
const editingAddressId = ref<number | null>(null);
const cities = ref<LocationOption[]>([]);
const subdistricts = ref<LocationOption[]>([]);
const loadingCities = ref(false);
const loadingSubdistricts = ref(false);

const addressForm = useForm({
    label: 'Rumah',
    recipient_name: '',
    phone: '',
    province_id: '' as number | '',
    city_id: '' as number | '',
    subdistrict_id: '' as number | '',
    postal_code: '',
    full_address: '',
    is_primary: false,
});

const loadCities = async (provinceId: number | ''): Promise<void> => {
    cities.value = [];

    if (!provinceId) {
        return;
    }

    loadingCities.value = true;
    try {
        const res = await fetch(`/checkout/locations/cities?province_id=${provinceId}`, {
            headers: { Accept: 'application/json' },
        });
        cities.value = await res.json();
    } finally {
        loadingCities.value = false;
    }
};

const loadSubdistricts = async (cityId: number | ''): Promise<void> => {
    subdistricts.value = [];

    if (!cityId) {
        return;
    }

    loadingSubdistricts.value = true;
    try {
        const res = await fetch(`/checkout/locations/subdistricts?city_id=${cityId}`, {
            headers: { Accept: 'application/json' },
        });
        subdistricts.value = await res.json();
    } finally {
        loadingSubdistricts.value = false;
    }
};

const handleProvinceChange = async (): Promise<void> => {
    addressForm.city_id = '';
    addressForm.subdistrict_id = '';
    subdistricts.value = [];
    await loadCities(addressForm.province_id);
};

const handleCityChange = async (): Promise<void> => {
    addressForm.subdistrict_id = '';
    const cityMatch = cities.value.find((c) => c.id === Number(addressForm.city_id));
    if (cityMatch?.postal_code && !addressForm.postal_code) {
        addressForm.postal_code = cityMatch.postal_code;
    }
    await loadSubdistricts(addressForm.city_id);
};

const openCreateAddressModal = (): void => {
    editingAddressId.value = null;
    addressForm.reset();
    addressForm.clearErrors();
    addressForm.label = 'Rumah';
    addressForm.is_primary = props.addresses.length === 0;
    cities.value = [];
    subdistricts.value = [];
    isAddressDialogOpen.value = true;
};

const openEditAddressModal = async (addr: CustomerAddressItem): Promise<void> => {
    editingAddressId.value = addr.id;
    addressForm.clearErrors();
    addressForm.label = addr.label || 'Rumah';
    addressForm.recipient_name = addr.recipient_name;
    addressForm.phone = addr.phone;
    addressForm.province_id = addr.province_id ?? '';
    addressForm.city_id = addr.city_id ?? '';
    addressForm.subdistrict_id = addr.subdistrict_id ?? '';
    addressForm.postal_code = addr.postal_code || '';
    addressForm.full_address = addr.full_address;
    addressForm.is_primary = addr.is_primary;

    isAddressDialogOpen.value = true;

    if (addr.province_id) {
        await loadCities(addr.province_id);
    }
    if (addr.city_id) {
        await loadSubdistricts(addr.city_id);
    }
};

const submitAddress = (): void => {
    if (editingAddressId.value) {
        addressForm.patch(`/checkout/addresses/${editingAddressId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isAddressDialogOpen.value = false;
            },
        });
        return;
    }

    addressForm.post('/checkout/addresses', {
        preserveScroll: true,
        onSuccess: () => {
            isAddressDialogOpen.value = false;
        },
    });
};

const setPrimaryAddress = (addressId: number): void => {
    router.patch(`/checkout/addresses/${addressId}/primary`, {}, { preserveScroll: true });
};

const deleteAddress = (addressId: number): void => {
    router.delete(`/checkout/addresses/${addressId}`, { preserveScroll: true });
};

// --- Place Order ---
const orderForm = useForm({
    address_id: 0,
    courier_id: 0,
    shipping_name: '',
    shipping_fee: 0,
    payment_method_id: 0,
});

const canSubmitOrder = computed(() => {
    return (
        !orderForm.processing &&
        !loadingShipping.value &&
        selectedAddressId.value !== null &&
        selectedCourierId.value !== null &&
        selectedService.value !== null &&
        selectedPaymentMethodId.value !== null
    );
});

const submitOrder = (): void => {
    if (!canSubmitOrder.value || !selectedService.value) {
        return;
    }

    orderForm.address_id = selectedAddressId.value as number;
    orderForm.courier_id = selectedCourierId.value as number;
    orderForm.shipping_name = selectedService.value.shipping_name;
    orderForm.shipping_fee = selectedService.value.cost;
    orderForm.payment_method_id = selectedPaymentMethodId.value as number;

    orderForm.post('/checkout', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div>
        <Head :title="`Checkout - ${store.name}`" />

        <StorefrontShell :store="store" active-nav="cart">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                    <Link href="/" class="hover:text-primary">Beranda</Link>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <Link href="/cart" class="hover:text-primary">Keranjang</Link>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <span class="font-medium text-foreground">Checkout</span>
                </nav>

                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">Checkout Pesanan</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Lengkapi alamat pengiriman, pilih kurir, dan pilih metode pembayaran.
                        </p>
                    </div>
                    <Link href="/cart">
                        <Button variant="outline" size="sm">Kembali ke Keranjang</Button>
                    </Link>
                </div>

                <!-- Validation Error Summary -->
                <div
                    v-if="Object.keys(orderForm.errors).length > 0"
                    class="mb-6 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-xs text-destructive"
                >
                    <p class="font-semibold">Mohon periksa kembali data checkout:</p>
                    <ul class="mt-1.5 list-disc pl-4">
                        <li v-for="(err, key) in orderForm.errors" :key="key">{{ err }}</li>
                    </ul>
                </div>

                <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                    <!-- KOLOM KIRI: ALAMAT, PRODUK, KURIR, PEMBAYARAN -->
                    <div class="space-y-6 lg:col-span-8">
                        <!-- 1. MANAJEMEN ALAMAT PENGIRIMAN -->
                        <section class="rounded-2xl border border-border bg-card p-5 shadow-xs sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                        <MapPin class="h-4.5 w-4.5" />
                                    </div>
                                    <div>
                                        <h2 class="text-base font-bold text-foreground">Alamat Pengiriman</h2>
                                        <p class="text-xs text-muted-foreground">
                                            Pilih alamat tujuan atau kelola daftar alamat Anda.
                                        </p>
                                    </div>
                                </div>

                                <Button type="button" size="sm" variant="outline" @click="openCreateAddressModal" class="gap-1.5">
                                    <Plus class="h-4 w-4" />
                                    Tambah Alamat
                                </Button>
                            </div>

                            <Separator class="my-4" />

                            <div
                                v-if="addresses.length === 0"
                                class="rounded-xl border border-dashed border-border bg-muted/20 p-6 text-center"
                            >
                                <p class="text-sm font-medium text-foreground">Belum ada alamat pengiriman tersimpan.</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Tambahkan alamat pengiriman terlebih dahulu untuk menghitung ongkos kirim.
                                </p>
                                <Button type="button" size="sm" @click="openCreateAddressModal" class="mt-4 gap-1.5">
                                    <Plus class="h-4 w-4" />
                                    Tambah Alamat Pertama
                                </Button>
                            </div>

                            <div v-else class="grid gap-3">
                                <div
                                    v-for="addr in addresses"
                                    :key="addr.id"
                                    @click="selectedAddressId = addr.id"
                                    class="cursor-pointer rounded-xl border p-4 transition"
                                    :class="
                                        selectedAddressId === addr.id
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                            : 'border-border bg-background hover:border-primary/40'
                                    "
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1 space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-bold text-foreground">
                                                    {{ addr.label || 'Alamat' }}
                                                </span>
                                                <Badge v-if="addr.is_primary" variant="default" class="text-[10px]">
                                                    Utama
                                                </Badge>
                                                <CheckCircle2
                                                    v-if="selectedAddressId === addr.id"
                                                    class="h-4 w-4 text-primary"
                                                />
                                            </div>
                                            <p class="text-xs font-semibold text-foreground">
                                                {{ addr.recipient_name }} &bull; {{ addr.phone }}
                                            </p>
                                            <p class="text-xs leading-relaxed text-muted-foreground">
                                                {{ addr.full_address }}
                                                <template v-if="addr.subdistrict_name || addr.city_name || addr.province_name">
                                                    —
                                                    {{
                                                        [
                                                            addr.subdistrict_name,
                                                            addr.city_name,
                                                            addr.province_name,
                                                            addr.postal_code,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(', ')
                                                    }}
                                                </template>
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-1" @click.stop>
                                            <Button
                                                v-if="!addr.is_primary"
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="setPrimaryAddress(addr.id)"
                                                class="h-8 gap-1 px-2 text-xs text-muted-foreground hover:text-primary"
                                                title="Jadikan alamat utama"
                                            >
                                                <Star class="h-3.5 w-3.5" />
                                                <span class="hidden sm:inline">Utama</span>
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                @click="openEditAddressModal(addr)"
                                                class="h-8 w-8 text-muted-foreground hover:text-foreground"
                                                aria-label="Edit alamat"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                @click="deleteAddress(addr.id)"
                                                class="h-8 w-8 text-muted-foreground hover:text-destructive"
                                                aria-label="Hapus alamat"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 2. PILIH KURIR & LAYANAN PENGIRIMAN -->
                        <section class="rounded-2xl border border-border bg-card p-5 shadow-xs sm:p-6">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <Truck class="h-4.5 w-4.5" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-foreground">Kurir & Ongkos Kirim</h2>
                                    <p class="text-xs text-muted-foreground">
                                        Pilih ekspedisi dan layanan pengiriman yang diinginkan.
                                    </p>
                                </div>
                            </div>

                            <Separator class="my-4" />

                            <div v-if="couriers.length === 0" class="text-xs text-muted-foreground">
                                Belum ada kurir pengiriman yang aktif saat ini.
                            </div>

                            <div v-else class="space-y-4">
                                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                                    <button
                                        v-for="courier in couriers"
                                        :key="courier.id"
                                        type="button"
                                        @click="selectedCourierId = courier.id"
                                        class="flex flex-col items-start rounded-xl border p-3.5 text-left transition"
                                        :class="
                                            selectedCourierId === courier.id
                                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                                : 'border-border bg-background hover:border-primary/40'
                                        "
                                    >
                                        <span class="text-sm font-bold text-foreground">{{ courier.name }}</span>
                                        <span class="text-[11px] uppercase text-muted-foreground">{{ courier.code }}</span>
                                    </button>
                                </div>

                                <!-- Daftar Layanan / Ongkir -->
                                <div v-if="selectedAddressId && selectedCourierId" class="space-y-2.5 pt-1">
                                    <div v-if="loadingShipping" class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Loader2 class="h-4 w-4 animate-spin text-primary" />
                                        Menghitung ongkos kirim...
                                    </div>

                                    <template v-else>
                                        <div
                                            v-if="shippingServices.length > 0"
                                            class="grid gap-2.5 sm:grid-cols-2"
                                        >
                                            <button
                                                v-for="svc in shippingServices"
                                                :key="svc.shipping_name"
                                                type="button"
                                                @click="selectedService = svc"
                                                class="flex items-center justify-between gap-3 rounded-xl border p-3.5 text-left transition"
                                                :class="
                                                    selectedService?.shipping_name === svc.shipping_name
                                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                                        : 'border-border bg-background hover:border-primary/40'
                                                "
                                            >
                                                <div class="min-w-0">
                                                    <p class="truncate text-xs font-bold text-foreground">
                                                        {{ svc.shipping_name }}
                                                    </p>
                                                    <p class="truncate text-[11px] text-muted-foreground">
                                                        {{ svc.description || svc.service }}
                                                        <template v-if="svc.etd"> &bull; Est. {{ svc.etd }} hari</template>
                                                    </p>
                                                </div>
                                                <span class="shrink-0 text-xs font-bold text-primary">
                                                    {{ formatRupiah(svc.cost) }}
                                                </span>
                                            </button>
                                        </div>

                                        <div
                                            v-else-if="shippingMessage"
                                            class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300"
                                        >
                                            <p class="font-medium">{{ shippingMessage }}</p>
                                            <p v-if="selectedService" class="mt-1 opacity-90">
                                                Menggunakan layanan standar: <strong>{{ selectedService.shipping_name }}</strong>
                                                (ongkos kirim akan dikonfirmasi oleh toko).
                                            </p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </section>

                        <!-- 3. PILIH METODE PEMBAYARAN -->
                        <section class="rounded-2xl border border-border bg-card p-5 shadow-xs sm:p-6">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <CreditCard class="h-4.5 w-4.5" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-foreground">Metode Pembayaran</h2>
                                    <p class="text-xs text-muted-foreground">
                                        Pilih metode pembayaran untuk menyelesaikan pesanan Anda.
                                    </p>
                                </div>
                            </div>

                            <Separator class="my-4" />

                            <div v-if="paymentMethods.length === 0" class="text-xs text-muted-foreground">
                                Belum ada metode pembayaran yang aktif saat ini.
                            </div>

                            <div v-else class="grid gap-3 sm:grid-cols-2">
                                <button
                                    v-for="method in paymentMethods"
                                    :key="method.id"
                                    type="button"
                                    @click="selectedPaymentMethodId = method.id"
                                    class="flex items-center gap-3 rounded-xl border p-3.5 text-left transition"
                                    :class="
                                        selectedPaymentMethodId === method.id
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                            : 'border-border bg-background hover:border-primary/40'
                                    "
                                >
                                    <img
                                        v-if="method.logo_url"
                                        :src="method.logo_url"
                                        :alt="method.name"
                                        class="h-8 w-12 shrink-0 rounded border border-border bg-white object-contain p-1"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-foreground">{{ method.name }}</p>
                                        <p v-if="method.account_number" class="truncate text-xs text-muted-foreground">
                                            {{ method.account_number }}
                                            <template v-if="method.account_name"> &bull; {{ method.account_name }}</template>
                                        </p>
                                    </div>
                                    <CheckCircle2
                                        v-if="selectedPaymentMethodId === method.id"
                                        class="h-4 w-4 shrink-0 text-primary"
                                    />
                                </button>
                            </div>
                        </section>

                        <!-- 4. PRODUK PESANAN -->
                        <section class="rounded-2xl border border-border bg-card p-5 shadow-xs sm:p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                        <ShoppingBag class="h-4.5 w-4.5" />
                                    </div>
                                    <div>
                                        <h2 class="text-base font-bold text-foreground">
                                            Produk Dipesan ({{ summary.total_items }} pcs)
                                        </h2>
                                    </div>
                                </div>
                                <Link href="/cart" class="text-xs font-medium text-primary hover:underline">
                                    Ubah Keranjang
                                </Link>
                            </div>

                            <Separator class="my-4" />

                            <div class="divide-y divide-border">
                                <div
                                    v-for="item in items"
                                    :key="item.id"
                                    class="flex items-center gap-3.5 py-3 first:pt-0 last:pb-0"
                                >
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-border bg-muted">
                                        <img
                                            v-if="item.product?.primary_image_url"
                                            :src="item.product.primary_image_url"
                                            :alt="item.product.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                                            <Package class="h-5 w-5 opacity-40" />
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-foreground">
                                            {{ item.product?.name ?? 'Produk' }}
                                        </p>
                                        <p v-if="item.variant_info" class="text-xs text-muted-foreground">
                                            Varian: {{ item.variant_info }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ item.quantity }} x {{ formatRupiah(item.product?.price ?? 0) }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-sm font-semibold text-foreground">
                                        {{ formatRupiah(item.subtotal) }}
                                    </span>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- KOLOM KANAN: RINGKASAN CHECKOUT -->
                    <aside class="rounded-2xl border border-border bg-card p-6 shadow-xs lg:sticky lg:top-24 lg:col-span-4">
                        <h2 class="text-base font-bold text-foreground">Ringkasan Pembayaran</h2>
                        <Separator class="my-4" />

                        <div class="space-y-2.5 text-sm">
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span>Subtotal ({{ summary.total_items }} barang)</span>
                                <span class="font-medium text-foreground">{{ formatRupiah(summary.subtotal) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span>Total Berat</span>
                                <span class="font-medium text-foreground">{{ formatWeight(summary.total_weight) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span>Ongkos Kirim</span>
                                <span class="font-medium text-foreground">{{ formatRupiah(shippingFee) }}</span>
                            </div>
                            <div v-if="selectedService" class="flex items-center justify-between text-xs text-muted-foreground">
                                <span>Layanan</span>
                                <span class="font-medium text-foreground">{{ selectedService.shipping_name }}</span>
                            </div>
                        </div>

                        <Separator class="my-4" />

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-foreground">Total Bayar</span>
                            <span class="text-xl font-extrabold text-primary">
                                {{ formatRupiah(totalPayable) }}
                            </span>
                        </div>

                        <Button
                            type="button"
                            size="lg"
                            :disabled="!canSubmitOrder"
                            @click="submitOrder"
                            class="mt-5 w-full gap-2 font-semibold"
                        >
                            <Loader2 v-if="orderForm.processing" class="h-4 w-4 animate-spin" />
                            <CheckCircle2 v-else class="h-4 w-4" />
                            {{ orderForm.processing ? 'Membuat Pesanan...' : 'Buat Pesanan' }}
                        </Button>

                        <p v-if="!selectedAddressId" class="mt-2.5 text-center text-xs text-destructive">
                            Tambahkan dan pilih alamat pengiriman terlebih dahulu.
                        </p>
                    </aside>
                </div>
            </div>
        </StorefrontShell>

        <!-- MODAL TAMBAH / EDIT ALAMAT -->
        <Dialog v-model:open="isAddressDialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingAddressId ? 'Ubah Alamat Pengiriman' : 'Tambah Alamat Pengiriman' }}
                    </DialogTitle>
                    <DialogDescription>
                        Pastikan detail alamat dan wilayah sudah benar agar ongkos kirim akurat.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitAddress" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="addr_label" class="text-xs font-semibold">Label Alamat</Label>
                            <Input
                                id="addr_label"
                                v-model="addressForm.label"
                                type="text"
                                placeholder="Rumah / Kantor"
                            />
                            <InputError :message="addressForm.errors.label" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="addr_postal" class="text-xs font-semibold">Kode Pos</Label>
                            <Input
                                id="addr_postal"
                                v-model="addressForm.postal_code"
                                type="text"
                                placeholder="50123"
                            />
                            <InputError :message="addressForm.errors.postal_code" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="addr_recipient" class="text-xs font-semibold">Nama Penerima</Label>
                            <Input
                                id="addr_recipient"
                                v-model="addressForm.recipient_name"
                                type="text"
                                required
                                placeholder="Nama lengkap penerima"
                            />
                            <InputError :message="addressForm.errors.recipient_name" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="addr_phone" class="text-xs font-semibold">No. HP Penerima</Label>
                            <Input
                                id="addr_phone"
                                v-model="addressForm.phone"
                                type="tel"
                                required
                                placeholder="0812xxxxxxxx"
                            />
                            <InputError :message="addressForm.errors.phone" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-1.5">
                            <Label for="addr_province" class="text-xs font-semibold">Provinsi</Label>
                            <select
                                id="addr_province"
                                v-model="addressForm.province_id"
                                @change="handleProvinceChange"
                                required
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs focus:ring-2 focus:ring-ring focus:outline-none"
                            >
                                <option value="">Pilih Provinsi</option>
                                <option v-for="prov in provinces" :key="prov.id" :value="prov.id">
                                    {{ prov.name }}
                                </option>
                            </select>
                            <InputError :message="addressForm.errors.province_id" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="addr_city" class="text-xs font-semibold">Kota/Kabupaten</Label>
                            <select
                                id="addr_city"
                                v-model="addressForm.city_id"
                                @change="handleCityChange"
                                :disabled="!addressForm.province_id || loadingCities"
                                required
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs focus:ring-2 focus:ring-ring focus:outline-none disabled:opacity-50"
                            >
                                <option value="">{{ loadingCities ? 'Memuat...' : 'Pilih Kota' }}</option>
                                <option v-for="city in cities" :key="city.id" :value="city.id">
                                    {{ city.name }}
                                </option>
                            </select>
                            <InputError :message="addressForm.errors.city_id" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="addr_subdistrict" class="text-xs font-semibold">Kecamatan</Label>
                            <select
                                id="addr_subdistrict"
                                v-model="addressForm.subdistrict_id"
                                :disabled="!addressForm.city_id || loadingSubdistricts"
                                required
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs focus:ring-2 focus:ring-ring focus:outline-none disabled:opacity-50"
                            >
                                <option value="">{{ loadingSubdistricts ? 'Memuat...' : 'Pilih Kecamatan' }}</option>
                                <option v-for="sub in subdistricts" :key="sub.id" :value="sub.id">
                                    {{ sub.name }}
                                </option>
                            </select>
                            <InputError :message="addressForm.errors.subdistrict_id" />
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="addr_full" class="text-xs font-semibold">Alamat Lengkap</Label>
                        <Textarea
                            id="addr_full"
                            v-model="addressForm.full_address"
                            required
                            rows="3"
                            placeholder="Nama jalan, nomor rumah, RT/RW, patokan..."
                        />
                        <InputError :message="addressForm.errors.full_address" />
                    </div>

                    <Label class="flex cursor-pointer items-center gap-2 text-xs">
                        <Checkbox
                            :model-value="addressForm.is_primary"
                            @update:model-value="(val) => (addressForm.is_primary = val === true)"
                        />
                        <span>Jadikan sebagai alamat utama</span>
                    </Label>

                    <DialogFooter class="gap-2 pt-2">
                        <Button type="button" variant="outline" @click="isAddressDialogOpen = false">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="addressForm.processing">
                            {{ addressForm.processing ? 'Menyimpan...' : 'Simpan Alamat' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
