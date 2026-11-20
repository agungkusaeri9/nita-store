<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronRight,
    Filter,
    Loader2,
    Package,
    RotateCcw,
    Search,
    SlidersHorizontal,
    Star,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import ProductCard from '@/components/user/ProductCard.vue';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import type {
    PaginatedProducts,
    StoreCategory,
    StoreIdentity,
    StoreProduct,
    StoreVariantType,
} from '@/types/storefront';

interface Props {
    store: StoreIdentity;
    categories: StoreCategory[];
    variantTypes: StoreVariantType[];
    priceBounds: {
        min: number;
        max: number;
    };
    products: PaginatedProducts;
    filters: {
        search: string;
        category: string;
        min_price: string;
        max_price: string;
        in_stock: boolean;
        min_rating: string;
        variant_values: number[];
        sort: string;
        per_page: number;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');
const minPrice = ref(props.filters.min_price ?? '');
const maxPrice = ref(props.filters.max_price ?? '');
const inStock = ref(Boolean(props.filters.in_stock));
const minRating = ref(props.filters.min_rating ?? '');
const selectedVariants = ref<number[]>([...(props.filters.variant_values ?? [])]);
const sort = ref(props.filters.sort ?? 'latest');
const isMobileFilterOpen = ref(false);
const displayedProducts = ref<StoreProduct[]>([...props.products.data]);
const isLoadingMore = ref(false);
const isAppendingMore = ref(false);

watch(
    () => props.filters,
    (newFilters) => {
        search.value = newFilters.search ?? '';
        category.value = newFilters.category ?? '';
        minPrice.value = newFilters.min_price ?? '';
        maxPrice.value = newFilters.max_price ?? '';
        inStock.value = Boolean(newFilters.in_stock);
        minRating.value = newFilters.min_rating ?? '';
        selectedVariants.value = [...(newFilters.variant_values ?? [])];
        sort.value = newFilters.sort ?? 'latest';
    },
);

watch(
    () => props.products,
    (newProducts) => {
        if (isAppendingMore.value && newProducts.current_page > 1) {
            const existingIds = new Set(displayedProducts.value.map((item) => item.id));
            const nextItems = newProducts.data.filter((item) => !existingIds.has(item.id));
            displayedProducts.value = [...displayedProducts.value, ...nextItems];
        } else {
            displayedProducts.value = [...newProducts.data];
        }

        isAppendingMore.value = false;
    },
);

const pricePresets = [
    { label: 'Di bawah Rp 250rb', min: '', max: '250000' },
    { label: 'Rp 250rb - 500rb', min: '250000', max: '500000' },
    { label: 'Rp 500rb - 1jt', min: '500000', max: '1000000' },
    { label: 'Di atas Rp 1jt', min: '1000000', max: '' },
];

const formatRupiah = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const activeCategoryObj = computed(
    () => props.categories.find((cat) => cat.slug === category.value) ?? null,
);

const activeFilterCount = computed(() => {
    let count = 0;

    if (search.value.trim()) count++;
    if (category.value) count++;
    if (minPrice.value || maxPrice.value) count++;
    if (inStock.value) count++;
    if (minRating.value) count++;
    if (selectedVariants.value.length > 0) count++;

    return count;
});

const buildFilterQuery = (): Record<string, string | number[] | boolean> => {
    const query: Record<string, string | number[] | boolean> = {};

    if (search.value.trim()) {
        query.search = search.value.trim();
    }

    if (category.value) {
        query.category = category.value;
    }

    if (minPrice.value !== '') {
        query.min_price = minPrice.value;
    }

    if (maxPrice.value !== '') {
        query.max_price = maxPrice.value;
    }

    if (inStock.value) {
        query.in_stock = '1';
    }

    if (minRating.value !== '') {
        query.min_rating = minRating.value;
    }

    if (selectedVariants.value.length > 0) {
        query.variant_values = selectedVariants.value;
    }

    if (sort.value && sort.value !== 'latest') {
        query.sort = sort.value;
    }

    return query;
};

const applyFilters = (): void => {
    isAppendingMore.value = false;
    isMobileFilterOpen.value = false;

    router.get('/products', buildFilterQuery(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleNavbarSearch = (queryText: string): void => {
    search.value = queryText;
    applyFilters();
};

const selectCategory = (slug: string): void => {
    category.value = slug;
    applyFilters();
};

const applyPricePreset = (min: string, max: string): void => {
    minPrice.value = min;
    maxPrice.value = max;
    applyFilters();
};

const isPresetActive = (min: string, max: string): boolean => {
    return minPrice.value === min && maxPrice.value === max;
};

const toggleVariantValue = (id: number): void => {
    if (selectedVariants.value.includes(id)) {
        selectedVariants.value = selectedVariants.value.filter((item) => item !== id);
    } else {
        selectedVariants.value = [...selectedVariants.value, id];
    }

    applyFilters();
};

const selectMinRating = (val: string): void => {
    minRating.value = minRating.value === val ? '' : val;
    applyFilters();
};

const resetAllFilters = (): void => {
    isAppendingMore.value = false;
    search.value = '';
    category.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    inStock.value = false;
    minRating.value = '';
    selectedVariants.value = [];
    sort.value = 'latest';
    isMobileFilterOpen.value = false;

    router.get('/products', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const hasMoreProducts = computed(
    () => props.products.current_page < props.products.last_page,
);

const remainingProductsCount = computed(() =>
    Math.max(0, props.products.total - displayedProducts.value.length),
);

const showMore = (): void => {
    if (!hasMoreProducts.value || isLoadingMore.value) {
        return;
    }

    isAppendingMore.value = true;

    router.get(
        '/products',
        {
            ...buildFilterQuery(),
            page: props.products.current_page + 1,
        },
        {
            only: ['products'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                isLoadingMore.value = true;
            },
            onFinish: () => {
                isLoadingMore.value = false;
            },
        },
    );
};
</script>

<template>
    <div>
        <Head :title="`Katalog Produk - ${store.name}`" />

        <StorefrontShell
            :store="store"
            :initial-search="search"
            active-nav="products"
            @search="handleNavbarSearch"
        >
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <!-- Breadcrumb & Header -->
                <div class="mb-6 space-y-2">
                    <nav class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Link href="/" class="hover:text-primary">Beranda</Link>
                        <ChevronRight class="h-3.5 w-3.5" />
                        <span class="font-medium text-foreground">Katalog Produk</span>
                        <template v-if="activeCategoryObj">
                            <ChevronRight class="h-3.5 w-3.5" />
                            <span class="font-medium text-primary">{{ activeCategoryObj.name }}</span>
                        </template>
                    </nav>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                                Katalog Produk
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                Temukan {{ products.total }} produk terbaik dari {{ store.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)] lg:items-start">
                    <!-- SIDEBAR FILTER DESKTOP -->
                    <aside class="hidden min-w-0 lg:block">
                        <div class="sticky top-24 max-h-[calc(100vh-7rem)] space-y-5 overflow-y-auto rounded-2xl border border-border bg-card p-5 shadow-xs">
                            <div class="flex items-center justify-between border-b border-border pb-3">
                                <div class="flex items-center gap-2 font-semibold text-foreground">
                                    <SlidersHorizontal class="h-4 w-4 text-primary" />
                                    Filter Produk
                                </div>
                                <button
                                    v-if="activeFilterCount > 0"
                                    type="button"
                                    @click="resetAllFilters"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                >
                                    <RotateCcw class="h-3 w-3" />
                                    Reset
                                </button>
                            </div>

                            <!-- Pencarian -->
                            <form @submit.prevent="applyFilters" class="space-y-2">
                                <Label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Cari Produk
                                </Label>
                                <div class="relative">
                                    <Search class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        v-model="search"
                                        type="text"
                                        placeholder="Nama produk / SKU..."
                                        class="h-9 pl-8 pr-8 text-sm"
                                    />
                                    <button
                                        v-if="search"
                                        type="button"
                                        @click="search = ''; applyFilters()"
                                        class="absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </form>

                            <!-- Kategori -->
                            <div class="space-y-2.5 border-t border-border pt-4">
                                <Label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Kategori
                                </Label>
                                <div class="space-y-1">
                                    <button
                                        type="button"
                                        @click="selectCategory('')"
                                        class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-sm transition"
                                        :class="
                                            category === ''
                                                ? 'bg-primary/10 font-semibold text-primary'
                                                : 'text-foreground hover:bg-muted'
                                        "
                                    >
                                        <span>Semua Kategori</span>
                                    </button>
                                    <button
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        type="button"
                                        @click="selectCategory(cat.slug)"
                                        class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-sm transition"
                                        :class="
                                            category === cat.slug
                                                ? 'bg-primary/10 font-semibold text-primary'
                                                : 'text-foreground hover:bg-muted'
                                        "
                                    >
                                        <span class="truncate">{{ cat.name }}</span>
                                        <span class="text-xs text-muted-foreground">{{ cat.products_count }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Harga -->
                            <div class="space-y-3 border-t border-border pt-4">
                                <Label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Rentang Harga
                                </Label>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <Input
                                            v-model="minPrice"
                                            type="number"
                                            min="0"
                                            placeholder="Min (Rp)"
                                            class="h-8 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            v-model="maxPrice"
                                            type="number"
                                            min="0"
                                            placeholder="Max (Rp)"
                                            class="h-8 text-xs"
                                        />
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="secondary"
                                    class="h-8 w-full text-xs"
                                    @click="applyFilters"
                                >
                                    Terapkan Harga
                                </Button>

                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    <button
                                        v-for="preset in pricePresets"
                                        :key="preset.label"
                                        type="button"
                                        @click="applyPricePreset(preset.min, preset.max)"
                                        class="rounded-full border px-2.5 py-1 text-[11px] transition"
                                        :class="
                                            isPresetActive(preset.min, preset.max)
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-border bg-background text-muted-foreground hover:border-primary/50 hover:text-foreground'
                                        "
                                    >
                                        {{ preset.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Ketersediaan Stok -->
                            <div class="space-y-2.5 border-t border-border pt-4">
                                <Label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Ketersediaan
                                </Label>
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-foreground">
                                    <input
                                        v-model="inStock"
                                        type="checkbox"
                                        @change="applyFilters"
                                        class="h-4 w-4 rounded border-border accent-primary"
                                    />
                                    <span>Hanya stok tersedia</span>
                                </label>
                            </div>

                            <!-- Rating Minimal -->
                            <div class="space-y-2.5 border-t border-border pt-4">
                                <Label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Rating Minimal
                                </Label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="ratingOption in ['4', '3']"
                                        :key="ratingOption"
                                        type="button"
                                        @click="selectMinRating(ratingOption)"
                                        class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-medium transition"
                                        :class="
                                            minRating === ratingOption
                                                ? 'border-primary bg-primary/10 text-primary'
                                                : 'border-border bg-background text-muted-foreground hover:border-primary/50'
                                        "
                                    >
                                        <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                        {{ ratingOption }}+ ke atas
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Varian -->
                            <div
                                v-for="vType in variantTypes"
                                :key="vType.id"
                                class="space-y-2 border-t border-border pt-4"
                            >
                                <Label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    {{ vType.name }}
                                </Label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="val in vType.values"
                                        :key="val.id"
                                        type="button"
                                        @click="toggleVariantValue(val.id)"
                                        class="rounded-md border px-2.5 py-1 text-xs transition"
                                        :class="
                                            selectedVariants.includes(val.id)
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-border bg-background text-foreground hover:border-primary/50'
                                        "
                                    >
                                        {{ val.value }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- MAIN CATALOG AREA -->
                    <div class="min-w-0 space-y-5">
                        <!-- Toolbar Atas -->
                        <div class="flex flex-col gap-3 rounded-xl border border-border bg-card p-3.5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Mobile Filter Drawer Trigger -->
                                <Sheet v-model:open="isMobileFilterOpen">
                                    <SheetTrigger as-child>
                                        <Button variant="outline" size="sm" class="gap-1.5 lg:hidden">
                                            <Filter class="h-4 w-4 text-primary" />
                                            Filter
                                            <Badge v-if="activeFilterCount > 0" class="ml-0.5 h-5 px-1.5 text-[10px]">
                                                {{ activeFilterCount }}
                                            </Badge>
                                        </Button>
                                    </SheetTrigger>
                                    <SheetContent side="left" class="w-[300px] overflow-y-auto p-6 sm:w-[340px]">
                                        <SheetHeader>
                                            <SheetTitle>Filter Produk</SheetTitle>
                                            <SheetDescription>
                                                Saring produk sesuai kategori, harga, rating, dan varian.
                                            </SheetDescription>
                                        </SheetHeader>

                                        <div class="mt-5 space-y-5 pb-6">
                                            <!-- Mobile Search -->
                                            <form @submit.prevent="applyFilters" class="space-y-2">
                                                <Label class="text-xs font-semibold uppercase">Cari Produk</Label>
                                                <Input v-model="search" placeholder="Nama produk / SKU..." class="h-9" />
                                            </form>

                                            <!-- Mobile Category -->
                                            <div class="space-y-2">
                                                <Label class="text-xs font-semibold uppercase">Kategori</Label>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <button
                                                        type="button"
                                                        @click="selectCategory('')"
                                                        class="rounded-full border px-3 py-1 text-xs"
                                                        :class="category === '' ? 'border-primary bg-primary text-primary-foreground' : 'border-border'"
                                                    >
                                                        Semua
                                                    </button>
                                                    <button
                                                        v-for="cat in categories"
                                                        :key="cat.id"
                                                        type="button"
                                                        @click="selectCategory(cat.slug)"
                                                        class="rounded-full border px-3 py-1 text-xs"
                                                        :class="category === cat.slug ? 'border-primary bg-primary text-primary-foreground' : 'border-border'"
                                                    >
                                                        {{ cat.name }} ({{ cat.products_count }})
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Mobile Price -->
                                            <div class="space-y-2">
                                                <Label class="text-xs font-semibold uppercase">Rentang Harga</Label>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <Input v-model="minPrice" type="number" min="0" placeholder="Harga Min" class="h-9 text-xs" />
                                                    <Input v-model="maxPrice" type="number" min="0" placeholder="Harga Max" class="h-9 text-xs" />
                                                </div>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <button
                                                        v-for="preset in pricePresets"
                                                        :key="preset.label"
                                                        type="button"
                                                        @click="applyPricePreset(preset.min, preset.max)"
                                                        class="rounded-full border px-2.5 py-1 text-[11px]"
                                                        :class="isPresetActive(preset.min, preset.max) ? 'border-primary bg-primary text-primary-foreground' : 'border-border'"
                                                    >
                                                        {{ preset.label }}
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Mobile Stock & Rating -->
                                            <div class="space-y-3">
                                                <label class="flex items-center gap-2 text-sm">
                                                    <input v-model="inStock" type="checkbox" class="h-4 w-4 accent-primary" />
                                                    Hanya stok tersedia
                                                </label>
                                                <div class="flex gap-2">
                                                    <button
                                                        v-for="ratingOption in ['4', '3']"
                                                        :key="ratingOption"
                                                        type="button"
                                                        @click="selectMinRating(ratingOption)"
                                                        class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs"
                                                        :class="minRating === ratingOption ? 'border-primary bg-primary/10 text-primary' : 'border-border'"
                                                    >
                                                        <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                                        {{ ratingOption }}+
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Mobile Variants -->
                                            <div v-for="vType in variantTypes" :key="vType.id" class="space-y-2">
                                                <Label class="text-xs font-semibold uppercase">{{ vType.name }}</Label>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <button
                                                        v-for="val in vType.values"
                                                        :key="val.id"
                                                        type="button"
                                                        @click="toggleVariantValue(val.id)"
                                                        class="rounded-md border px-2.5 py-1 text-xs"
                                                        :class="selectedVariants.includes(val.id) ? 'border-primary bg-primary text-primary-foreground' : 'border-border'"
                                                    >
                                                        {{ val.value }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="flex gap-2 pt-2">
                                                <Button class="flex-1" @click="applyFilters">Terapkan Filter</Button>
                                                <Button variant="outline" @click="resetAllFilters">Reset</Button>
                                            </div>
                                        </div>
                                    </SheetContent>
                                </Sheet>

                                <span class="text-xs text-muted-foreground sm:text-sm">
                                    Menampilkan
                                    <strong class="text-foreground">{{ displayedProducts.length }}</strong>
                                    dari <strong class="text-foreground">{{ products.total }}</strong> produk
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <Select v-model="sort" @update:model-value="applyFilters">
                                    <SelectTrigger class="h-9 w-[180px] text-xs sm:text-sm">
                                        <SelectValue placeholder="Urutkan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="latest">Terbaru</SelectItem>
                                        <SelectItem value="best_selling">Paling Laris</SelectItem>
                                        <SelectItem value="highest_rated">Rating Tertinggi</SelectItem>
                                        <SelectItem value="price_asc">Harga Terendah</SelectItem>
                                        <SelectItem value="price_desc">Harga Tertinggi</SelectItem>
                                        <SelectItem value="name_asc">Nama A - Z</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Active Filter Chips -->
                        <div v-if="activeFilterCount > 0" class="flex flex-wrap items-center gap-2">
                            <Badge v-if="filters.search" variant="secondary" class="gap-1.5 py-1">
                                Cari: "{{ filters.search }}"
                                <button type="button" @click="search = ''; applyFilters()">
                                    <X class="h-3 w-3" />
                                </button>
                            </Badge>
                            <Badge v-if="activeCategoryObj" variant="secondary" class="gap-1.5 py-1">
                                Kategori: {{ activeCategoryObj.name }}
                                <button type="button" @click="selectCategory('')">
                                    <X class="h-3 w-3" />
                                </button>
                            </Badge>
                            <Badge v-if="filters.min_price || filters.max_price" variant="secondary" class="gap-1.5 py-1">
                                Harga: {{ filters.min_price ? formatRupiah(Number(filters.min_price)) : 'Rp 0' }} -
                                {{ filters.max_price ? formatRupiah(Number(filters.max_price)) : 'Tak terbatas' }}
                                <button type="button" @click="minPrice = ''; maxPrice = ''; applyFilters()">
                                    <X class="h-3 w-3" />
                                </button>
                            </Badge>
                            <Badge v-if="filters.in_stock" variant="secondary" class="gap-1.5 py-1">
                                Stok Tersedia
                                <button type="button" @click="inStock = false; applyFilters()">
                                    <X class="h-3 w-3" />
                                </button>
                            </Badge>
                            <Badge v-if="filters.min_rating" variant="secondary" class="gap-1.5 py-1">
                                Rating {{ filters.min_rating }}+
                                <button type="button" @click="minRating = ''; applyFilters()">
                                    <X class="h-3 w-3" />
                                </button>
                            </Badge>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-7 px-2 text-xs text-primary"
                                @click="resetAllFilters"
                            >
                                Reset Semua
                            </Button>
                        </div>

                        <!-- Product Grid -->
                        <div
                            v-if="displayedProducts.length > 0"
                            class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 sm:gap-5"
                        >
                            <ProductCard
                                v-for="product in displayedProducts"
                                :key="product.id"
                                :product="product"
                            />
                        </div>

                        <!-- Empty State -->
                        <div
                            v-else
                            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/20 px-6 py-16 text-center"
                        >
                            <Package class="mb-3 h-12 w-12 text-muted-foreground/50" />
                            <h3 class="text-base font-semibold text-foreground">Produk Tidak Ditemukan</h3>
                            <p class="mt-1 max-w-md text-sm text-muted-foreground">
                                Tidak ada produk yang cocok dengan kombinasi filter Anda. Coba ubah atau reset filter.
                            </p>
                            <Button class="mt-5" variant="outline" @click="resetAllFilters">
                                Reset Semua Filter
                            </Button>
                        </div>

                        <!-- Show More -->
                        <div
                            v-if="displayedProducts.length > 0"
                            class="flex flex-col items-center justify-center gap-2 pt-4"
                        >
                            <p class="text-xs text-muted-foreground">
                                Menampilkan {{ displayedProducts.length }} dari {{ products.total }} produk
                            </p>

                            <Button
                                v-if="hasMoreProducts"
                                type="button"
                                variant="outline"
                                size="lg"
                                :disabled="isLoadingMore"
                                @click="showMore"
                                class="min-w-[220px] gap-2 rounded-full border-primary/40 font-semibold text-primary hover:bg-primary/5"
                            >
                                <Loader2 v-if="isLoadingMore" class="h-4 w-4 animate-spin" />
                                <ChevronDown v-else class="h-4 w-4" />
                                <span>
                                    {{
                                        isLoadingMore
                                            ? 'Memuat Produk...'
                                            : `Tampilkan Lebih Banyak (${remainingProductsCount} lagi)`
                                    }}
                                </span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </StorefrontShell>
    </div>
</template>
