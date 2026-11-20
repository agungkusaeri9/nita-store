<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Flame,
    Globe,
    Grid2X2,
    LayoutGrid,
    Mail,
    MapPin,
    MessageCircle,
    Package,
    Phone,
    ShieldCheck,
    ShoppingBag,
    Sparkles,
    Store,
    Truck,
    X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import ProductCard from '@/components/user/ProductCard.vue';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import type {
    PaginatedProducts,
    StoreBanner,
    StoreCategory,
    StoreIdentity,
    StoreProduct,
} from '@/types/storefront';

interface Props {
    store: StoreIdentity;
    banners: StoreBanner[];
    categories: StoreCategory[];
    recommendedProducts: StoreProduct[];
    bestSellerProducts: StoreProduct[];
    products: PaginatedProducts;
    filters: {
        search: string;
        category: string;
        sort: string;
    };
}

const props = defineProps<Props>();

const activeCategory = ref(props.filters.category ?? '');
const activeSort = ref(props.filters.sort ?? 'latest');

watch(
    () => props.filters,
    (newFilters) => {
        activeCategory.value = newFilters.category ?? '';
        activeSort.value = newFilters.sort ?? 'latest';
    },
);

const isFiltered = computed(
    () => Boolean(props.filters.search) || Boolean(props.filters.category),
);

const featuredCategories = computed(() => props.categories.slice(0, 9));

const selectedCategoryObj = computed(
    () => props.categories.find((cat) => cat.slug === activeCategory.value) ?? null,
);

const storeLogo = computed(() => props.store.logo_url || '/images/logo.png');

const formatWhatsappUrl = (phone: string | null, text?: string): string => {
    if (!phone) {
        return '#';
    }

    const digits = phone.replace(/\D/g, '');
    const normalized = digits.startsWith('0') ? `62${digits.slice(1)}` : digits;
    const base = `https://wa.me/${normalized}`;

    return text ? `${base}?text=${encodeURIComponent(text)}` : base;
};

// Catalog Filtering & Navigation
const applyCatalogFilter = (scrollToCatalog = true): void => {
    const query: Record<string, string> = {};

    if (props.filters.search) {
        query.search = props.filters.search;
    }

    if (activeCategory.value) {
        query.category = activeCategory.value;
    }

    if (activeSort.value && activeSort.value !== 'latest') {
        query.sort = activeSort.value;
    }

    router.get('/', query, {
        preserveState: true,
        preserveScroll: !scrollToCatalog,
        replace: true,
        onSuccess: () => {
            if (scrollToCatalog) {
                document.getElementById('katalog')?.scrollIntoView({ behavior: 'smooth' });
            }
        },
    });
};

const clearFilters = (): void => {
    activeCategory.value = '';
    activeSort.value = 'latest';
    router.get('/', {}, { preserveState: true, preserveScroll: true, replace: true });
};

const visitPage = (url: string | null): void => {
    if (!url) {
        return;
    }

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            document.getElementById('katalog')?.scrollIntoView({ behavior: 'smooth' });
        },
    });
};

// Banner Carousel
const currentSlide = ref(0);
let slideTimer: ReturnType<typeof setInterval> | null = null;

const totalSlides = computed(() => props.banners.length);

const nextSlide = (): void => {
    if (totalSlides.value <= 1) {
        return;
    }

    currentSlide.value = (currentSlide.value + 1) % totalSlides.value;
};

const prevSlide = (): void => {
    if (totalSlides.value <= 1) {
        return;
    }

    currentSlide.value = (currentSlide.value - 1 + totalSlides.value) % totalSlides.value;
};

const startAutoSlide = (): void => {
    stopAutoSlide();

    if (totalSlides.value > 1) {
        slideTimer = setInterval(nextSlide, 5000);
    }
};

const stopAutoSlide = (): void => {
    if (slideTimer) {
        clearInterval(slideTimer);
        slideTimer = null;
    }
};

onMounted(startAutoSlide);
onBeforeUnmount(stopAutoSlide);

const failedBannerImages = ref<Record<number, boolean>>({});
const failedCategoryImages = ref<Record<number, boolean>>({});

const handleBannerAction = (banner: StoreBanner): void => {
    if (banner.target_type === 'category' && banner.category_slug) {
        router.get('/products', { category: banner.category_slug });
        return;
    }

    if (banner.target_type === 'product') {
        if (banner.product_slug) {
            router.get(`/products/${banner.product_slug}`);
            return;
        }

        if (banner.product_name) {
            router.get('/products', { search: banner.product_name });
        }

        return;
    }

    if (banner.target_type === 'url' && banner.target_url) {
        window.open(banner.target_url, '_blank', 'noopener,noreferrer');
        return;
    }

    router.get('/products');
};
</script>

<template>
    <div>
        <Head :title="`${store.name} - Toko Online Resmi`" />

        <StorefrontShell :store="store" :initial-search="filters.search" active-nav="home">
            <!-- HERO BANNER CAROUSEL (DARI DATABASE) -->
            <section class="mx-auto max-w-7xl px-4 pt-5 sm:px-6 lg:px-8">
                <div
                    v-if="banners.length > 0"
                    class="relative overflow-hidden rounded-2xl border border-border bg-muted shadow-sm"
                    @mouseenter="stopAutoSlide"
                    @mouseleave="startAutoSlide"
                >
                    <div
                        class="flex transition-transform duration-500 ease-out"
                        :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
                    >
                        <div
                            v-for="banner in banners"
                            :key="banner.id"
                            class="relative min-w-full"
                        >
                            <div class="relative h-[240px] w-full sm:h-[340px] lg:h-[420px]">
                                <img
                                    v-if="banner.image_url && !failedBannerImages[banner.id]"
                                    :src="banner.image_url"
                                    :alt="banner.title"
                                    @error="failedBannerImages[banner.id] = true"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="h-full w-full bg-linear-to-br from-primary/90 via-primary/70 to-zinc-900"
                                />

                                <!-- Overlay Content -->
                                <div class="absolute inset-0 flex items-end bg-linear-to-t from-black/80 via-black/35 to-transparent p-5 sm:items-center sm:bg-linear-to-r sm:from-black/80 sm:via-black/45 sm:to-transparent sm:p-10 lg:p-14">
                                    <div class="max-w-xl space-y-2.5 text-white sm:space-y-4">
                                        <Badge
                                            variant="secondary"
                                            class="bg-white/20 text-white backdrop-blur-xs hover:bg-white/25"
                                        >
                                            Promo Spesial {{ store.name }}
                                        </Badge>
                                        <h1 class="text-xl leading-tight font-bold sm:text-3xl lg:text-4xl">
                                            {{ banner.title }}
                                        </h1>
                                        <p
                                            v-if="banner.description"
                                            class="line-clamp-2 text-xs text-white/85 sm:line-clamp-3 sm:text-sm lg:text-base"
                                        >
                                            {{ banner.description }}
                                        </p>
                                        <div class="pt-1">
                                            <Button
                                                size="sm"
                                                variant="secondary"
                                                class="gap-1.5 rounded-full font-semibold sm:h-10 sm:px-6"
                                                @click="handleBannerAction(banner)"
                                            >
                                                <span v-if="banner.target_type === 'category' && banner.category_name">
                                                    Jelajahi {{ banner.category_name }}
                                                </span>
                                                <span v-else-if="banner.target_type === 'product'">
                                                    Lihat Produk
                                                </span>
                                                <span v-else-if="banner.target_type === 'url'">
                                                    Selengkapnya
                                                </span>
                                                <span v-else>Belanja Sekarang</span>
                                                <ChevronRight class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carousel Controls -->
                    <template v-if="banners.length > 1">
                        <button
                            type="button"
                            @click="prevSlide"
                            class="absolute top-1/2 left-3 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-xs transition hover:bg-black/65"
                            aria-label="Banner sebelumnya"
                        >
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <button
                            type="button"
                            @click="nextSlide"
                            class="absolute top-1/2 right-3 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-xs transition hover:bg-black/65"
                            aria-label="Banner berikutnya"
                        >
                            <ChevronRight class="h-5 w-5" />
                        </button>

                        <div class="absolute right-0 bottom-3 left-0 flex justify-center gap-2">
                            <button
                                v-for="(banner, index) in banners"
                                :key="banner.id"
                                type="button"
                                @click="currentSlide = index"
                                class="h-2 rounded-full transition-all"
                                :class="currentSlide === index ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"
                                :aria-label="`Pilih banner ${index + 1}`"
                            />
                        </div>
                    </template>
                </div>

                <!-- Fallback Hero -->
                <div
                    v-else
                    class="relative overflow-hidden rounded-2xl border border-border bg-linear-to-r from-zinc-900 via-zinc-800 to-primary/90 p-8 text-white shadow-sm sm:p-12"
                >
                    <div class="max-w-2xl space-y-4">
                        <Badge variant="secondary" class="bg-white/15 text-white">
                            Selamat Datang di {{ store.name }}
                        </Badge>
                        <h1 class="text-2xl font-bold tracking-tight sm:text-4xl">
                            Koleksi Produk Pilihan Terbaik Untuk Anda
                        </h1>
                        <p v-if="store.description" class="text-sm text-white/85 sm:text-base">
                            {{ store.description }}
                        </p>
                        <div class="pt-2">
                            <Link href="/products">
                                <Button variant="secondary" class="rounded-full font-semibold">
                                    Jelajahi Produk
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- KATEGORI PRODUK -->
            <section id="kategori" class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <LayoutGrid class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold tracking-tight sm:text-xl">Kategori Produk</h2>
                            <p class="text-xs text-muted-foreground sm:text-sm">
                                Temukan produk berdasarkan kategori pilihan
                            </p>
                        </div>
                    </div>
                    <Link href="/categories" class="text-xs font-semibold text-primary hover:underline sm:text-sm">
                        Semua Kategori →
                    </Link>
                </div>

                <div class="grid grid-cols-4 gap-2.5 sm:grid-cols-5 lg:grid-flow-col lg:auto-cols-fr lg:gap-3">
                    <Link
                        v-for="category in featuredCategories"
                        :key="category.id"
                        :href="`/products?category=${category.slug}`"
                        class="group flex min-w-0 flex-col items-center justify-center gap-1.5 rounded-xl border border-border bg-card p-2.5 text-center transition-all hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-xs sm:p-3"
                    >
                        <div class="h-10 w-10 overflow-hidden rounded-full border border-border bg-muted">
                            <img
                                v-if="category.image_url && !failedCategoryImages[category.id]"
                                :src="category.image_url"
                                :alt="category.name"
                                @error="failedCategoryImages[category.id] = true"
                                class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-muted-foreground group-hover:text-primary"
                            >
                                <Package class="h-4 w-4" />
                            </div>
                        </div>
                        <span class="w-full truncate text-xs font-semibold text-foreground group-hover:text-primary">
                            {{ category.name }}
                        </span>
                    </Link>

                    <Link
                        href="/categories"
                        class="group flex min-w-0 flex-col items-center justify-center gap-1.5 rounded-xl border border-dashed border-primary/40 bg-primary/5 p-2.5 text-center transition-all hover:-translate-y-0.5 hover:bg-primary/10 hover:shadow-xs sm:p-3"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/15 text-primary">
                            <Grid2X2 class="h-4 w-4" />
                        </div>
                        <span class="w-full truncate text-xs font-semibold text-primary">
                            Semua Kategori
                        </span>
                    </Link>
                </div>
            </section>

            <!-- PRODUK REKOMENDASI -->
            <section
                v-if="recommendedProducts.length > 0 && !isFiltered"
                id="rekomendasi"
                class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8"
            >
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <Sparkles class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold tracking-tight sm:text-xl">Produk Rekomendasi</h2>
                            <p class="text-xs text-muted-foreground sm:text-sm">
                                Pilihan spesial dengan rating dan kualitas terbaik
                            </p>
                        </div>
                    </div>
                    <Link href="/products?sort=highest_rated" class="text-xs font-semibold text-primary hover:underline sm:text-sm">
                        Lihat Semua →
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-3.5 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                    <ProductCard
                        v-for="product in recommendedProducts"
                        :key="`rec-${product.id}`"
                        :product="product"
                        highlight-badge="Rekomendasi"
                    />
                </div>
            </section>

            <!-- PRODUK TERLARIS -->
            <section
                v-if="bestSellerProducts.length > 0 && !isFiltered"
                id="terlaris"
                class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8"
            >
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400">
                            <Flame class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold tracking-tight sm:text-xl">Produk Terlaris</h2>
                            <p class="text-xs text-muted-foreground sm:text-sm">
                                Produk favorit yang paling banyak dibeli pelanggan
                            </p>
                        </div>
                    </div>
                    <Link href="/products?sort=best_selling" class="text-xs font-semibold text-primary hover:underline sm:text-sm">
                        Lihat Semua →
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-3.5 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                    <ProductCard
                        v-for="product in bestSellerProducts"
                        :key="`best-${product.id}`"
                        :product="product"
                        highlight-badge="Terlaris"
                    />
                </div>
            </section>

            <!-- PRODUK LAINNYA / KATALOG -->
            <section id="katalog" class="mx-auto max-w-7xl px-4 pt-12 pb-14 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <ShoppingBag class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold tracking-tight sm:text-xl">
                                {{ isFiltered ? 'Hasil Pencarian Produk' : 'Produk Lainnya' }}
                            </h2>
                            <p class="text-xs text-muted-foreground sm:text-sm">
                                Menampilkan {{ products.total }} produk tersedia
                                <span v-if="selectedCategoryObj">
                                    dalam kategori <strong>{{ selectedCategoryObj.name }}</strong>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <Button
                            v-if="isFiltered"
                            variant="outline"
                            size="sm"
                            @click="clearFilters"
                            class="gap-1.5"
                        >
                            <X class="h-3.5 w-3.5" />
                            Reset Filter
                        </Button>

                        <Select v-model="activeSort" @update:model-value="applyCatalogFilter(true)">
                            <SelectTrigger class="h-9 w-[180px]">
                                <SelectValue placeholder="Urutkan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="latest">Terbaru</SelectItem>
                                <SelectItem value="best_selling">Paling Laris</SelectItem>
                                <SelectItem value="price_asc">Harga Terendah</SelectItem>
                                <SelectItem value="price_desc">Harga Tertinggi</SelectItem>
                            </SelectContent>
                        </Select>

                        <Link href="/products">
                            <Button variant="outline" size="sm">Filter Lengkap</Button>
                        </Link>
                    </div>
                </div>

                <div
                    v-if="products.data.length > 0"
                    class="grid grid-cols-2 gap-3.5 sm:gap-5 md:grid-cols-3 lg:grid-cols-4"
                >
                    <ProductCard
                        v-for="product in products.data"
                        :key="`cat-${product.id}`"
                        :product="product"
                    />
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/20 px-6 py-16 text-center"
                >
                    <Package class="mb-3 h-12 w-12 text-muted-foreground/50" />
                    <h3 class="text-base font-semibold text-foreground">Produk Tidak Ditemukan</h3>
                    <p class="mt-1 max-w-md text-sm text-muted-foreground">
                        Maaf, tidak ada produk yang sesuai dengan kategori yang dipilih.
                    </p>
                    <Button class="mt-5" variant="outline" @click="clearFilters">
                        Tampilkan Semua Produk
                    </Button>
                </div>

                <div
                    v-if="products.last_page > 1"
                    class="mt-8 flex flex-wrap items-center justify-center gap-1.5"
                >
                    <button
                        v-for="(link, index) in products.links"
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
            </section>

            <!-- IDENTITAS TOKO -->
            <section id="identitas-toko" class="border-t border-border bg-muted/35 py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-xs sm:p-8 lg:p-10">
                        <div class="grid gap-8 lg:grid-cols-12 lg:items-center">
                            <div class="space-y-4 lg:col-span-7">
                                <div class="flex items-center gap-4">
                                    <img
                                        :src="storeLogo"
                                        :alt="store.name"
                                        class="h-14 w-auto max-w-[160px] rounded-lg border border-border bg-background p-1.5 object-contain"
                                    />
                                    <div>
                                        <div class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-wider text-primary uppercase">
                                            <Store class="h-3.5 w-3.5" />
                                            Identitas Toko
                                        </div>
                                        <h2 class="text-xl font-bold text-foreground sm:text-2xl">
                                            {{ store.name }}
                                        </h2>
                                    </div>
                                </div>

                                <p v-if="store.description" class="text-sm leading-relaxed text-muted-foreground sm:text-base">
                                    {{ store.description }}
                                </p>

                                <div class="flex flex-wrap gap-3 pt-1">
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                                        <ShieldCheck class="h-3.5 w-3.5" />
                                        Produk Original & Berkualitas
                                    </div>
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                        <Truck class="h-3.5 w-3.5" />
                                        Pengiriman Terpercaya
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 rounded-xl border border-border bg-muted/30 p-5 text-sm lg:col-span-5">
                                <div v-if="store.address" class="flex items-start gap-3">
                                    <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                    <div>
                                        <div class="font-semibold text-foreground">Alamat Toko</div>
                                        <div class="text-muted-foreground">{{ store.address }}</div>
                                    </div>
                                </div>
                                <div v-if="store.phone" class="flex items-center gap-3">
                                    <Phone class="h-4 w-4 shrink-0 text-primary" />
                                    <div>
                                        <span class="font-semibold text-foreground">Telepon: </span>
                                        <span class="text-muted-foreground">{{ store.phone }}</span>
                                    </div>
                                </div>
                                <div v-if="store.email" class="flex items-center gap-3">
                                    <Mail class="h-4 w-4 shrink-0 text-primary" />
                                    <div>
                                        <span class="font-semibold text-foreground">Email: </span>
                                        <a :href="`mailto:${store.email}`" class="text-primary hover:underline">
                                            {{ store.email }}
                                        </a>
                                    </div>
                                </div>
                                <div v-if="store.website" class="flex items-center gap-3">
                                    <Globe class="h-4 w-4 shrink-0 text-primary" />
                                    <div>
                                        <span class="font-semibold text-foreground">Website: </span>
                                        <a
                                            :href="store.website"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-primary hover:underline"
                                        >
                                            {{ store.website }}
                                        </a>
                                    </div>
                                </div>

                                <div v-if="store.whatsapp" class="pt-2">
                                    <a
                                        :href="formatWhatsappUrl(store.whatsapp, `Halo ${store.name}, saya ingin berkonsultasi mengenai produk.`)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="block"
                                    >
                                        <Button class="w-full gap-2">
                                            <MessageCircle class="h-4 w-4" />
                                            Hubungi Kami via WhatsApp
                                        </Button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </StorefrontShell>
    </div>
</template>
