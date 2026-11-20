<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Package, ShoppingBag } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import type { PaginationLink, StoreIdentity } from '@/types/storefront';

interface OrderItem {
    id: number;
    quantity: number;
    price: number;
    total_price: number;
    product_name: string;
    product_slug: string | null;
    product_image_url: string | null;
}

interface UserOrder {
    id: number;
    order_number: string;
    status: string;
    subtotal: number;
    shipping_fee: number;
    total_amount: number;
    payment_method: string | null;
    courier: string | null;
    has_payment_proof?: boolean;
    created_at: string | null;
    items_count: number;
    items: OrderItem[];
}

interface Props {
    store: StoreIdentity;
    orders: {
        data: UserOrder[];
        current_page: number;
        last_page: number;
        total: number;
        links: PaginationLink[];
    };
}

defineProps<Props>();

const formatRupiah = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const statusLabel = (status: string): string => {
    switch (status) {
        case 'pending':
            return 'Menunggu Pembayaran';
        case 'processing':
            return 'Diproses';
        case 'success':
        case 'completed':
            return 'Selesai';
        case 'failed':
            return 'Dibatalkan / Gagal';
        default:
            return status;
    }
};

const statusVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (status) {
        case 'success':
        case 'completed':
            return 'default';
        case 'processing':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
};

const visitPage = (url: string | null): void => {
    if (!url) {
        return;
    }

    router.visit(url, { preserveScroll: true });
};
</script>

<template>
    <div>
        <Head :title="`Pesanan Saya - ${store.name}`" />

        <StorefrontShell :store="store">
            <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
                <nav class="mb-6 flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Link href="/" class="hover:text-primary">Beranda</Link>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <span class="font-medium text-foreground">Pesanan Saya</span>
                </nav>

                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <ShoppingBag class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">Pesanan Saya</h1>
                        <p class="text-sm text-muted-foreground">
                            Riwayat pesanan Anda di {{ store.name }}
                        </p>
                    </div>
                </div>

                <div v-if="orders.data.length > 0" class="space-y-4">
                    <div
                        v-for="order in orders.data"
                        :key="order.id"
                        class="overflow-hidden rounded-2xl border border-border bg-card shadow-xs"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-muted/30 px-5 py-3.5">
                            <div class="flex flex-wrap items-center gap-3 text-xs sm:text-sm">
                                <Link
                                    :href="`/my-orders/${order.order_number}`"
                                    class="font-mono font-semibold text-foreground hover:text-primary hover:underline"
                                >
                                    {{ order.order_number }}
                                </Link>
                                <span v-if="order.created_at" class="text-muted-foreground">
                                    • {{ order.created_at }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge :variant="statusVariant(order.status)">
                                    {{ statusLabel(order.status) }}
                                </Badge>
                                <Link :href="`/my-orders/${order.order_number}`">
                                    <Button variant="outline" size="sm" class="h-7 px-2.5 text-xs">
                                        Detail
                                    </Button>
                                </Link>
                            </div>
                        </div>

                        <div class="divide-y divide-border px-5">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="flex items-center justify-between gap-4 py-3.5"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
                                        <img
                                            v-if="item.product_image_url"
                                            :src="item.product_image_url"
                                            :alt="item.product_name"
                                            class="h-full w-full object-cover"
                                        />
                                        <Package v-else class="h-5 w-5 text-muted-foreground/60" />
                                    </div>
                                    <div class="min-w-0">
                                        <Link
                                            v-if="item.product_slug"
                                            :href="`/products/${item.product_slug}`"
                                            class="block truncate text-sm font-semibold text-foreground hover:text-primary"
                                        >
                                            {{ item.product_name }}
                                        </Link>
                                        <span v-else class="block truncate text-sm font-semibold text-foreground">
                                            {{ item.product_name }}
                                        </span>
                                        <span class="text-xs text-muted-foreground">
                                            {{ item.quantity }} x {{ formatRupiah(item.price) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="shrink-0 text-sm font-semibold text-foreground">
                                    {{ formatRupiah(item.total_price) }}
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border bg-muted/20 px-5 py-3 text-xs sm:text-sm">
                            <div class="text-muted-foreground">
                                <span v-if="order.payment_method">{{ order.payment_method }}</span>
                                <span v-if="order.courier"> • Kurir: {{ order.courier }}</span>
                            </div>
                            <div class="font-semibold text-foreground">
                                Total: <span class="text-base text-primary">{{ formatRupiah(order.total_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="orders.last_page > 1" class="flex flex-wrap justify-center gap-1.5 pt-4">
                        <button
                            v-for="(link, index) in orders.links"
                            :key="index"
                            type="button"
                            :disabled="!link.url || link.active"
                            @click="visitPage(link.url)"
                            class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-sm font-medium transition-colors"
                            :class="[
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : link.url
                                      ? 'border-border bg-card text-foreground hover:bg-muted'
                                      : 'cursor-not-allowed border-border/50 bg-muted/30 text-muted-foreground opacity-50',
                            ]"
                        >
                            <span v-html="link.label" />
                        </button>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/20 px-6 py-16 text-center"
                >
                    <ShoppingBag class="mb-3 h-12 w-12 text-muted-foreground/50" />
                    <h3 class="text-base font-semibold text-foreground">Belum Ada Pesanan</h3>
                    <p class="mt-1 max-w-md text-sm text-muted-foreground">
                        Anda belum memiliki riwayat pesanan. Mulai jelajahi produk pilihan kami!
                    </p>
                    <Link href="/products" class="mt-5">
                        <Button>Belanja Sekarang</Button>
                    </Link>
                </div>
            </div>
        </StorefrontShell>
    </div>
</template>
