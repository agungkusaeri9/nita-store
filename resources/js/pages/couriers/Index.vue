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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CreditCard,
    Edit,
    Filter,
    Image as ImageIcon,
    Loader2,
    MoreVertical,
    Plus,
    Search,
    Trash2,
    ChevronLeft,
    ChevronRight,
    X,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Courier {
    id: number;
    name: string;
    logo: string | null;
    code: string;
    description: string;
    is_active: boolean;
    created_at: string;
}

interface PaginationData {
    data: Courier[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    couriers: PaginationData;
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
    { title: 'Kurir', href: '/dashboard/couriers' },
];

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const perPage = ref(props.filters.per_page?.toString() || '10');
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout>;

const fetchcouriers = () => {
    isLoading.value = true;
    router.get('/dashboard/couriers', {
        search: search.value,
        status: status.value,
        per_page: perPage.value
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => (isLoading.value = false),
    });
};

watch(search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchcouriers, 400);
    }
});

watch([status, perPage], () => {
    fetchcouriers();
});

const perPageOptions = ['10', '20', '30', '40', '50', '100'];

const clearSearch = () => {
    search.value = '';
};

// Modal state
const isModalOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<number | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    _method: 'POST',
    name: '',
    code: '',
    description: '',
    logo_file: null as File | null,
    is_active: true,
});

const previewUrl = ref<string | null>(null);

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.logo_file = target.files[0];
        previewUrl.value = URL.createObjectURL(target.files[0]);
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    currentId.value = null;
    form.reset();
    form.clearErrors();
    form.name = '';
    form.code = '';
    form.description = '';
    form.logo_file = null;
    form.is_active = true;
    form._method = 'POST';
    previewUrl.value = null;
    if (fileInput.value) fileInput.value.value = '';
    isModalOpen.value = true;
};

const openEditModal = (method: Courier) => {
    isEditing.value = true;
    currentId.value = method.id;
    form.clearErrors();
    form.name = method.name;
    form.code = method.code;
    form.description = method.description;
    form.is_active = method.is_active;
    form.logo_file = null;
    form._method = 'PUT';
    previewUrl.value = method.logo ? `/storage/${method.logo}` : null;
    if (fileInput.value) fileInput.value.value = '';
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value && currentId.value) {
        form.post(`/dashboard/couriers/${currentId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                isModalOpen.value = false;
            },
        });
    } else {
        form.post('/dashboard/couriers', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                isModalOpen.value = false;
            },
        });
    }
};

// Delete Confirmation
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref<Courier | null>(null);

const confirmDelete = (method: Courier) => {
    itemToDelete.value = method;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        router.delete(`/dashboard/couriers/${itemToDelete.value.id}`, {
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                itemToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Manajemen Kurir" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Kurir</h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kelola daftar bank, rekening, dan Kurir yang tersedia.
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <Button @click="openCreateModal" class="gap-2">
                    <Plus class="h-4 w-4" />
                    Tambah Kurir
                </Button>
            </div>
        </div>

        <div class="rounded-xl border bg-card shadow-xs">
            <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Tampilkan</span>
                    <Select v-model="perPage">
                        <SelectTrigger class="h-8 w-[72px] text-xs">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in perPageOptions"
                                :key="opt"
                                :value="opt"
                            >
                                {{ opt }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span>data</span>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 border-r pr-2 mr-2">
                        <Filter class="h-4 w-4 text-muted-foreground" />
                        <Select v-model="status">
                            <SelectTrigger class="h-8 w-[130px] bg-background text-xs">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="active">Aktif</SelectItem>
                                <SelectItem value="inactive">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="relative w-full max-w-xs sm:w-64">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            type="search"
                            placeholder="Cari Nama Kurir atau rekening..."
                            class="h-8 w-full bg-background pl-8 pr-8 text-xs"
                        />
                        <button
                            v-if="search"
                            type="button"
                            class="absolute top-2 right-2 text-muted-foreground hover:text-foreground"
                            @click="clearSearch"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="relative overflow-x-auto">
                <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-background/50 backdrop-blur-sm">
                    <Loader2 class="h-8 w-8 animate-spin text-primary" />
                </div>
                <table class="w-full caption-bottom text-sm">
                    <thead class="bg-muted/50 [&_tr]:border-b">
                        <tr class="border-b transition-colors">
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Logo</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Nama Kurir</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Kode Kurir</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Deskripsi</th>
                            <th class="h-10 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                            <th class="h-10 px-4 text-right align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="couriers.data.length === 0">
                            <td colspan="6" class="h-32 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <CreditCard class="h-8 w-8 text-muted-foreground/50" />
                                    <p>Tidak ada Kurir ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="method in couriers.data"
                            :key="method.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="flex h-10 w-16 items-center justify-center overflow-hidden rounded-md border bg-white">
                                    <img v-if="method.logo" :src="`/storage/${method.logo}`" :alt="method.name" class="h-full w-full object-contain p-1" />
                                    <ImageIcon v-else class="h-4 w-4 text-muted-foreground" />
                                </div>
                            </td>
                            <td class="p-4 align-middle font-medium">
                                {{ method.name }}
                            </td>
                            <td class="p-4 align-middle">
                                {{ method.code }}
                            </td>
                            <td class="p-4 align-middle">
                                {{ method.description }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <Badge :variant="method.is_active ? 'default' : 'secondary'">
                                    {{ method.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-[160px]">
                                        <DropdownMenuItem @click="openEditModal(method)" class="cursor-pointer">
                                            <Edit class="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="confirmDelete(method)"
                                            class="text-destructive focus:text-destructive cursor-pointer"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between" v-if="couriers.data.length > 0">
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ couriers.from || 0 }} sampai {{ couriers.to || 0 }} dari {{ couriers.total }} data
                </div>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in couriers.links" :key="i">
                        <Button
                            v-if="i === 0"
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, { search, status, per_page: perPage }, { preserveState: true })"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            v-else-if="i === couriers.links.length - 1"
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, { search, status, per_page: perPage }, { preserveState: true })"
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
                            @click="link.url && router.get(link.url, { search, status, per_page: perPage }, { preserveState: true })"
                        >
                            {{ link.label }}
                        </Button>
                    </template>
                </div>
            </div>
        </div>

        <Dialog v-model:open="isModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Kurir' : 'Tambah Kurir' }}</DialogTitle>
                    <DialogDescription>
                        Isi form di bawah ini untuk mengatur Kurir / rekening tujuan.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitForm">
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="name">Nama Kurir / Dompet Digital <span class="text-destructive">*</span></Label>
                            <Input id="name" v-model="form.name" placeholder="Contoh: Bank Mandiri / GoPay" required />
                            <span class="text-xs text-destructive" v-if="form.errors.name">{{ form.errors.name }}</span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="code">Kode Kurir (RajaOngkir) / No HP <span class="text-destructive">*</span></Label>
                            <Input id="code" v-model="form.code" placeholder="Contoh: 1234567890" required />
                            <span class="text-xs text-destructive" v-if="form.errors.code">{{ form.errors.code }}</span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">Deskripsi <span class="text-destructive">*</span></Label>
                            <Input id="description" v-model="form.description" placeholder="Contoh: PT Batik Sejahtera" required />
                            <span class="text-xs text-destructive" v-if="form.errors.description">{{ form.errors.description }}</span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="logo">Logo (Opsional)</Label>
                            <Input 
                                id="logo" 
                                type="file" 
                                accept="image/*" 
                                @change="handleLogoChange" 
                                ref="fileInput"
                            />
                            <div v-if="previewUrl" class="mt-2 w-32 h-16 border rounded bg-white p-1">
                                <img :src="previewUrl" alt="Preview Logo" class="w-full h-full object-contain" />
                            </div>
                            <span class="text-xs text-destructive" v-if="form.errors.logo_file">{{ form.errors.logo_file }}</span>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <Label for="is_active">Status Aktif</Label>
                            <label class="relative flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    v-model="form.is_active" 
                                    id="is_active" 
                                    class="peer sr-only"
                                />
                                <div class="peer h-6 w-11 rounded-full bg-input after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing" class="gap-2">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog v-model:open="isDeleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Kurir?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Anda yakin ingin menghapus Kurir <strong>{{ itemToDelete?.name }}</strong>? Data ini akan dihapus permanen.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction @click="executeDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                        Ya, Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>


