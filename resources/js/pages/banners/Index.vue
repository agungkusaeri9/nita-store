<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    AlertTriangle,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Edit,
    ExternalLink,
    Eye,
    EyeOff,
    Image as ImageIcon,
    Info,
    Layers,
    Loader2,
    Package,
    Plus,
    Search,
    Smartphone,
    Trash2,
    X,
} from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue';

interface BannerProduct {
    id: number;
    name: string;
}

interface BannerCategory {
    id: number;
    name: string;
}

interface Banner {
    id: number;
    title: string;
    image: string;
    image_url: string | null;
    target_type: 'none' | 'product' | 'category' | 'url';
    target_url: string | null;
    sort_order: number;
    is_active: boolean;
    start_date: string | null;
    end_date: string | null;
    description: string | null;
    created_at: string | null;
    product?: BannerProduct | null;
    category?: BannerCategory | null;
}

interface Props {
    banners: {
        data: Banner[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
        target_type?: string;
        sort?: string;
        direction?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Banner Promosi (Mobile)', href: '/dashboard/banners' },
];

const filterState = reactive({
    search: props.filters.search || '',
    status: props.filters.status || 'all',
    target_type: props.filters.target_type || 'all',
    per_page: props.filters.per_page || '10',
});

const togglingBannerId = ref<number | null>(null);

const buildQuery = () => ({
    ...filterState,
    status: filterState.status === 'all' ? '' : filterState.status,
    target_type: filterState.target_type === 'all' ? '' : filterState.target_type,
});

const debouncedSearch = useDebounceFn(() => {
    router.get('/dashboard/banners', buildQuery(), {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(
    () => ({ ...filterState }),
    () => {
        debouncedSearch();
    }
);

const clearSearch = () => {
    filterState.search = '';
};

// Delete confirmation handling
const isDeleteDialogOpen = ref(false);
const bannerToDelete = ref<Banner | null>(null);

const confirmDelete = (banner: Banner) => {
    bannerToDelete.value = banner;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (bannerToDelete.value) {
        router.delete(`/dashboard/banners/${bannerToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                bannerToDelete.value = null;
            },
        });
    }
};

const toggleBanner = (banner: Banner) => {
    togglingBannerId.value = banner.id;
    router.post(
        `/dashboard/banners/${banner.id}/toggle`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                togglingBannerId.value = null;
            },
        }
    );
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const perPageOptions = ['10', '20', '30', '50'];
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 bg-muted/20">
        <Head title="Banner Promosi Mobile" />

        <!-- Breadcrumbs -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Smartphone class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                            Banner Promosi Mobile
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Kelola gambar slider promo, urutan, status, dan target navigasi untuk aplikasi mobile.
                        </p>
                    </div>
                </div>
            </div>

            <Button as-child class="shrink-0">
                <Link href="/dashboard/banners/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Banner
                </Link>
            </Button>
        </div>

        <!-- Mobile Dimension Guide Callout -->
        <div class="rounded-xl border border-primary/20 bg-primary/5 p-4 text-sm text-foreground">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 rounded-full bg-primary/10 p-1.5 text-primary">
                        <Info class="h-4 w-4" />
                    </div>
                    <div class="space-y-1">
                        <p class="font-semibold text-primary">Panduan & Rekomendasi Banner Mobile App:</p>
                        <p class="text-xs text-muted-foreground leading-relaxed">
                            • Rasio gambar ideal: <strong class="text-foreground font-medium">2:1</strong> (Landscape) | Resolusi rekomendasi: <strong class="text-foreground font-medium">1080 x 540 px</strong> (min. 720 x 360 px).<br />
                            • Format file: <strong class="text-foreground font-medium">JPG, PNG, WEBP</strong> | Ukuran maks: <strong class="text-foreground font-medium">2 MB</strong>.<br />
                            • Letakkan teks dan elemen promo utama di area tengah agar tidak terpotong saat ditampilkan di berbagai tipe layar smartphone.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable Card Container -->
        <div class="rounded-xl border bg-card shadow-xs">
            <!-- Filter & Controls -->
            <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Per Page -->
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <span>Tampilkan</span>
                        <Select v-model="filterState.per_page">
                            <SelectTrigger class="h-8 w-[76px] text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="opt in perPageOptions" :key="opt" :value="opt">
                                    {{ opt }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Status Filter -->
                    <Select v-model="filterState.status">
                        <SelectTrigger class="h-8 w-[130px] text-xs">
                            <SelectValue placeholder="Semua Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="active">Aktif</SelectItem>
                            <SelectItem value="inactive">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Target Type Filter -->
                    <Select v-model="filterState.target_type">
                        <SelectTrigger class="h-8 w-[140px] text-xs">
                            <SelectValue placeholder="Semua Target" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Target</SelectItem>
                            <SelectItem value="none">Hanya Gambar</SelectItem>
                            <SelectItem value="product">Buka Produk</SelectItem>
                            <SelectItem value="category">Buka Kategori</SelectItem>
                            <SelectItem value="url">URL Kustom</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Search Input -->
                <div class="relative w-full max-w-xs sm:w-64">
                    <Search class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="filterState.search"
                        type="search"
                        placeholder="Cari judul banner..."
                        class="h-8 pr-8 pl-8 text-xs"
                    />
                    <button
                        v-if="filterState.search"
                        type="button"
                        class="absolute top-2 right-2 text-muted-foreground hover:text-foreground"
                        @click="clearSearch"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="relative w-full overflow-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="bg-muted/50 [&_tr]:border-b">
                        <tr class="border-b transition-colors">
                            <th class="h-10 w-16 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Urutan
                            </th>
                            <th class="h-10 w-44 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Preview Banner
                            </th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Judul & Deskripsi
                            </th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Target Klik Mobile
                            </th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Periode Tayang
                            </th>
                            <th class="h-10 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Status
                            </th>
                            <th class="h-10 px-4 text-right align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border [&_tr:last-child]:border-0">
                        <tr
                            v-for="banner in banners.data"
                            :key="banner.id"
                            class="transition-colors hover:bg-muted/40"
                            :class="{ 'opacity-65': !banner.is_active }"
                        >
                            <!-- Sort Order Badge -->
                            <td class="p-3.5 text-center align-middle">
                                <Badge variant="secondary" class="font-mono text-xs font-bold px-2 py-0.5">
                                    #{{ banner.sort_order }}
                                </Badge>
                            </td>

                            <!-- Image Preview (2:1 aspect ratio) -->
                            <td class="p-3.5 text-center align-middle">
                                <div class="mx-auto w-36 aspect-[2/1] overflow-hidden rounded-lg border bg-muted/40 shadow-xs flex items-center justify-center">
                                    <img
                                        v-if="banner.image_url"
                                        :src="banner.image_url"
                                        :alt="banner.title"
                                        class="h-full w-full object-cover"
                                    />
                                    <ImageIcon v-else class="h-5 w-5 text-muted-foreground/50" />
                                </div>
                            </td>

                            <!-- Title & Description -->
                            <td class="p-3.5 align-middle">
                                <div class="flex flex-col gap-1 max-w-sm">
                                    <span class="font-semibold text-foreground text-sm leading-tight">
                                        {{ banner.title }}
                                    </span>
                                    <p v-if="banner.description" class="text-xs text-muted-foreground line-clamp-2">
                                        {{ banner.description }}
                                    </p>
                                </div>
                            </td>

                            <!-- Target Type -->
                            <td class="p-3.5 align-middle text-xs">
                                <div v-if="banner.target_type === 'product'" class="flex items-center gap-1.5 text-foreground">
                                    <Badge variant="outline" class="gap-1 border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-400">
                                        <Package class="h-3 w-3" />
                                        Produk
                                    </Badge>
                                    <span class="font-medium truncate max-w-[150px]">{{ banner.product?.name || '-' }}</span>
                                </div>
                                <div v-else-if="banner.target_type === 'category'" class="flex items-center gap-1.5 text-foreground">
                                    <Badge variant="outline" class="gap-1 border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-900 dark:bg-purple-950/40 dark:text-purple-400">
                                        <Layers class="h-3 w-3" />
                                        Kategori
                                    </Badge>
                                    <span class="font-medium">{{ banner.category?.name || '-' }}</span>
                                </div>
                                <div v-else-if="banner.target_type === 'url'" class="flex items-center gap-1.5 text-foreground">
                                    <Badge variant="outline" class="gap-1 border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-400">
                                        <ExternalLink class="h-3 w-3" />
                                        URL
                                    </Badge>
                                    <a :href="banner.target_url || '#'" target="_blank" class="truncate max-w-[150px] text-primary hover:underline">
                                        {{ banner.target_url || '-' }}
                                    </a>
                                </div>
                                <div v-else class="text-muted-foreground flex items-center gap-1.5">
                                    <Badge variant="secondary" class="text-[10px]">
                                        Hanya Gambar
                                    </Badge>
                                </div>
                            </td>

                            <!-- Display Period -->
                            <td class="p-3.5 align-middle text-xs text-muted-foreground">
                                <div v-if="banner.start_date || banner.end_date" class="flex flex-col gap-0.5">
                                    <span class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        {{ formatDate(banner.start_date) }} - {{ formatDate(banner.end_date) }}
                                    </span>
                                </div>
                                <span v-else class="text-muted-foreground/70">Selalu Tayang</span>
                            </td>

                            <!-- Status & Toggle -->
                            <td class="p-3.5 text-center align-middle">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="h-7 text-xs px-2.5 gap-1.5"
                                    :disabled="togglingBannerId === banner.id"
                                    @click="toggleBanner(banner)"
                                >
                                    <Loader2 v-if="togglingBannerId === banner.id" class="h-3.5 w-3.5 animate-spin" />
                                    <template v-else>
                                        <Eye v-if="banner.is_active" class="h-3.5 w-3.5 text-emerald-600" />
                                        <EyeOff v-else class="h-3.5 w-3.5 text-muted-foreground" />
                                        <span :class="banner.is_active ? 'text-emerald-700 dark:text-emerald-400 font-medium' : 'text-muted-foreground'">
                                            {{ banner.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </template>
                                </Button>
                            </td>

                            <!-- Actions -->
                            <td class="p-3.5 text-right align-middle">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-muted-foreground hover:text-foreground"
                                        as-child
                                        title="Edit Banner"
                                    >
                                        <Link :href="`/dashboard/banners/${banner.id}/edit`">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-muted-foreground hover:text-destructive"
                                        title="Hapus Banner"
                                        @click="confirmDelete(banner)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="banners.data.length === 0">
                            <td colspan="7" class="p-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                    <div class="rounded-full bg-muted p-3">
                                        <Smartphone class="h-6 w-6" />
                                    </div>
                                    <p class="font-medium text-foreground">Belum ada banner promosi</p>
                                    <p class="text-xs max-w-sm">
                                        {{ filterState.search ? 'Tidak ada banner yang sesuai dengan pencarian.' : 'Tambahkan banner promosi untuk ditampilkan di aplikasi mobile.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer / Pagination -->
            <div class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ banners.from || 0 }} sampai {{ banners.to || 0 }} dari {{ banners.total }} banner
                </div>

                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in banners.links" :key="i">
                        <Button
                            v-if="i === 0"
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, buildQuery(), { preserveState: true })"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            v-else-if="i === banners.links.length - 1"
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, buildQuery(), { preserveState: true })"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                        <span v-else-if="link.label === '...'" class="px-2 text-xs text-muted-foreground">...</span>
                        <Button
                            v-else
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-8 min-w-8 px-2.5 text-xs font-medium"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, buildQuery(), { preserveState: true })"
                        >
                            {{ link.label }}
                        </Button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <div class="mb-2 flex items-center gap-2 text-destructive">
                        <AlertTriangle class="h-5 w-5" />
                        <DialogTitle>Konfirmasi Hapus Banner</DialogTitle>
                    </div>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus banner <strong>"{{ bannerToDelete?.title }}"</strong>? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-3 sm:gap-3">
                    <Button variant="outline" @click="isDeleteDialogOpen = false">
                        Batal
                    </Button>
                    <Button variant="destructive" @click="executeDelete">
                        Hapus Banner
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
