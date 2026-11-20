<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Check,
    CheckCircle2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Loader2,
    MessageCircle,
    Minus,
    Package,
    Plus,
    Share2,
    ShieldCheck,
    ShoppingBag,
    ShoppingCart,
    Star,
    Store,
    Truck,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import ProductCard from '@/components/user/ProductCard.vue';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import type {
    PaginatedReviews,
    StoreIdentity,
    StoreProduct,
    StoreRatingStats,
    StoreReview,
} from '@/types/storefront';

interface Props {
    store: StoreIdentity;
    product: StoreProduct;
    ratingStats: StoreRatingStats;
    reviews: PaginatedReviews;
    reviewRatingFilter: string;
    relatedProducts: StoreProduct[];
}

const props = defineProps<Props>();

// Gallery state
const galleryImages = computed(() => {
    const urls: string[] = [];

    if (props.product.primary_image_url) {
        urls.push(props.product.primary_image_url);
    }

    for (const img of props.product.gallery_images) {
        if (img.image_url && !urls.includes(img.image_url)) {
            urls.push(img.image_url);
        }
    }

    return urls;
});

const activeImageIndex = ref(0);
const failedImages = ref<Record<string, boolean>>({});

const activeImageUrl = computed(() => galleryImages.value[activeImageIndex.value] ?? null);

watch(
    () => props.product.id,
    () => {
        activeImageIndex.value = 0;
        failedImages.value = {};
        quantity.value = 1;
        initSelectedVariants();
    },
);

const prevImage = (): void => {
    if (galleryImages.value.length <= 1) {
        return;
    }

    activeImageIndex.value =
        (activeImageIndex.value - 1 + galleryImages.value.length) % galleryImages.value.length;
};

const nextImage = (): void => {
    if (galleryImages.value.length <= 1) {
        return;
    }

    activeImageIndex.value = (activeImageIndex.value + 1) % galleryImages.value.length;
};

// Variant & Quantity Selection
const selectedVariants = ref<Record<string, string>>({});
const quantity = ref(1);

const initSelectedVariants = (): void => {
    const initial: Record<string, string> = {};

    for (const variant of props.product.variants) {
        if (variant.values.length > 0) {
            initial[variant.type] = variant.values[0];
        }
    }

    selectedVariants.value = initial;
};

initSelectedVariants();

const decreaseQty = (): void => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const increaseQty = (): void => {
    if (quantity.value < props.product.stock) {
        quantity.value++;
    }
};

// Formatting & Actions
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

const whatsappOrderUrl = computed(() => {
    if (!props.store.whatsapp) {
        return '#';
    }

    const digits = props.store.whatsapp.replace(/\D/g, '');
    const normalized = digits.startsWith('0') ? `62${digits.slice(1)}` : digits;

    const variantSummary = Object.entries(selectedVariants.value)
        .map(([type, val]) => `${type}: ${val}`)
        .join(', ');

    const lines = [
        `Halo ${props.store.name}, saya ingin memesan produk:`,
        `*${props.product.name}*`,
        variantSummary ? `Varian: ${variantSummary}` : null,
        `Jumlah: ${quantity.value} pcs`,
        `Harga Total: ${formatRupiah(props.product.price * quantity.value)}`,
        typeof window !== 'undefined' ? `Link: ${window.location.href}` : null,
    ].filter(Boolean);

    return `https://wa.me/${normalized}?text=${encodeURIComponent(lines.join('\n'))}`;
});

const page = usePage<any>();
const copied = ref(false);
const addingToCart = ref(false);

const addToCart = (): void => {
    if (addingToCart.value || props.product.is_active === false || props.product.stock <= 0) {
        return;
    }

    if (!page.props.auth?.user) {
        router.visit('/login');
        return;
    }

    const variantSummary = Object.entries(selectedVariants.value)
        .map(([type, val]) => `${type}: ${val}`)
        .join(', ');

    router.post(
        '/cart',
        {
            product_id: props.product.id,
            variant_info: variantSummary || null,
            quantity: quantity.value,
        },
        {
            preserveScroll: true,
            onStart: () => {
                addingToCart.value = true;
            },
            onFinish: () => {
                addingToCart.value = false;
            },
        },
    );
};

const shareProduct = async (): Promise<void> => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        if (navigator.share) {
            await navigator.share({
                title: props.product.name,
                url: window.location.href,
            });
            return;
        }

        await navigator.clipboard.writeText(window.location.href);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        // Ignore cancelled share dialog
    }
};

// Reviews with Star Filter & Show More (limit 10)
const displayedReviews = ref<StoreReview[]>([...props.reviews.data]);
const activeRatingFilter = ref(props.reviewRatingFilter ?? '');
const isLoadingReviews = ref(false);
const isAppendingReviews = ref(false);

watch(
    () => props.reviewRatingFilter,
    (val) => {
        activeRatingFilter.value = val ?? '';
    },
);

watch(
    () => props.reviews,
    (newReviews) => {
        if (isAppendingReviews.value && newReviews.current_page > 1) {
            const existingIds = new Set(displayedReviews.value.map((r) => r.id));
            const nextItems = newReviews.data.filter((r) => !existingIds.has(r.id));
            displayedReviews.value = [...displayedReviews.value, ...nextItems];
        } else {
            displayedReviews.value = [...newReviews.data];
        }

        isAppendingReviews.value = false;
    },
);

const hasMoreReviews = computed(
    () => props.reviews.current_page < props.reviews.last_page,
);

const remainingReviewsCount = computed(() =>
    Math.max(0, props.reviews.total - displayedReviews.value.length),
);

const filterReviewsByRating = (rating: string): void => {
    const nextRating = activeRatingFilter.value === rating ? '' : rating;
    activeRatingFilter.value = nextRating;
    isAppendingReviews.value = false;

    router.get(
        `/products/${props.product.slug}`,
        nextRating ? { rating: nextRating } : {},
        {
            only: ['reviews', 'reviewRatingFilter'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const loadMoreReviews = (): void => {
    if (!hasMoreReviews.value || isLoadingReviews.value) {
        return;
    }

    isAppendingReviews.value = true;

    const query: Record<string, string | number> = {
        page: props.reviews.current_page + 1,
    };

    if (activeRatingFilter.value) {
        query.rating = activeRatingFilter.value;
    }

    router.get(`/products/${props.product.slug}`, query, {
        only: ['reviews', 'reviewRatingFilter'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => {
            isLoadingReviews.value = true;
        },
        onFinish: () => {
            isLoadingReviews.value = false;
        },
    });
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <div>
        <Head :title="`${product.name} - ${store.name}`" />

        <StorefrontShell :store="store" active-nav="products">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="mb-6 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                    <Link href="/" class="hover:text-primary">Beranda</Link>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <Link href="/products" class="hover:text-primary">Katalog Produk</Link>
                    <template v-if="product.category">
                        <ChevronRight class="h-3.5 w-3.5" />
                        <Link
                            :href="`/products?category=${product.category.slug}`"
                            class="hover:text-primary"
                        >
                            {{ product.category.name }}
                        </Link>
                    </template>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <span class="truncate font-medium text-foreground">{{ product.name }}</span>
                </nav>

                <!-- BAGIAN UTAMA: GALERI FOTO & INFORMASI PRODUK -->
                <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
                    <!-- Kiri: Foto Utama & Galeri Foto -->
                    <div class="space-y-3 lg:col-span-5">
                        <div class="relative aspect-square overflow-hidden rounded-2xl border border-border bg-muted shadow-xs">
                            <img
                                v-if="activeImageUrl && !failedImages[activeImageUrl]"
                                :src="activeImageUrl"
                                :alt="product.name"
                                @error="failedImages[activeImageUrl] = true"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full flex-col items-center justify-center gap-3 text-muted-foreground/50"
                            >
                                <Package class="h-16 w-16" />
                                <span class="text-sm">Foto produk belum tersedia</span>
                            </div>

                            <!-- Prev/Next Gallery Arrows -->
                            <template v-if="galleryImages.length > 1">
                                <button
                                    type="button"
                                    @click="prevImage"
                                    class="absolute top-1/2 left-3 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-xs transition hover:bg-black/65"
                                    aria-label="Foto sebelumnya"
                                >
                                    <ChevronLeft class="h-5 w-5" />
                                </button>
                                <button
                                    type="button"
                                    @click="nextImage"
                                    class="absolute top-1/2 right-3 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-xs transition hover:bg-black/65"
                                    aria-label="Foto berikutnya"
                                >
                                    <ChevronRight class="h-5 w-5" />
                                </button>
                            </template>

                            <!-- Out of Stock Overlay -->
                            <div
                                v-if="product.stock <= 0"
                                class="absolute inset-0 flex items-center justify-center bg-black/50 backdrop-blur-[1px]"
                            >
                                <span class="rounded-full bg-white px-4 py-1.5 text-sm font-semibold text-zinc-900">
                                    Stok Habis
                                </span>
                            </div>
                        </div>

                        <!-- Galeri Thumbnail -->
                        <div
                            v-if="galleryImages.length > 1"
                            class="grid grid-cols-5 gap-2.5 sm:grid-cols-6"
                        >
                            <button
                                v-for="(imgUrl, idx) in galleryImages"
                                :key="idx"
                                type="button"
                                @click="activeImageIndex = idx"
                                class="aspect-square overflow-hidden rounded-xl border-2 bg-muted transition"
                                :class="
                                    activeImageIndex === idx
                                        ? 'border-primary ring-2 ring-primary/20'
                                        : 'border-border opacity-70 hover:opacity-100'
                                "
                            >
                                <img
                                    v-if="!failedImages[imgUrl]"
                                    :src="imgUrl"
                                    :alt="`${product.name} - ${idx + 1}`"
                                    @error="failedImages[imgUrl] = true"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-muted-foreground/50"
                                >
                                    <Package class="h-5 w-5" />
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Kanan: Detail Produk, Varian, Harga, & Tombol Aksi -->
                    <div class="space-y-6 lg:col-span-7">
                        <div class="space-y-3 rounded-2xl border border-border bg-card p-6 shadow-xs">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <Link
                                    v-if="product.category"
                                    :href="`/products?category=${product.category.slug}`"
                                >
                                    <Badge variant="secondary" class="hover:bg-primary/15">
                                        {{ product.category.name }}
                                    </Badge>
                                </Link>

                                <span v-if="product.sku" class="font-mono text-xs text-muted-foreground">
                                    SKU: {{ product.sku }}
                                </span>
                            </div>

                            <h1 class="text-2xl leading-tight font-bold tracking-tight text-foreground sm:text-3xl">
                                {{ product.name }}
                            </h1>

                            <!-- Rating & Terjual -->
                            <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                <a
                                    href="#ulasan"
                                    class="inline-flex items-center gap-1.5 hover:text-primary"
                                >
                                    <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
                                    <span class="font-semibold text-foreground">
                                        {{ ratingStats.average_rating > 0 ? ratingStats.average_rating.toFixed(1) : 'Belum ada rating' }}
                                    </span>
                                    <span v-if="ratingStats.total_count > 0">
                                        ({{ ratingStats.total_count }} ulasan)
                                    </span>
                                </a>
                                <span>•</span>
                                <span class="inline-flex items-center gap-1.5">
                                    <ShoppingBag class="h-4 w-4" />
                                    Terjual <strong class="text-foreground">{{ product.sold_count }}</strong>
                                </span>
                                <span>•</span>
                                <Badge :variant="product.stock > 0 ? 'outline' : 'destructive'">
                                    {{ product.stock > 0 ? `Stok Tersedia: ${product.stock}` : 'Stok Habis' }}
                                </Badge>
                            </div>

                            <Separator />

                            <!-- Harga -->
                            <div class="py-1">
                                <div class="text-3xl font-extrabold tracking-tight text-primary sm:text-4xl">
                                    {{ formatRupiah(product.price) }}
                                </div>
                            </div>

                            <!-- Pilihan Varian -->
                            <div v-if="product.variants.length > 0" class="space-y-4 pt-2">
                                <div
                                    v-for="variant in product.variants"
                                    :key="variant.type"
                                    class="space-y-2"
                                >
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-semibold text-foreground">{{ variant.type }}</span>
                                        <span class="text-xs text-primary font-medium">
                                            {{ selectedVariants[variant.type] }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="val in variant.values"
                                            :key="val"
                                            type="button"
                                            @click="selectedVariants[variant.type] = val"
                                            class="rounded-lg border px-3.5 py-1.5 text-sm font-medium transition"
                                            :class="
                                                selectedVariants[variant.type] === val
                                                    ? 'border-primary bg-primary text-primary-foreground shadow-xs'
                                                    : 'border-border bg-background text-foreground hover:border-primary/50'
                                            "
                                        >
                                            {{ val }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Jumlah & Tombol Pemesanan -->
                            <div class="space-y-4 pt-3">
                                <div class="flex flex-wrap items-center gap-4">
                                    <span class="text-sm font-semibold text-foreground">Jumlah</span>
                                    <div class="inline-flex items-center rounded-lg border border-border bg-background">
                                        <button
                                            type="button"
                                            @click="decreaseQty"
                                            :disabled="quantity <= 1 || product.stock <= 0"
                                            class="flex h-9 w-9 items-center justify-center text-foreground transition hover:bg-muted disabled:opacity-40"
                                            aria-label="Kurangi jumlah"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </button>
                                        <span class="min-w-10 text-center text-sm font-semibold">
                                            {{ product.stock > 0 ? quantity : 0 }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="increaseQty"
                                            :disabled="quantity >= product.stock || product.stock <= 0"
                                            class="flex h-9 w-9 items-center justify-center text-foreground transition hover:bg-muted disabled:opacity-40"
                                            aria-label="Tambah jumlah"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <span v-if="product.stock > 0" class="text-xs text-muted-foreground">
                                        Subtotal:
                                        <strong class="text-foreground">
                                            {{ formatRupiah(product.price * quantity) }}
                                        </strong>
                                    </span>
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row">
                                    <Button
                                        type="button"
                                        size="lg"
                                        :disabled="addingToCart || product.is_active === false || product.stock <= 0"
                                        @click="addToCart"
                                        class="flex-1 gap-2 font-semibold"
                                    >
                                        <Loader2 v-if="addingToCart" class="h-4 w-4 animate-spin" />
                                        <ShoppingCart v-else class="h-4 w-4" />
                                        {{ product.stock <= 0 ? 'Stok Habis' : 'Tambah ke Keranjang' }}
                                    </Button>

                                    <a
                                        v-if="store.whatsapp && product.stock > 0"
                                        :href="whatsappOrderUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex-1"
                                    >
                                        <Button variant="outline" size="lg" class="w-full gap-2 font-semibold">
                                            <MessageCircle class="h-4 w-4" />
                                            Pesan via WhatsApp
                                        </Button>
                                    </a>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="lg"
                                        @click="shareProduct"
                                        class="gap-2"
                                    >
                                        <Check v-if="copied" class="h-4 w-4 text-emerald-600" />
                                        <Share2 v-else class="h-4 w-4" />
                                        <span>{{ copied ? 'Link Disalin' : 'Bagikan' }}</span>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Jaminan & Info Pengiriman -->
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="flex items-start gap-3 rounded-xl border border-border bg-card p-3.5 text-xs">
                                <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                <div>
                                    <div class="font-semibold text-foreground">Produk Original</div>
                                    <div class="text-muted-foreground">Dijamin kualitas resmi {{ store.name }}</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 rounded-xl border border-border bg-card p-3.5 text-xs">
                                <Truck class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                <div>
                                    <div class="font-semibold text-foreground">Berat {{ formatWeight(product.weight) }}</div>
                                    <div class="text-muted-foreground">Pengiriman aman ke seluruh Indonesia</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 rounded-xl border border-border bg-card p-3.5 text-xs">
                                <Store class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                <div>
                                    <div class="font-semibold text-foreground">Respon Cepat</div>
                                    <div class="text-muted-foreground">Konsultasi langsung via WhatsApp</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DESKRIPSI & SPESIFIKASI PRODUK -->
                <div class="mt-10 grid gap-6 lg:grid-cols-12">
                    <div class="rounded-2xl border border-border bg-card p-6 shadow-xs lg:col-span-8">
                        <h2 class="mb-4 text-lg font-bold text-foreground">Deskripsi Produk</h2>
                        <div class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground sm:text-base">
                            {{ product.description || 'Belum ada deskripsi lengkap untuk produk ini.' }}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border bg-card p-6 shadow-xs lg:col-span-4">
                        <h2 class="mb-4 text-lg font-bold text-foreground">Spesifikasi</h2>
                        <dl class="divide-y divide-border text-sm">
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-muted-foreground">Kategori</dt>
                                <dd class="font-medium text-foreground">{{ product.category?.name || '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-muted-foreground">SKU</dt>
                                <dd class="font-mono text-foreground">{{ product.sku || '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-muted-foreground">Berat</dt>
                                <dd class="font-medium text-foreground">{{ formatWeight(product.weight) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-muted-foreground">Stok</dt>
                                <dd class="font-medium text-foreground">{{ product.stock }} pcs</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-muted-foreground">Terjual</dt>
                                <dd class="font-medium text-foreground">{{ product.sold_count }} pcs</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- ULASAN PEMBELI (REVIEWS) + SHOW MORE LIMIT 10 -->
                <section id="ulasan" class="mt-10 rounded-2xl border border-border bg-card p-6 shadow-xs">
                    <div class="mb-6 flex flex-col gap-6 border-b border-border pb-6 lg:flex-row lg:items-center lg:justify-between">
                        <!-- Rating Summary -->
                        <div class="flex items-center gap-5">
                            <div class="text-center">
                                <div class="text-4xl font-extrabold text-foreground">
                                    {{ ratingStats.average_rating.toFixed(1) }}
                                    <span class="text-lg font-normal text-muted-foreground">/ 5.0</span>
                                </div>
                                <div class="mt-1 flex items-center justify-center gap-0.5">
                                    <Star
                                        v-for="s in 5"
                                        :key="s"
                                        class="h-4 w-4"
                                        :class="
                                            s <= Math.round(ratingStats.average_rating)
                                                ? 'fill-amber-400 text-amber-400'
                                                : 'text-muted-foreground/30'
                                        "
                                    />
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ ratingStats.total_count }} ulasan pembeli
                                </p>
                            </div>

                            <!-- Star Breakdown Bars -->
                            <div class="w-48 space-y-1.5 sm:w-64">
                                <button
                                    v-for="star in [5, 4, 3, 2, 1]"
                                    :key="star"
                                    type="button"
                                    @click="filterReviewsByRating(String(star))"
                                    class="flex w-full items-center gap-2 text-xs transition hover:opacity-80"
                                >
                                    <span class="flex w-8 items-center gap-0.5 font-medium text-foreground">
                                        {{ star }}
                                        <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                                    </span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                        <div
                                            class="h-full rounded-full bg-amber-400 transition-all"
                                            :style="{ width: `${ratingStats.breakdown[star]?.percentage ?? 0}%` }"
                                        />
                                    </div>
                                    <span class="w-7 text-right text-muted-foreground">
                                        {{ ratingStats.breakdown[star]?.count ?? 0 }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Filter Rating Pills -->
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="filterReviewsByRating('')"
                                class="rounded-full border px-3.5 py-1.5 text-xs font-medium transition"
                                :class="
                                    activeRatingFilter === ''
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border bg-background text-foreground hover:border-primary/50'
                                "
                            >
                                Semua ({{ ratingStats.total_count }})
                            </button>
                            <button
                                v-for="star in [5, 4, 3, 2, 1]"
                                :key="`pill-${star}`"
                                type="button"
                                @click="filterReviewsByRating(String(star))"
                                class="inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-xs font-medium transition"
                                :class="
                                    activeRatingFilter === String(star)
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border bg-background text-foreground hover:border-primary/50'
                                "
                            >
                                {{ star }}
                                <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                                ({{ ratingStats.breakdown[star]?.count ?? 0 }})
                            </button>
                        </div>
                    </div>

                    <!-- Review List -->
                    <div v-if="displayedReviews.length > 0" class="divide-y divide-border">
                        <article
                            v-for="review in displayedReviews"
                            :key="review.id"
                            class="py-5 first:pt-0 last:pb-0"
                        >
                            <div class="flex items-start gap-3.5">
                                <Avatar class="h-10 w-10 border border-border">
                                    <AvatarImage
                                        v-if="review.customer_avatar"
                                        :src="review.customer_avatar"
                                        :alt="review.customer_name"
                                    />
                                    <AvatarFallback class="text-xs font-semibold">
                                        {{ getInitials(review.customer_name) }}
                                    </AvatarFallback>
                                </Avatar>

                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-semibold text-foreground">
                                                {{ review.customer_name }}
                                            </span>
                                            <span
                                                v-if="review.is_verified_purchase"
                                                class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:text-emerald-400"
                                            >
                                                <CheckCircle2 class="h-3 w-3" />
                                                Pembelian Terverifikasi
                                            </span>
                                        </div>
                                        <span v-if="review.created_at" class="text-xs text-muted-foreground">
                                            {{ review.created_at }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-0.5">
                                        <Star
                                            v-for="s in 5"
                                            :key="s"
                                            class="h-3.5 w-3.5"
                                            :class="
                                                s <= review.rating
                                                    ? 'fill-amber-400 text-amber-400'
                                                    : 'text-muted-foreground/30'
                                            "
                                        />
                                    </div>

                                    <p class="whitespace-pre-line text-sm leading-relaxed text-foreground/90">
                                        {{ review.comment || 'Pelanggan memberikan penilaian bintang.' }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="py-12 text-center text-sm text-muted-foreground">
                        Belum ada ulasan
                        <span v-if="activeRatingFilter"> untuk rating {{ activeRatingFilter }} bintang</span>.
                    </div>

                    <!-- Show More Reviews (Limit 10) -->
                    <div
                        v-if="displayedReviews.length > 0"
                        class="mt-6 flex flex-col items-center justify-center gap-2 border-t border-border pt-6"
                    >
                        <p class="text-xs text-muted-foreground">
                            Menampilkan {{ displayedReviews.length }} dari {{ reviews.total }} ulasan
                        </p>

                        <Button
                            v-if="hasMoreReviews"
                            type="button"
                            variant="outline"
                            :disabled="isLoadingReviews"
                            @click="loadMoreReviews"
                            class="min-w-[220px] gap-2 rounded-full border-primary/40 font-semibold text-primary hover:bg-primary/5"
                        >
                            <Loader2 v-if="isLoadingReviews" class="h-4 w-4 animate-spin" />
                            <ChevronDown v-else class="h-4 w-4" />
                            <span>
                                {{
                                    isLoadingReviews
                                        ? 'Memuat Ulasan...'
                                        : `Tampilkan Lebih Banyak (${remainingReviewsCount} lagi)`
                                }}
                            </span>
                        </Button>
                    </div>
                </section>

                <!-- PRODUK TERKAIT -->
                <section v-if="relatedProducts.length > 0" class="mt-12 pb-6">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold tracking-tight text-foreground sm:text-xl">
                                Produk Terkait
                            </h2>
                            <p class="text-xs text-muted-foreground sm:text-sm">
                                Produk serupa yang mungkin Anda suka
                            </p>
                        </div>
                        <Link
                            :href="product.category ? `/products?category=${product.category.slug}` : '/products'"
                            class="text-xs font-semibold text-primary hover:underline sm:text-sm"
                        >
                            Lihat Lainnya →
                        </Link>
                    </div>

                    <div class="grid grid-cols-2 gap-3.5 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                        <ProductCard
                            v-for="item in relatedProducts"
                            :key="`rel-${item.id}`"
                            :product="item"
                        />
                    </div>
                </section>
            </div>
        </StorefrontShell>
    </div>
</template>
