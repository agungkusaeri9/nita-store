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
import { Textarea } from '@/components/ui/textarea';
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
    ChevronLeft,
    ChevronRight,
    Copy,
    Check,
    Edit,
    Eye,
    EyeOff,
    Filter,
    KeyRound,
    Loader2,
    MapPin,
    MoreVertical,
    Plus,
    Search,
    SlidersHorizontal,
    Trash2,
    Truck,
    X,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface AppConfigItem {
    id: number;
    key: string;
    value: string | null;
    description: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginationData {
    data: AppConfigItem[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    configs: PaginationData;
    filters: {
        search?: string;
        sort?: string;
        direction?: string;
        per_page?: string | number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Konfigurasi', href: '#' },
    { title: 'Semua Konfigurasi', href: '/dashboard/configurations' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page?.toString() || '10');
const isLoading = ref(false);
const revealedIds = ref<number[]>([]);
const copiedKey = ref<string | null>(null);

let searchTimeout: ReturnType<typeof setTimeout>;

const fetchConfigs = () => {
    isLoading.value = true;
    router.get(
        '/dashboard/configurations',
        {
            search: search.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => (isLoading.value = false),
        }
    );
};

watch(search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchConfigs, 400);
    }
});

watch(perPage, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        fetchConfigs();
    }
});

const clearSearch = () => {
    search.value = '';
};

const toggleReveal = (id: number) => {
    if (revealedIds.value.includes(id)) {
        revealedIds.value = revealedIds.value.filter((i) => i !== id);
    } else {
        revealedIds.value.push(id);
    }
};

const copyToClipboard = async (text: string, identifier: string) => {
    try {
        await navigator.clipboard.writeText(text);
        copiedKey.value = identifier;
        setTimeout(() => {
            if (copiedKey.value === identifier) {
                copiedKey.value = null;
            }
        }, 2000);
    } catch {
        // fallback
    }
};

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<number | null>(null);

const form = useForm({
    key: '',
    value: '',
    description: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    currentId.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (item: AppConfigItem) => {
    isEditing.value = true;
    currentId.value = item.id;
    form.key = item.key;
    form.value = item.value ?? '';
    form.description = item.description ?? '';
    form.clearErrors();
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value && currentId.value) {
        form.put(`/dashboard/app-configs/${currentId.value}`, {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/dashboard/app-configs', {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

// Delete Confirmation
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref<AppConfigItem | null>(null);

const confirmDelete = (item: AppConfigItem) => {
    itemToDelete.value = item;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        router.delete(`/dashboard/app-configs/${itemToDelete.value.id}`, {
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                itemToDelete.value = null;
            },
        });
    }
};

const isKeySensitive = (key: string): boolean => {
    const lower = key.toLowerCase();
    return (
        lower.includes('key') ||
        lower.includes('secret') ||
        lower.includes('token') ||
        lower.includes('password')
    );
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Manajemen Konfigurasi Aplikasi" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                        Konfigurasi Aplikasi
                    </h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kelola konfigurasi sistem secara fleksibel dengan pasangan key, value, dan deskripsi.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Link href="/dashboard/configurations/rajaongkir">
                    <Button variant="outline" size="sm" class="gap-1.5">
                        <Truck class="h-4 w-4 text-blue-600" />
                        RajaOngkir
                    </Button>
                </Link>
                <Link href="/dashboard/configurations/geoapify">
                    <Button variant="outline" size="sm" class="gap-1.5">
                        <MapPin class="h-4 w-4 text-emerald-600" />
                        Geoapify
                    </Button>
                </Link>
                <Button @click="openCreateModal" size="sm" class="gap-1.5">
                    <Plus class="h-4 w-4" />
                    Tambah Konfigurasi
                </Button>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="flex flex-col gap-3 rounded-lg border bg-card p-4 text-card-foreground shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="relative flex-1 max-w-md">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="search"
                    placeholder="Cari berdasarkan key, value, atau deskripsi..."
                    class="pl-9 pr-9"
                />
                <button
                    v-if="search"
                    @click="clearSearch"
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground whitespace-nowrap">Tampilkan:</span>
                <Select v-model="perPage">
                    <SelectTrigger class="w-[75px] h-9">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Table Card -->
        <div class="overflow-hidden rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="relative overflow-x-auto">
                <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-background/50 backdrop-blur-xs">
                    <Loader2 class="h-6 w-6 animate-spin text-primary" />
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-4 py-3.5 font-medium">Key</th>
                            <th scope="col" class="px-4 py-3.5 font-medium">Value</th>
                            <th scope="col" class="px-4 py-3.5 font-medium">Deskripsi</th>
                            <th scope="col" class="px-4 py-3.5 font-medium">Terakhir Diperbarui</th>
                            <th scope="col" class="px-4 py-3.5 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="item in configs.data"
                            :key="item.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <!-- Key Column -->
                            <td class="px-4 py-3.5 align-top">
                                <div class="flex items-center gap-2">
                                    <code class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold text-foreground">
                                        {{ item.key }}
                                    </code>
                                    <button
                                        @click="copyToClipboard(item.key, 'key-' + item.id)"
                                        type="button"
                                        title="Salin Key"
                                        class="text-muted-foreground hover:text-foreground transition-colors"
                                    >
                                        <Check v-if="copiedKey === 'key-' + item.id" class="h-3.5 w-3.5 text-green-600" />
                                        <Copy v-else class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>

                            <!-- Value Column -->
                            <td class="px-4 py-3.5 align-top">
                                <div v-if="item.value !== null && item.value !== ''" class="flex items-center gap-2 max-w-md">
                                    <template v-if="isKeySensitive(item.key)">
                                        <span v-if="!revealedIds.includes(item.id)" class="font-mono text-xs text-muted-foreground">
                                            ••••••••••••••••••••
                                        </span>
                                        <span v-else class="font-mono text-xs break-all text-foreground">
                                            {{ item.value }}
                                        </span>
                                        <button
                                            @click="toggleReveal(item.id)"
                                            type="button"
                                            class="text-muted-foreground hover:text-foreground transition-colors"
                                            :title="revealedIds.includes(item.id) ? 'Sembunyikan' : 'Tampilkan'"
                                        >
                                            <EyeOff v-if="revealedIds.includes(item.id)" class="h-3.5 w-3.5" />
                                            <Eye v-else class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                    <template v-else>
                                        <span class="text-xs break-all text-foreground line-clamp-2">
                                            {{ item.value }}
                                        </span>
                                    </template>
                                    <button
                                        @click="copyToClipboard(item.value, 'val-' + item.id)"
                                        type="button"
                                        title="Salin Nilai"
                                        class="text-muted-foreground hover:text-foreground transition-colors ml-auto shrink-0"
                                    >
                                        <Check v-if="copiedKey === 'val-' + item.id" class="h-3.5 w-3.5 text-green-600" />
                                        <Copy v-else class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                                <div v-else>
                                    <Badge variant="outline" class="text-[11px] text-amber-600 border-amber-200 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-900/50">
                                        (Kosong)
                                    </Badge>
                                </div>
                            </td>

                            <!-- Description Column -->
                            <td class="px-4 py-3.5 align-top">
                                <span v-if="item.description" class="text-xs text-muted-foreground">
                                    {{ item.description }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground/60 italic">-</span>
                            </td>

                            <!-- Updated At Column -->
                            <td class="px-4 py-3.5 align-top text-xs text-muted-foreground whitespace-nowrap">
                                {{ new Date(item.updated_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) }}
                            </td>

                            <!-- Action Column -->
                            <td class="px-4 py-3.5 align-top text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-36">
                                        <DropdownMenuItem @click="openEditModal(item)">
                                            <Edit class="mr-2 h-4 w-4" />
                                            Ubah
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="confirmDelete(item)" class="text-destructive focus:text-destructive">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="configs.data.length === 0">
                            <td colspan="5" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <SlidersHorizontal class="h-10 w-10 text-muted-foreground/40" />
                                    <h3 class="text-sm font-medium text-foreground">Belum ada konfigurasi</h3>
                                    <p class="text-xs text-muted-foreground max-w-sm">
                                        {{ search ? 'Tidak ada konfigurasi yang cocok dengan pencarian Anda.' : 'Tambahkan konfigurasi baru untuk menyimpan parameter atau API key.' }}
                                    </p>
                                    <Button v-if="!search" @click="openCreateModal" size="sm" class="mt-2 gap-1.5">
                                        <Plus class="h-4 w-4" />
                                        Tambah Konfigurasi
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="configs.total > 0"
                class="flex flex-col gap-4 border-t p-4 sm:flex-row sm:items-center sm:justify-between text-xs text-muted-foreground"
            >
                <div>
                    Menampilkan <span class="font-medium text-foreground">{{ configs.from ?? 0 }}</span> sampai
                    <span class="font-medium text-foreground">{{ configs.to ?? 0 }}</span> dari
                    <span class="font-medium text-foreground">{{ configs.total }}</span> konfigurasi
                </div>

                <div class="flex items-center gap-1.5">
                    <template v-for="(link, i) in configs.links" :key="i">
                        <Button
                            v-if="link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-8 min-w-8 px-2 text-xs"
                            as-child
                        >
                            <Link :href="link.url" preserve-scroll preserve-state>
                                <span v-html="link.label"></span>
                            </Link>
                        </Button>
                        <Button
                            v-else
                            variant="ghost"
                            size="sm"
                            class="h-8 min-w-8 px-2 text-xs text-muted-foreground/50 pointer-events-none"
                            disabled
                        >
                            <span v-html="link.label"></span>
                        </Button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <Dialog :open="isModalOpen" @update:open="(val: boolean) => (isModalOpen = val)">
            <DialogContent class="sm:max-w-lg">
                <form @submit.prevent="submitForm" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {{ isEditing ? 'Ubah Konfigurasi' : 'Tambah Konfigurasi Baru' }}
                        </DialogTitle>
                        <DialogDescription>
                            {{ isEditing ? 'Perbarui data konfigurasi di bawah ini.' : 'Masukkan key unik, nilai, dan deskripsi konfigurasi baru.' }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4 py-2">
                        <!-- Key Input -->
                        <div class="space-y-1.5">
                            <Label for="key" class="text-xs font-semibold">
                                Key Konfigurasi <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="key"
                                v-model="form.key"
                                placeholder="misal: rajaongkir_api_key, geoapify_api_key"
                                :disabled="form.processing"
                                class="font-mono text-xs"
                            />
                            <p v-if="form.errors.key" class="text-xs text-destructive">
                                {{ form.errors.key }}
                            </p>
                        </div>

                        <!-- Value Input -->
                        <div class="space-y-1.5">
                            <Label for="value" class="text-xs font-semibold">
                                Nilai (Value)
                            </Label>
                            <Textarea
                                id="value"
                                v-model="form.value"
                                placeholder="Masukkan nilai konfigurasi (boleh dikosongkan)"
                                :disabled="form.processing"
                                rows="3"
                                class="font-mono text-xs"
                            />
                            <p v-if="form.errors.value" class="text-xs text-destructive">
                                {{ form.errors.value }}
                            </p>
                        </div>

                        <!-- Description Input -->
                        <div class="space-y-1.5">
                            <Label for="description" class="text-xs font-semibold">
                                Deskripsi (Opsional)
                            </Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                placeholder="Keterangan fungsi konfigurasi ini"
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.description" class="text-xs text-destructive">
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isModalOpen = false"
                            :disabled="form.processing"
                        >
                            Batal
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="gap-1.5">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            {{ isEditing ? 'Simpan Perubahan' : 'Tambah Konfigurasi' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Alert Dialog -->
        <AlertDialog :open="isDeleteDialogOpen" @update:open="(val: boolean) => (isDeleteDialogOpen = val)">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Konfigurasi?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus konfigurasi
                        <code class="font-mono font-semibold text-foreground">
                            {{ itemToDelete?.key }}
                        </code>? Tindakan ini tidak dapat dibatalkan dan sistem yang bergantung pada key ini mungkin terpengaruh.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="isDeleteDialogOpen = false">
                        Batal
                    </AlertDialogCancel>
                    <AlertDialogAction
                        @click="executeDelete"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>

