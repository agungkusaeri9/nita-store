<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Package, ShoppingBag, Star } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { StoreProduct } from '@/types/storefront';

const props = defineProps<{
    product: StoreProduct;
    highlightBadge?: string;
}>();

const imageFailed = ref(false);

watch(
    () => props.product.primary_image_url,
    () => {
        imageFailed.value = false;
    },
);

const formatRupiah = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <Link
        :href="`/products/${product.slug}`"
        class="group flex h-full min-w-0 flex-col overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md"
    >
        <!-- Thumbnail -->
        <div class="relative aspect-square w-full overflow-hidden bg-muted">
            <img
                v-if="product.primary_image_url && !imageFailed"
                :src="product.primary_image_url"
                :alt="product.name"
                loading="lazy"
                @error="imageFailed = true"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div
                v-else
                class="flex h-full w-full flex-col items-center justify-center gap-2 bg-muted/60 text-muted-foreground/50"
            >
                <Package class="h-10 w-10" />
                <span class="text-xs">Belum ada foto</span>
            </div>

            <!-- Highlight Badge -->
            <div v-if="highlightBadge" class="absolute top-2.5 left-2.5">
                <Badge class="bg-primary/95 text-primary-foreground shadow-xs backdrop-blur-xs">
                    {{ highlightBadge }}
                </Badge>
            </div>

            <!-- Out of Stock Overlay -->
            <div
                v-if="product.stock <= 0"
                class="absolute inset-0 flex items-center justify-center bg-black/50 backdrop-blur-[1px]"
            >
                <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-zinc-900">
                    Stok Habis
                </span>
            </div>
        </div>

        <!-- Body -->
        <div class="flex min-w-0 flex-1 flex-col justify-between gap-3 p-3.5 sm:p-4">
            <div class="min-w-0 space-y-1.5">
                <span
                    v-if="product.category"
                    class="block truncate text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                >
                    {{ product.category.name }}
                </span>
                <h3 class="line-clamp-2 text-sm leading-snug font-semibold text-foreground group-hover:text-primary sm:text-base">
                    {{ product.name }}
                </h3>
            </div>

            <div class="space-y-2">
                <div class="truncate text-base font-bold text-primary sm:text-lg">
                    {{ formatRupiah(product.price) }}
                </div>

                <div class="flex flex-wrap items-center justify-between gap-1.5 border-t border-border/60 pt-2 text-xs text-muted-foreground">
                    <div class="flex items-center gap-1 whitespace-nowrap">
                        <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                        <span class="font-medium text-foreground">
                            {{ product.average_rating > 0 ? product.average_rating.toFixed(1) : 'Baru' }}
                        </span>
                        <span v-if="product.reviews_count > 0">({{ product.reviews_count }})</span>
                    </div>
                    <div class="flex items-center gap-1 whitespace-nowrap">
                        <ShoppingBag class="h-3.5 w-3.5" />
                        <span>Terjual {{ product.sold_count }}</span>
                    </div>
                </div>
            </div>
        </div>
    </Link>
</template>
