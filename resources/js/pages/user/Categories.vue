<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, ChevronRight, LayoutGrid, Package, Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import type { StoreCategory, StoreIdentity } from '@/types/storefront';

interface Props {
    store: StoreIdentity;
    categories: StoreCategory[];
    filters: {
        search: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const failedImages = ref<Record<number, boolean>>({});

watch(
    () => props.filters.search,
    (val) => {
        search.value = val ?? '';
    },
);

const applySearch = (): void => {
    const trimmed = search.value.trim();

    router.get(
        '/categories',
        trimmed ? { search: trimmed } : {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearSearch = (): void => {
    search.value = '';
    applySearch();
};
</script>

<template>
    <div>
        <Head :title="`Kategori Produk - ${store.name}`" />

        <StorefrontShell :store="store" active-nav="categories">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="mb-6 flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Link href="/" class="hover:text-primary">Beranda</Link>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <span class="font-medium text-foreground">Kategori Produk</span>
                </nav>

                <!-- Header & Pencarian Kategori -->
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <LayoutGrid class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                                Semua Kategori Produk
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                Pilih kategori untuk melihat koleksi produk {{ store.name }}
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="applySearch" class="relative w-full sm:w-72">
                        <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Cari kategori..."
                            class="h-10 pr-9 pl-9"
                        />
                        <button
                            v-if="search"
                            type="button"
                            @click="clearSearch"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            aria-label="Hapus pencarian kategori"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </form>
                </div>

                <!-- Grid Kategori -->
                <div
                    v-if="categories.length > 0"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
                >
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="`/products?category=${category.slug}`"
                        class="group flex flex-col justify-between gap-4 rounded-2xl border border-border bg-card p-5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md"
                    >
                        <div class="flex items-start gap-4">
                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-border bg-muted">
                                <img
                                    v-if="category.image_url && !failedImages[category.id]"
                                    :src="category.image_url"
                                    :alt="category.name"
                                    @error="failedImages[category.id] = true"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-primary/10 text-primary"
                                >
                                    <Package class="h-7 w-7" />
                                </div>
                            </div>

                            <div class="min-w-0 flex-1 space-y-1">
                                <h2 class="truncate text-base font-bold text-foreground group-hover:text-primary">
                                    {{ category.name }}
                                </h2>
                                <p class="line-clamp-2 text-xs leading-relaxed text-muted-foreground">
                                    {{ category.description || `Koleksi produk pilihan kategori ${category.name}.` }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-border/60 pt-3 text-xs font-medium">
                            <span class="text-muted-foreground">
                                {{ category.products_count }} Produk tersedia
                            </span>
                            <span class="inline-flex items-center gap-1 text-primary group-hover:underline">
                                Lihat Produk
                                <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/20 px-6 py-16 text-center"
                >
                    <LayoutGrid class="mb-3 h-12 w-12 text-muted-foreground/50" />
                    <h3 class="text-base font-semibold text-foreground">Kategori Tidak Ditemukan</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Tidak ada kategori yang sesuai dengan pencarian Anda.
                    </p>
                    <Button v-if="filters.search" class="mt-5" variant="outline" @click="clearSearch">
                        Tampilkan Semua Kategori
                    </Button>
                </div>
            </div>
        </StorefrontShell>
    </div>
</template>
