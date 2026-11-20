<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
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
    ChevronLeft,
    ChevronRight,
    Layers,
    ListOrdered,
    Pencil,
    Plus,
    Search,
    Settings2,
    Trash2,
    X,
} from 'lucide-vue-next';
import { reactive, ref } from 'vue';

interface VariantType {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    values_count: number;
    created_at: string;
}

interface Pagination {
    data: VariantType[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Props {
    variantTypes: Pagination;
    filters: {
        search?: string;
        is_active?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '#' },
    { title: 'Varian Produk', href: '/dashboard/variant-types' },
];

const filterState = reactive({
    search: props.filters.search || '',
    is_active: props.filters.is_active || 'all',
    per_page: props.filters.per_page || '10',
});

const buildQuery = () => ({
    ...filterState,
    is_active: filterState.is_active === 'all' ? '' : filterState.is_active,
});

const debouncedSearch = useDebounceFn(() => {
    router.get('/dashboard/variant-types', buildQuery(), { preserveState: true, replace: true });
}, 300);

import { watch } from 'vue';
watch(() => ({ ...filterState }), () => { debouncedSearch(); });

const clearSearch = () => { filterState.search = ''; };

// Delete
const isDeleteDialogOpen = ref(false);
const variantToDelete = ref<VariantType | null>(null);

const confirmDelete = (vt: VariantType) => {
    variantToDelete.value = vt;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (variantToDelete.value) {
        router.delete(`/dashboard/variant-types/${variantToDelete.value.id}`, {
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                variantToDelete.value = null;
            },
        });
    }
};

const perPageOptions = ['10', '20', '30', '50', '100'];

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Varian Produk" />

        <!-- Breadcrumbs in Content -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Varian Produk</h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kelola tipe varian produk (Ukuran, Warna, Motif, dll.)
                </p>
            </div>

            <Button as-child class="shrink-0">
                <Link href="/dashboard/variant-types/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Varian
                </Link>
            </Button>
        </div>

        <!-- Table Card -->
        <div class="bg-card border rounded-xl shadow-sm overflow-hidden">
            <!-- Top Controls -->
            <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <span>Tampilkan</span>
                        <Select v-model="filterState.per_page">
                            <SelectTrigger class="h-8 w-[72px] text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="opt in perPageOptions" :key="opt" :value="opt">
                                    {{ opt }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <span>data</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Select v-model="filterState.is_active">
                            <SelectTrigger class="h-8 w-[130px] text-xs">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="true">Aktif</SelectItem>
                                <SelectItem value="false">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div class="relative w-full max-w-xs sm:w-64">
                    <Search class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="filterState.search"
                        type="search"
                        placeholder="Cari varian..."
                        class="h-9 pl-8 pr-8 text-sm"
                    />
                    <button
                        v-if="filterState.search"
                        @click="clearSearch"
                        class="absolute top-2.5 right-2.5 text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/40 border-b">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground w-8">#</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Nama Varian</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Slug</th>
                            <th class="px-4 py-3 text-center font-medium text-muted-foreground">Jumlah Nilai</th>
                            <th class="px-4 py-3 text-center font-medium text-muted-foreground">Urutan</th>
                            <th class="px-4 py-3 text-center font-medium text-muted-foreground">Status</th>
                            <th class="px-4 py-3 text-center font-medium text-muted-foreground">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-if="variantTypes.data.length > 0"
                            v-for="(vt, index) in variantTypes.data"
                            :key="vt.id"
                            class="hover:bg-muted/30 transition-colors"
                        >
                            <td class="px-4 py-3 text-muted-foreground text-xs">
                                {{ (variantTypes.current_page - 1) * variantTypes.per_page + index + 1 }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
                                        <Layers class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-foreground leading-tight">{{ vt.name }}</p>
                                        <p v-if="vt.description" class="text-xs text-muted-foreground truncate max-w-[200px]">
                                            {{ vt.description }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <code class="text-xs bg-muted px-1.5 py-0.5 rounded">{{ vt.slug }}</code>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Link
                                    :href="`/dashboard/variant-types/${vt.id}/values`"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-primary hover:underline"
                                >
                                    <ListOrdered class="h-3.5 w-3.5" />
                                    {{ vt.values_count }} nilai
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-center text-muted-foreground text-xs">{{ vt.sort_order }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="vt.is_active ? 'default' : 'secondary'">
                                    {{ vt.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <Link :href="`/dashboard/variant-types/${vt.id}/values`">
                                        <Button variant="ghost" size="icon" class="h-7 w-7" title="Kelola Nilai">
                                            <Settings2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </Link>
                                    <Link :href="`/dashboard/variant-types/${vt.id}/edit`">
                                        <Button variant="ghost" size="icon" class="h-7 w-7" title="Edit">
                                            <Pencil class="h-3.5 w-3.5" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-7 w-7 text-destructive hover:text-destructive hover:bg-destructive/10"
                                        title="Hapus"
                                        @click="confirmDelete(vt)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-else>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                    <Layers class="h-10 w-10 opacity-30" />
                                    <p class="font-medium text-foreground">Data varian produk tidak ditemukan</p>
                                    <p class="text-xs">
                                        {{ filterState.search ? 'Coba ubah kata kunci pencarian.' : 'Belum ada data varian yang terdaftar.' }}
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
                    Menampilkan {{ variantTypes.from || 0 }} sampai {{ variantTypes.to || 0 }} dari
                    {{ variantTypes.total }} data
                </div>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in variantTypes.links" :key="i">
                        <Button
                            v-if="i === 0"
                            variant="outline" size="icon" class="h-8 w-8"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, buildQuery(), { preserveState: true })"
                            aria-label="Halaman sebelumnya"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            v-else-if="i === variantTypes.links.length - 1"
                            variant="outline" size="icon" class="h-8 w-8"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, buildQuery(), { preserveState: true })"
                            aria-label="Halaman berikutnya"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                        <span v-else-if="link.label === '...'" class="px-2 text-xs text-muted-foreground">...</span>
                        <Button
                            v-else
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm" class="h-8 min-w-8 px-2.5 text-xs font-medium"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, buildQuery(), { preserveState: true })"
                        >
                            {{ link.label }}
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Dialog -->
    <Dialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus Varian Produk</DialogTitle>
                <DialogDescription>
                    Apakah Anda yakin ingin menghapus varian
                    <strong class="text-foreground">{{ variantToDelete?.name }}</strong>?
                    Semua nilai varian di dalamnya juga akan dihapus. Tindakan ini tidak dapat dibatalkan.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="outline">Batal</Button>
                </DialogClose>
                <Button variant="destructive" @click="executeDelete">Ya, Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
