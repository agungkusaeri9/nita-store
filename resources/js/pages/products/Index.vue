<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
// Removed Table imports
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Edit,
    Eye,
    Filter,
    Image as ImageIcon,
    LayoutGrid,
    Loader2,
    MoreVertical,
    Package,
    Plus,
    Search,
    Trash2,
    ChevronLeft,
    ChevronRight,
    FileUp,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

interface Product {
    id: number;
    name: string;
    slug: string;
    primary_image_url: string | null;
    price: string;
    stock: number;
    sku: string | null;
    is_active: boolean;
    category?: {
        name: string;
    };
}

interface PaginationData {
    data: Product[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    products: PaginationData;
    filters: {
        search?: string;
        status?: string;
        sort?: string;
        direction?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '#' },
    { title: 'Semua Produk', href: '/dashboard/products' },
];

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const perPage = ref(props.filters.per_page?.toString() || '10');
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout>;

const buildQuery = () => {
    const query: Record<string, string> = {};
    if (search.value) query.search = search.value;
    if (status.value !== 'all') query.status = status.value;
    if (perPage.value !== '10') query.per_page = perPage.value;
    
    // Maintain sorting if exists
    if (props.filters.sort) query.sort = props.filters.sort;
    if (props.filters.direction) query.direction = props.filters.direction;

    return query;
};

const fetchProducts = () => {
    isLoading.value = true;
    router.get('/dashboard/products', buildQuery(), {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => (isLoading.value = false),
    });
};

watch(search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchProducts, 400);
    }
});

watch([status, perPage], () => {
    fetchProducts();
});

// Delete Confirmation
const isDeleteDialogOpen = ref(false);
const productToDelete = ref<Product | null>(null);

const confirmDelete = (product: Product) => {
    productToDelete.value = product;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (productToDelete.value) {
        router.delete(`/dashboard/products/${productToDelete.value.id}`, {
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                productToDelete.value = null;
            },
        });
    }
};

const formatRupiah = (angka: string) => {
    const num = parseFloat(angka);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

// Import Data
const isImportModalOpen = ref(false);
const importFileInput = ref<HTMLInputElement | null>(null);
const importForm = useForm({
    file: null as File | null,
});

const handleImportFile = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        importForm.file = target.files[0];
    }
};

const submitImport = () => {
    importForm.post('/dashboard/products/import', {
        preserveScroll: true,
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
            if (importFileInput.value) importFileInput.value.value = '';
        },
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Semua Produk" />

        <!-- Breadcrumbs -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Semua Produk</h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kelola data produk, harga, stok, gambar, dan varian.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button v-if="can('import products')" variant="outline" @click="isImportModalOpen = true" class="gap-2">
                    <FileUp class="h-4 w-4" />
                    Import Excel
                </Button>
                <Button v-if="can('create products')" as-child class="gap-2">
                    <Link href="/dashboard/products/create">
                        <Plus class="h-4 w-4" />
                        Tambah Produk
                    </Link>
                </Button>
            </div>
        </div>

        <!-- DataTable Container -->
        <div class="rounded-xl border bg-card shadow-xs">
            <!-- Table Controls -->
            <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-1 items-center gap-2 sm:max-w-xs">
                    <div class="relative w-full">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Cari produk atau SKU..."
                            class="w-full bg-background pl-8"
                        />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 border-r pr-2 mr-2">
                        <Filter class="h-4 w-4 text-muted-foreground" />
                        <Select v-model="status">
                            <SelectTrigger class="h-9 w-[130px] bg-background">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="active">Aktif</SelectItem>
                                <SelectItem value="inactive">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground hidden sm:inline-block">Tampilkan</span>
                        <Select v-model="perPage">
                            <SelectTrigger class="h-9 w-[70px] bg-background">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10</SelectItem>
                                <SelectItem value="20">20</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="relative overflow-x-auto">
                <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-background/50 backdrop-blur-sm">
                    <Loader2 class="h-8 w-8 animate-spin text-primary" />
                </div>
                <table class="w-full caption-bottom text-sm">
                    <thead class="bg-muted/50 [&_tr]:border-b">
                        <tr class="border-b transition-colors">
                            <th class="h-10 w-[80px] px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Gambar</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Produk</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Harga</th>
                            <th class="h-10 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Stok</th>
                            <th class="h-10 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                            <th class="h-10 px-4 text-right align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-for="product in products.data" :key="product.id" class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                            <td class="p-4 align-middle">
                                <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-md border bg-muted">
                                    <img
                                        v-if="product.primary_image_url"
                                        :src="product.primary_image_url"
                                        :alt="product.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <ImageIcon v-else class="h-5 w-5 text-muted-foreground/50" />
                                </div>
                            </td>
                            <td class="p-4 align-middle text-left">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ product.name }}</span>
                                    <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                                        <span>{{ product.category?.name || 'Tanpa Kategori' }}</span>
                                        <span v-if="product.sku" class="flex items-center gap-1">
                                            <span class="h-1 w-1 rounded-full bg-muted-foreground/30"></span>
                                            SKU: {{ product.sku }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-left">
                                <span class="font-medium text-foreground">{{ formatRupiah(product.price) }}</span>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <Badge :variant="product.stock > 0 ? 'secondary' : 'destructive'" class="font-mono">
                                    {{ product.stock }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <Badge :variant="product.is_active ? 'default' : 'secondary'">
                                    {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" class="h-8 w-8 p-0">
                                            <span class="sr-only">Buka menu</span>
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <Link :href="`/dashboard/products/${product.id}`">
                                            <DropdownMenuItem class="cursor-pointer">
                                                <Eye class="mr-2 h-4 w-4" />
                                                Lihat Detail
                                            </DropdownMenuItem>
                                        </Link>
                                        <Link v-if="can('edit products')" :href="`/dashboard/products/${product.id}/edit`">
                                            <DropdownMenuItem class="cursor-pointer">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit Produk
                                            </DropdownMenuItem>
                                        </Link>
                                        <DropdownMenuItem
                                            v-if="can('delete products')"
                                            class="cursor-pointer text-destructive focus:text-destructive"
                                            @click="confirmDelete(product)"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Hapus Produk
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>

                        <tr v-if="products.data.length === 0">
                            <td colspan="6" class="h-32 text-center p-4 align-middle">
                                <div class="flex flex-col items-center justify-center text-muted-foreground">
                                    <Package class="mb-2 h-8 w-8 opacity-20" />
                                    <p>Tidak ada data produk ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- DataTable Footer / Pagination -->
            <div
                class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ products.from || 0 }} sampai {{ products.to || 0 }} dari
                    {{ products.total }} data
                </div>

                <!-- Numbered Pagination -->
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in products.links" :key="i">
                        <!-- Previous button -->
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
                        <!-- Next button -->
                        <Button
                            v-else-if="i === products.links.length - 1"
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, buildQuery(), { preserveState: true })"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                        <!-- Ellipsis -->
                        <span
                            v-else-if="link.label === '...'"
                            class="px-2 text-xs text-muted-foreground"
                        >
                            ...
                        </span>
                        <!-- Page Number buttons -->
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

        <!-- Delete Confirmation Dialog -->
        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Produk?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Anda yakin ingin menghapus produk <strong>{{ productToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan dan akan menghapus semua gambar serta data varian terkait.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="executeDelete">
                        Hapus Produk
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
        <!-- Import Modal -->
        <Dialog v-model:open="isImportModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Import Produk via Excel</DialogTitle>
                    <DialogDescription class="space-y-4">
                        <p>Unggah file Excel (.xlsx) atau CSV dengan kolom: name, category, price, stock, sku, weight, description, is_active, variants.</p>
                        <Button as-child variant="outline" size="sm">
                            <a href="/dashboard/products/template" download>Download Template Excel</a>
                        </Button>
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitImport">
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="file">File Excel / CSV</Label>
                            <Input 
                                id="file" 
                                type="file" 
                                accept=".xlsx,.xls,.csv" 
                                @change="handleImportFile" 
                                ref="importFileInput"
                            />
                            <p v-if="importForm.errors.file" class="text-xs text-destructive">{{ importForm.errors.file }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isImportModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="importForm.processing || !importForm.file">
                            <Loader2 v-if="importForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Import Data
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
