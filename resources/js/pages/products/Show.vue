<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import StarRating from '@/components/StarRating.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Box,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock,
    Edit,
    Eye,
    EyeOff,
    FileText,
    Image as ImageIcon,
    Info,
    Layers,
    Loader2,
    MessageSquare,
    PackageCheck,
    ShoppingBag,
    Star,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Variant {
    type: string;
    values: string[];
}

interface ProductImage {
    id: number;
    url: string;
    sort_order: number;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    primary_image_url: string | null;
    price: string;
    stock: number;
    sku: string | null;
    weight: number;
    is_active: boolean;
    created_at: string;
    category?: {
        id: number;
        name: string;
    };
    images: ProductImage[];
    variants: Variant[];
}

interface ReviewUser {
    id: number;
    name: string;
    username: string;
}

interface ReviewCustomer {
    id: number;
    user?: ReviewUser;
}

interface ReviewOrder {
    id: number;
    order_number: string;
}

interface ProductReviewItem {
    id: number;
    rating: number;
    comment: string | null;
    is_visible: boolean;
    created_at: string;
    customer?: ReviewCustomer;
    order?: ReviewOrder;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedReviews {
    data: ProductReviewItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

interface RatingBreakdownItem {
    count: number;
    percentage: number;
}

interface RatingStats {
    average_rating: number;
    total_count: number;
    breakdown: Record<number, RatingBreakdownItem>;
}

interface ReviewFilters {
    review_per_page: number;
    review_rating: number | null;
}

interface Props {
    product: Product;
    reviews: PaginatedReviews;
    rating_stats: RatingStats;
    review_filters: ReviewFilters;
}

const props = defineProps<Props>();

const togglingReviewId = ref<number | null>(null);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '/dashboard/products' },
    { title: props.product.name, href: '#' },
]);

const formatRupiah = (angka: string) => {
    const num = parseFloat(angka);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatReviewDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getInitials = (name?: string) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const navigateReviews = (params: { page?: number; rating?: number | null; perPage?: number }) => {
    const query: Record<string, any> = {};

    const rating = params.rating !== undefined ? params.rating : props.review_filters.review_rating;
    if (rating !== null && rating !== undefined) {
        query.review_rating = rating;
    }

    const perPage = params.perPage !== undefined ? params.perPage : props.review_filters.review_per_page;
    if (perPage && perPage !== 10) {
        query.review_per_page = perPage;
    }

    const page = params.page !== undefined ? params.page : 1;
    if (page > 1) {
        query.page = page;
    }

    router.get(window.location.pathname, query, {
        preserveScroll: true,
        preserveState: true,
        only: ['reviews', 'review_filters'],
    });
};

const handleRatingFilter = (star: number | null) => {
    navigateReviews({ rating: star, page: 1 });
};

const handlePerPageChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const perPage = parseInt(target.value, 10);
    navigateReviews({ perPage, page: 1 });
};

const handlePageChange = (page: number) => {
    if (page < 1 || page > props.reviews.last_page || page === props.reviews.current_page) {
        return;
    }
    navigateReviews({ page });
};

const toggleVisibility = (review: ProductReviewItem) => {
    togglingReviewId.value = review.id;
    router.post(
        `/dashboard/reviews/${review.id}/toggle`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                togglingReviewId.value = null;
            },
        }
    );
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 bg-muted/20">
        <Head :title="`Detail Produk: ${product.name}`" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Header -->
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="flex gap-4 items-start">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-background shadow-sm">
                    <img v-if="product.primary_image_url" :src="product.primary_image_url" :alt="product.name" class="h-full w-full object-cover" />
                    <ImageIcon v-else class="h-6 w-6 text-muted-foreground/50" />
                </div>
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ product.name }}</h1>
                        <Badge :variant="product.is_active ? 'default' : 'secondary'" class="h-6">
                            {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                        <span class="flex items-center gap-1.5">
                            <Layers class="h-4 w-4" />
                            {{ product.category?.name || 'Tanpa Kategori' }}
                        </span>
                        <span class="flex items-center gap-1.5" v-if="product.sku">
                            <Box class="h-4 w-4" />
                            SKU: {{ product.sku }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 pl-1 border-l">
                            <StarRating
                                :rating="rating_stats.average_rating"
                                size="xs"
                                :show-score="true"
                                score-class="text-xs font-semibold text-foreground"
                                :show-count="true"
                                :count="rating_stats.total_count"
                            />
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button variant="outline" as-child class="bg-background">
                    <Link href="/dashboard/products">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="`/dashboard/products/${product.id}/edit`">
                        <Edit class="mr-2 h-4 w-4" />
                        Edit Produk
                    </Link>
                </Button>
            </div>
        </div>

        <Tabs defaultValue="overview" class="w-full">
            <TabsList class="mb-4 grid w-full grid-cols-4 max-w-[560px]">
                <TabsTrigger value="overview">Informasi Utama</TabsTrigger>
                <TabsTrigger value="reviews" class="relative">
                    Ulasan
                    <Badge variant="secondary" class="ml-1.5 px-1.5 py-0 text-xs font-semibold">
                        {{ rating_stats.total_count }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="gallery">Galeri</TabsTrigger>
                <TabsTrigger value="variants">Varian</TabsTrigger>
            </TabsList>

            <!-- TAB 1: OVERVIEW -->
            <TabsContent value="overview" class="space-y-6">
                <!-- Metrics -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card class="bg-background">
                        <CardContent class="p-5">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <ShoppingBag class="h-4 w-4" />
                                Harga Produk
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-xl font-bold text-primary">{{ formatRupiah(product.price) }}</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-background">
                        <CardContent class="p-5">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Box class="h-4 w-4" />
                                Stok Tersedia
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold">{{ product.stock }}</span>
                                <span class="text-xs text-muted-foreground">Pcs</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-background">
                        <CardContent class="p-5">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Star class="h-4 w-4 text-amber-500" />
                                Rating & Ulasan
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-2xl font-bold text-amber-500">{{ rating_stats.average_rating.toFixed(1) }}</span>
                                <span class="text-xs text-muted-foreground">/ 5.0 ({{ rating_stats.total_count }} ulasan)</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-background">
                        <CardContent class="p-5">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Layers class="h-4 w-4" />
                                Jumlah Varian
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold">{{ product.variants?.length || 0 }}</span>
                                <span class="text-xs text-muted-foreground">Tipe</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <Card class="bg-background">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Info class="h-5 w-5 text-muted-foreground" />
                                Spesifikasi
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="font-medium text-muted-foreground">Kategori</dt>
                                    <dd class="mt-1 font-medium">{{ product.category?.name || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-muted-foreground">Berat Kemasan</dt>
                                    <dd class="mt-1 font-medium">{{ product.weight }} Gram</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-muted-foreground">SKU / Kode Produk</dt>
                                    <dd class="mt-1 font-medium">{{ product.sku || 'Belum diatur' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-muted-foreground">Ditambahkan Pada</dt>
                                    <dd class="mt-1 font-medium flex items-center gap-1.5">
                                        <Clock class="h-3.5 w-3.5 text-muted-foreground" />
                                        {{ formatDate(product.created_at) }}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card class="bg-background h-full">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <FileText class="h-5 w-5 text-muted-foreground" />
                                Deskripsi Produk
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                {{ product.description || 'Tidak ada deskripsi yang ditambahkan untuk produk ini.' }}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>

            <!-- TAB 2: REVIEWS (ULASAN & RATING) -->
            <TabsContent value="reviews" class="space-y-6">
                <!-- Rating Summary Box -->
                <Card class="bg-background border-border/80 shadow-sm">
                    <CardHeader class="pb-4">
                        <CardTitle class="text-lg flex items-center gap-2">
                            <Star class="h-5 w-5 text-amber-500 fill-amber-500" />
                            Ringkasan Penilaian Produk
                        </CardTitle>
                        <CardDescription>
                            Ulasan resmi dari pelanggan yang telah membeli produk ini (transaksi terverifikasi).
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-6 md:grid-cols-12 items-center">
                            <!-- Left: Big Rating Number & Stars -->
                            <div class="md:col-span-4 flex flex-col items-center justify-center p-6 rounded-xl bg-muted/30 border text-center">
                                <div class="text-5xl font-black tracking-tight text-foreground">
                                    {{ rating_stats.average_rating.toFixed(1) }}
                                </div>
                                <div class="mt-2">
                                    <StarRating :rating="rating_stats.average_rating" size="lg" />
                                </div>
                                <div class="mt-2 text-sm text-muted-foreground font-medium">
                                    Berdasarkan {{ rating_stats.total_count }} ulasan pembeli
                                </div>
                            </div>

                            <!-- Right: Star Breakdown Progress Bars -->
                            <div class="md:col-span-8 space-y-2.5">
                                <div
                                    v-for="star in [5, 4, 3, 2, 1]"
                                    :key="star"
                                    class="flex items-center gap-3 text-sm cursor-pointer group"
                                    @click="handleRatingFilter(review_filters.review_rating === star ? null : star)"
                                >
                                    <button
                                        type="button"
                                        class="flex items-center gap-1 w-20 font-medium text-foreground group-hover:text-primary transition-colors text-left"
                                    >
                                        <Star class="h-4 w-4 text-amber-400 fill-amber-400" />
                                        <span>{{ star }} Bintang</span>
                                    </button>

                                    <!-- Progress Bar Container -->
                                    <div class="flex-1 h-3 bg-muted rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-amber-400 rounded-full transition-all duration-500 group-hover:bg-amber-500"
                                            :style="{ width: `${rating_stats.breakdown[star]?.percentage || 0}%` }"
                                        />
                                    </div>

                                    <div class="w-16 text-right text-xs text-muted-foreground font-medium">
                                        {{ rating_stats.breakdown[star]?.count || 0 }} ({{ rating_stats.breakdown[star]?.percentage || 0 }}%)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Review Filter & List Card -->
                <Card class="bg-background">
                    <CardHeader class="pb-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <CardTitle class="text-base flex items-center gap-2">
                                    <MessageSquare class="h-4 w-4 text-primary" />
                                    Daftar Ulasan Pembeli
                                </CardTitle>
                                <CardDescription>
                                    Menampilkan ulasan terverifikasi per transaksi (dapat mengatur visibilitas ulasan)
                                </CardDescription>
                            </div>

                            <!-- Filter & Per Page Controls -->
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Per Page Limit Selector (Default 10) -->
                                <div class="flex items-center gap-2">
                                    <label for="review-limit" class="text-xs text-muted-foreground font-medium whitespace-nowrap">
                                        Limit Ulasan:
                                    </label>
                                    <select
                                        id="review-limit"
                                        :value="review_filters.review_per_page"
                                        @change="handlePerPageChange"
                                        class="h-8 rounded-md border border-input bg-background px-2.5 py-1 text-xs font-medium focus:outline-none focus:ring-1 focus:ring-ring"
                                    >
                                        <option :value="5">5 per halaman</option>
                                        <option :value="10">10 per halaman (Default)</option>
                                        <option :value="15">15 per halaman</option>
                                        <option :value="20">20 per halaman</option>
                                        <option :value="50">50 per halaman</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Rating Pills Filter -->
                        <div class="flex flex-wrap items-center gap-1.5 pt-3 border-t">
                            <span class="text-xs text-muted-foreground font-medium mr-1">Filter Bintang:</span>
                            <Button
                                size="sm"
                                variant="outline"
                                class="h-7 text-xs px-2.5 rounded-full"
                                :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90 hover:text-primary-foreground border-primary': review_filters.review_rating === null }"
                                @click="handleRatingFilter(null)"
                            >
                                Semua ({{ rating_stats.total_count }})
                            </Button>
                            <Button
                                v-for="star in [5, 4, 3, 2, 1]"
                                :key="star"
                                size="sm"
                                variant="outline"
                                class="h-7 text-xs px-2.5 rounded-full inline-flex items-center gap-1"
                                :class="{ 'bg-amber-500 text-white hover:bg-amber-600 hover:text-white border-amber-500': review_filters.review_rating === star }"
                                @click="handleRatingFilter(star)"
                            >
                                <Star class="h-3 w-3 fill-current" />
                                {{ star }} ({{ rating_stats.breakdown[star]?.count || 0 }})
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <!-- Reviews List -->
                        <div v-if="reviews.data.length > 0" class="divide-y divide-border">
                            <div
                                v-for="review in reviews.data"
                                :key="review.id"
                                class="py-5 first:pt-0 last:pb-0 space-y-3"
                                :class="{ 'opacity-60 bg-muted/20 -mx-4 px-4 rounded-lg': !review.is_visible }"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                                    <!-- User Info, Verified Badge & Visibility Status -->
                                    <div class="flex items-center gap-3">
                                        <Avatar class="h-9 w-9 border">
                                            <AvatarFallback class="bg-primary/10 text-primary font-semibold text-xs">
                                                {{ getInitials(review.customer?.user?.name) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-semibold text-foreground">
                                                    {{ review.customer?.user?.name || 'Pelanggan Batik' }}
                                                </span>
                                                <Badge variant="secondary" class="h-5 px-1.5 text-[10px] gap-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                    <CheckCircle2 class="h-3 w-3" />
                                                    Pembeli Terverifikasi
                                                </Badge>

                                                <!-- Visibility Badge -->
                                                <Badge
                                                    variant="outline"
                                                    class="h-5 px-1.5 text-[10px] gap-1"
                                                    :class="review.is_visible ? 'bg-emerald-50/50 text-emerald-600 dark:text-emerald-400 border-emerald-200' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-300'"
                                                >
                                                    {{ review.is_visible ? 'Ditampilkan' : 'Disembunyikan' }}
                                                </Badge>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                                                <span>@{{ review.customer?.user?.username || 'user' }}</span>
                                                <span>•</span>
                                                <span class="flex items-center gap-1 font-mono">
                                                    <PackageCheck class="h-3 w-3 text-muted-foreground" />
                                                    {{ review.order?.order_number || '-' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Rating Stars, Date & Visibility Action -->
                                    <div class="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-start gap-2 sm:gap-1.5">
                                        <div class="flex items-center gap-2">
                                            <StarRating :rating="review.rating" size="sm" />
                                            <span class="text-xs text-muted-foreground">
                                                {{ formatReviewDate(review.created_at) }}
                                            </span>
                                        </div>

                                        <!-- Visibility Toggle Action Button -->
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-7 text-xs px-2.5 gap-1.5"
                                            :disabled="togglingReviewId === review.id"
                                            @click="toggleVisibility(review)"
                                        >
                                            <Loader2 v-if="togglingReviewId === review.id" class="h-3.5 w-3.5 animate-spin" />
                                            <template v-else>
                                                <EyeOff v-if="review.is_visible" class="h-3.5 w-3.5 text-muted-foreground" />
                                                <Eye v-else class="h-3.5 w-3.5 text-emerald-600" />
                                                <span>{{ review.is_visible ? 'Sembunyikan' : 'Tampilkan' }}</span>
                                            </template>
                                        </Button>
                                    </div>
                                </div>

                                <!-- Review Comment -->
                                <div class="pl-12 text-sm text-foreground/90 leading-relaxed bg-muted/15 p-3 rounded-lg border border-border/50">
                                    {{ review.comment || 'Pengguna tidak memberikan komentar teks tambahan.' }}
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-else
                            class="flex flex-col items-center justify-center rounded-xl border border-dashed py-14 text-center text-muted-foreground"
                        >
                            <MessageSquare class="mb-3 h-10 w-10 opacity-20" />
                            <p class="font-medium text-foreground">Belum Ada Ulasan</p>
                            <p class="text-sm max-w-sm mt-1">
                                <template v-if="review_filters.review_rating">
                                    Tidak ada ulasan untuk filter rating {{ review_filters.review_rating }} bintang.
                                </template>
                                <template v-else>
                                    Belum ada pembeli yang memberikan ulasan untuk produk ini.
                                </template>
                            </p>
                            <Button
                                v-if="review_filters.review_rating"
                                variant="outline"
                                size="sm"
                                class="mt-4"
                                @click="handleRatingFilter(null)"
                            >
                                Reset Filter
                            </Button>
                        </div>

                        <!-- Pagination Footer -->
                        <div
                            v-if="reviews.total > 0"
                            class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t"
                        >
                            <div class="text-xs text-muted-foreground">
                                Menampilkan <span class="font-medium text-foreground">{{ reviews.from || 0 }}</span> -
                                <span class="font-medium text-foreground">{{ reviews.to || 0 }}</span> dari
                                <span class="font-medium text-foreground">{{ reviews.total }}</span> ulasan
                            </div>

                            <div class="flex items-center gap-1.5">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 px-2.5 text-xs"
                                    :disabled="reviews.current_page <= 1"
                                    @click="handlePageChange(reviews.current_page - 1)"
                                >
                                    <ChevronLeft class="h-4 w-4 mr-1" />
                                    Sebelumnya
                                </Button>

                                <div class="flex items-center gap-1">
                                    <template v-for="link in reviews.links" :key="link.label">
                                        <Button
                                            v-if="!link.label.includes('Previous') && !link.label.includes('Next') && !link.label.includes('pagination')"
                                            size="sm"
                                            :variant="link.active ? 'default' : 'outline'"
                                            class="h-8 w-8 p-0 text-xs"
                                            @click="link.url ? handlePageChange(parseInt(link.label, 10)) : undefined"
                                        >
                                            {{ link.label }}
                                        </Button>
                                    </template>
                                </div>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 px-2.5 text-xs"
                                    :disabled="reviews.current_page >= reviews.last_page"
                                    @click="handlePageChange(reviews.current_page + 1)"
                                >
                                    Selanjutnya
                                    <ChevronRight class="h-4 w-4 ml-1" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- TAB 3: GALLERY -->
            <TabsContent value="gallery" class="space-y-6">
                <Card class="bg-background">
                    <CardHeader>
                        <CardTitle>Gambar Produk</CardTitle>
                        <CardDescription>Gambar utama dan galeri pendukung.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="w-full max-w-sm space-y-3">
                                <h3 class="text-sm font-medium">Gambar Utama</h3>
                                <div class="aspect-square overflow-hidden rounded-xl border bg-muted flex items-center justify-center">
                                    <img v-if="product.primary_image_url" :src="product.primary_image_url" :alt="product.name" class="h-full w-full object-cover" />
                                    <ImageIcon v-else class="h-10 w-10 text-muted-foreground/30" />
                                </div>
                            </div>
                            
                            <Separator orientation="vertical" class="hidden md:block h-auto" />
                            <Separator class="md:hidden" />
                            
                            <div class="w-full space-y-3">
                                <h3 class="text-sm font-medium">Galeri Tambahan</h3>
                                <div v-if="product.images.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <div v-for="image in product.images" :key="image.id" class="aspect-square overflow-hidden rounded-xl border bg-muted">
                                        <img :src="image.url" alt="Gallery image" class="h-full w-full object-cover" />
                                    </div>
                                </div>
                                <div v-else class="flex h-40 flex-col items-center justify-center rounded-xl border border-dashed text-muted-foreground">
                                    <ImageIcon class="mb-2 h-8 w-8 opacity-20" />
                                    <p class="text-sm">Tidak ada gambar tambahan.</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- TAB 4: VARIANTS -->
            <TabsContent value="variants" class="space-y-6">
                <Card class="bg-background">
                    <CardHeader>
                        <CardTitle>Daftar Varian</CardTitle>
                        <CardDescription>Pilihan ukuran, warna, atau jenis yang tersedia untuk produk ini.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="product.variants && product.variants.length > 0" class="grid gap-6 sm:grid-cols-2">
                            <div v-for="(variant, index) in product.variants" :key="index" class="space-y-3 rounded-lg border p-4 bg-muted/20">
                                <h4 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">{{ variant.type }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    <Badge v-for="(val, vIndex) in variant.values" :key="vIndex" variant="secondary" class="px-3 py-1 text-sm bg-background">
                                        {{ val }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-muted-foreground">
                            <Box class="mb-3 h-10 w-10 opacity-20" />
                            <p class="font-medium text-foreground">Tidak Ada Varian</p>
                            <p class="text-sm">Produk ini tidak memiliki varian.</p>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </div>
</template>
