<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Minus,
    Package,
    Plus,
    ShoppingBag,
    ShoppingCart,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import type { StoreIdentity } from '@/types/storefront';

interface CartProduct {
    id: number;
    name: string;
    slug: string;
    price: number;
    stock: number;
    weight: number;
    is_active: boolean;
    primary_image_url: string | null;
    category_name: string | null;
}

interface CartItem {
    id: number;
    product_id: number;
    variant_info: string | null;
    quantity: number;
    subtotal: number;
    product: CartProduct | null;
}

interface CartSummary {
    total_items: number;
    total_weight: number;
    total_price: number;
}

const props = defineProps<{
    store: StoreIdentity;
    items: CartItem[];
    summary: CartSummary;
}>();

const processingId = ref<number | null>(null);
const failedImages = ref<Record<number, boolean>>({});

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

const updateQuantity = (item: CartItem, nextQty: number): void => {
    if (processingId.value !== null || nextQty < 1 || nextQty > 20) {
        return;
    }

    if (item.product && nextQty > item.product.stock) {
        return;
    }

    router.patch(
        `/cart/${item.id}`,
        { quantity: nextQty },
        {
            preserveScroll: true,
            onStart: () => {
                processingId.value = item.id;
            },
            onFinish: () => {
                processingId.value = null;
            },
        },
    );
};

const removeItem = (item: CartItem): void => {
    if (processingId.value !== null) {
        return;
    }

    router.delete(`/cart/${item.id}`, {
        preserveScroll: true,
        onStart: () => {
            processingId.value = item.id;
        },
        onFinish: () => {
            processingId.value = null;
        },
    });
};
</script>

<template>
    <div>
        <Head :title="`Keranjang Belanja - ${store.name}`" />

        <StorefrontShell :store="store" active-nav="cart">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">
                            Keranjang Belanja
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Kelola produk pilihan Anda sebelum melanjutkan pemesanan.
                        </p>
                    </div>
                    <Link href="/products">
                        <Button variant="outline" size="sm" class="gap-1.5">
                            <ShoppingBag class="h-4 w-4" />
                            Lanjut Belanja
                        </Button>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-if="items.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/20 px-6 py-16 text-center"
                >
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <ShoppingCart class="h-8 w-8" />
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-foreground">
                        Keranjang belanja masih kosong
                    </h2>
                    <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                        Jelajahi katalog produk kami dan tambahkan produk favorit Anda ke keranjang.
                    </p>
                    <Link href="/products" class="mt-6">
                        <Button class="gap-2">
                            Jelajahi Produk
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                </div>

                <!-- Cart Content -->
                <div v-else class="grid gap-6 lg:grid-cols-12 lg:items-start">
                    <!-- Item List -->
                    <div class="space-y-4 lg:col-span-8">
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="flex flex-col gap-4 rounded-2xl border border-border bg-card p-4 shadow-xs sm:flex-row sm:items-center"
                        >
                            <Link
                                :href="item.product ? `/products/${item.product.slug}` : '/products'"
                                class="h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-border bg-muted"
                            >
                                <img
                                    v-if="item.product?.primary_image_url && !failedImages[item.id]"
                                    :src="item.product.primary_image_url"
                                    :alt="item.product.name"
                                    @error="failedImages[item.id] = true"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-muted-foreground"
                                >
                                    <Package class="h-8 w-8 opacity-40" />
                                </div>
                            </Link>

                            <div class="min-w-0 flex-1 space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge v-if="item.product?.category_name" variant="secondary" class="text-[10px]">
                                        {{ item.product.category_name }}
                                    </Badge>
                                    <Badge
                                        v-if="item.product && (!item.product.is_active || item.product.stock <= 0)"
                                        variant="destructive"
                                        class="text-[10px]"
                                    >
                                        Tidak Tersedia
                                    </Badge>
                                </div>

                                <Link
                                    :href="item.product ? `/products/${item.product.slug}` : '/products'"
                                    class="block truncate text-sm font-semibold text-foreground hover:text-primary sm:text-base"
                                >
                                    {{ item.product?.name ?? 'Produk tidak ditemukan' }}
                                </Link>

                                <p v-if="item.variant_info" class="text-xs text-muted-foreground">
                                    Varian: {{ item.variant_info }}
                                </p>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 pt-0.5 text-xs text-muted-foreground">
                                    <span class="font-medium text-foreground">
                                        {{ formatRupiah(item.product?.price ?? 0) }}
                                    </span>
                                    <span v-if="item.product">
                                        Stok: {{ item.product.stock }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                                <p class="text-sm font-bold text-primary sm:text-base">
                                    {{ formatRupiah(item.subtotal) }}
                                </p>

                                <div class="flex items-center gap-2">
                                    <div class="inline-flex items-center rounded-lg border border-border bg-background">
                                        <button
                                            type="button"
                                            @click="updateQuantity(item, item.quantity - 1)"
                                            :disabled="processingId === item.id || item.quantity <= 1"
                                            class="flex h-8 w-8 items-center justify-center text-foreground transition hover:bg-muted disabled:opacity-40"
                                            aria-label="Kurangi jumlah"
                                        >
                                            <Minus class="h-3.5 w-3.5" />
                                        </button>
                                        <span class="min-w-8 text-center text-xs font-semibold">
                                            {{ item.quantity }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="updateQuantity(item, item.quantity + 1)"
                                            :disabled="
                                                processingId === item.id ||
                                                item.quantity >= 20 ||
                                                !item.product ||
                                                item.quantity >= item.product.stock
                                            "
                                            class="flex h-8 w-8 items-center justify-center text-foreground transition hover:bg-muted disabled:opacity-40"
                                            aria-label="Tambah jumlah"
                                        >
                                            <Plus class="h-3.5 w-3.5" />
                                        </button>
                                    </div>

                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        @click="removeItem(item)"
                                        :disabled="processingId === item.id"
                                        class="h-8 w-8 text-muted-foreground hover:text-destructive"
                                        aria-label="Hapus item"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Sidebar -->
                    <div class="rounded-2xl border border-border bg-card p-6 shadow-xs lg:sticky lg:top-24 lg:col-span-4">
                        <h2 class="text-base font-bold text-foreground">Ringkasan Belanja</h2>
                        <Separator class="my-4" />

                        <div class="space-y-2.5 text-sm">
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span>Total Barang</span>
                                <span class="font-medium text-foreground">{{ summary.total_items }} pcs</span>
                            </div>
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span>Estimasi Berat</span>
                                <span class="font-medium text-foreground">{{ formatWeight(summary.total_weight) }}</span>
                            </div>
                        </div>

                        <Separator class="my-4" />

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-foreground">Total Harga</span>
                            <span class="text-lg font-extrabold text-primary">
                                {{ formatRupiah(summary.total_price) }}
                            </span>
                        </div>

                        <Link href="/checkout" class="mt-5 block">
                            <Button size="lg" class="w-full gap-2 font-semibold">
                                Lanjut ke Checkout
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                        <Link href="/products" class="mt-3 block">
                            <Button variant="outline" class="w-full">
                                Tambah Produk Lain
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </StorefrontShell>
    </div>
</template>
