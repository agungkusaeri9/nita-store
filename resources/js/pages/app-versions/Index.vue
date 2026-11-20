<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    Download,
    Edit,
    FileArchive,
    Filter,
    Loader2,
    MoreVertical,
    Plus,
    Power,
    Search,
    ShieldAlert,
    Smartphone,
    Star,
    Trash2,
    Wrench,
    X,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
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
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import type { BreadcrumbItem } from '@/types';

const { can } = usePermissions();

interface AppVersionItem {
    id: number;
    name: string;
    type: 'android' | 'ios';
    version: string;
    version_code: number;
    min_version: string | null;
    file_path: string | null;
    file_name: string | null;
    file_size: number | null;
    file_url: string | null;
    download_url: string | null;
    release_notes: string | null;
    is_latest: boolean;
    is_force_update: boolean;
    is_maintenance: boolean;
    maintenance_message: string | null;
    is_active: boolean;
    released_at: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginationData {
    data: AppVersionItem[];
    current_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    appVersions: PaginationData;
    filters: {
        search?: string;
        type?: string;
        status?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengaturan', href: '#' },
    { title: 'Manajemen Aplikasi', href: '/dashboard/app-versions' },
];

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || 'all');
const statusFilter = ref(props.filters.status || 'all');
const perPage = ref(props.filters.per_page?.toString() || '10');
const perPageOptions = ['10', '25', '50', '100'];

let searchTimeout: ReturnType<typeof setTimeout>;
watch([search, typeFilter, statusFilter, perPage], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/dashboard/app-versions',
            {
                search: search.value,
                type: typeFilter.value,
                status: statusFilter.value,
                per_page: perPage.value,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});

const clearSearch = () => {
    search.value = '';
};

const formatFileSize = (bytes: number | null): string => {
    if (!bytes || bytes <= 0) {
return '-';
}

    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(unitIndex === 0 ? 0 : 2)} ${units[unitIndex]}`;
};

const copiedId = ref<number | null>(null);
const copyFileUrl = async (item: AppVersionItem) => {
    if (!item.file_url) {
return;
}

    try {
        await navigator.clipboard.writeText(item.file_url);
        copiedId.value = item.id;
        setTimeout(() => {
            if (copiedId.value === item.id) {
copiedId.value = null;
}
        }, 2000);
    } catch {
        // ignore
    }
};

const isModalOpen = ref(false);
const editingItem = ref<AppVersionItem | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFileName = ref<string | null>(null);

const form = useForm({
    _method: 'POST',
    name: 'Heritage Batik Lewayan',
    type: 'android' as 'android' | 'ios',
    version: '1.0.0',
    version_code: 1,
    min_version: '1.0.0',
    file: null as File | null,
    release_notes: '',
    is_latest: true,
    is_force_update: false,
    is_maintenance: false,
    maintenance_message: '',
    is_active: true,
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        form.file = target.files[0];
        selectedFileName.value = `${target.files[0].name} (${formatFileSize(target.files[0].size)})`;
    } else {
        form.file = null;
        selectedFileName.value = null;
    }
};

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    form._method = 'POST';
    form.name = 'Heritage Batik Lewayan';
    form.type = 'android';
    form.version = '1.0.0';
    form.version_code = 1;
    form.min_version = '1.0.0';
    form.file = null;
    form.release_notes = '';
    form.is_latest = true;
    form.is_force_update = false;
    form.is_maintenance = false;
    form.maintenance_message = '';
    form.is_active = true;
    selectedFileName.value = null;

    if (fileInput.value) {
fileInput.value.value = '';
}

    isModalOpen.value = true;
};

const openEditModal = (item: AppVersionItem) => {
    editingItem.value = item;
    form.clearErrors();
    form._method = 'PUT';
    form.name = item.name;
    form.type = item.type;
    form.version = item.version;
    form.version_code = item.version_code;
    form.min_version = item.min_version || '';
    form.file = null;
    form.release_notes = item.release_notes || '';
    form.is_latest = item.is_latest;
    form.is_force_update = item.is_force_update;
    form.is_maintenance = item.is_maintenance;
    form.maintenance_message = item.maintenance_message || '';
    form.is_active = item.is_active;
    selectedFileName.value = null;

    if (fileInput.value) {
fileInput.value.value = '';
}

    isModalOpen.value = true;
};

const submitForm = () => {
    if (editingItem.value) {
        form.post(`/dashboard/app-versions/${editingItem.value.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingItem.value = null;
            },
        });
    } else {
        form.post('/dashboard/app-versions', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingItem.value = null;
            },
        });
    }
};

const toggleActive = (item: AppVersionItem) => {
    router.post(`/dashboard/app-versions/${item.id}/toggle`, {}, { preserveScroll: true });
};

const setAsLatest = (item: AppVersionItem) => {
    router.post(`/dashboard/app-versions/${item.id}/set-latest`, {}, { preserveScroll: true });
};

const toggleForceUpdate = (item: AppVersionItem) => {
    router.post(`/dashboard/app-versions/${item.id}/toggle-force-update`, {}, { preserveScroll: true });
};

const isDeleteDialogOpen = ref(false);
const itemToDelete = ref<AppVersionItem | null>(null);

const confirmDelete = (item: AppVersionItem) => {
    itemToDelete.value = item;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        router.delete(`/dashboard/app-versions/${itemToDelete.value.id}`, {
            preserveScroll: true,
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
        <Head title="Manajemen Aplikasi" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <Smartphone class="h-8 w-8 text-primary" />
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                        Manajemen Aplikasi
                    </h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kelola rilis versi aplikasi mobile (Android & iOS), file instalasi (APK/IPA), wajib update, dan status maintenance.
                </p>
            </div>

            <Button v-if="can('create app versions')" @click="openCreateModal" class="gap-2">
                <Plus class="h-4 w-4" />
                Tambah Versi Aplikasi
            </Button>
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
                            <SelectItem v-for="opt in perPageOptions" :key="opt" :value="opt">
                                {{ opt }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span>data</span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 border-r pr-2 mr-1">
                        <Filter class="h-4 w-4 text-muted-foreground" />
                        <Select v-model="typeFilter">
                            <SelectTrigger class="h-8 w-[140px] bg-background text-xs">
                                <SelectValue placeholder="Tipe Platform" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Platform</SelectItem>
                                <SelectItem value="android">Android</SelectItem>
                                <SelectItem value="ios">iOS</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="statusFilter">
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
                            placeholder="Cari versi, nama, atau catatan..."
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

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground text-xs uppercase">
                        <tr>
                            <th class="h-10 px-4 text-left font-medium">Aplikasi & Platform</th>
                            <th class="h-10 px-4 text-left font-medium">Versi & Build</th>
                            <th class="h-10 px-4 text-left font-medium">File Aplikasi</th>
                            <th class="h-10 px-4 text-left font-medium">Catatan Rilis</th>
                            <th class="h-10 px-4 text-center font-medium">Kebijakan & Status</th>
                            <th class="h-10 px-4 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="appVersions.data.length === 0">
                            <td colspan="6" class="p-8 text-center text-muted-foreground">
                                Tidak ada data versi aplikasi ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="item in appVersions.data"
                            :key="item.id"
                            class="transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-top">
                                <div class="space-y-1">
                                    <div class="font-medium text-foreground">
                                        {{ item.name }}
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <Badge
                                            :variant="item.type === 'android' ? 'default' : 'secondary'"
                                            class="text-[11px] uppercase"
                                        >
                                            {{ item.type === 'android' ? 'Android' : 'iOS' }}
                                        </Badge>
                                        <Badge
                                            v-if="item.is_latest"
                                            variant="outline"
                                            class="text-[10px] gap-1 border-emerald-500 text-emerald-600 bg-emerald-500/10"
                                        >
                                            <Star class="h-3 w-3 fill-current" />
                                            Versi Utama (isLatest)
                                        </Badge>
                                    </div>
                                </div>
                            </td>

                            <td class="p-4 align-top whitespace-nowrap">
                                <div class="space-y-1">
                                    <div class="font-mono text-sm font-semibold text-foreground">
                                        v{{ item.version }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        Build #{{ item.version_code }}
                                        <span v-if="item.min_version"> • Min: v{{ item.min_version }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="p-4 align-top">
                                <div v-if="item.file_path" class="space-y-1.5">
                                    <div class="flex items-center gap-1.5 text-xs font-medium text-foreground">
                                        <FileArchive class="h-4 w-4 shrink-0 text-primary" />
                                        <span class="truncate max-w-[200px]" :title="item.file_name || item.file_path">
                                            {{ item.file_name || item.file_path }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <span>{{ formatFileSize(item.file_size) }}</span>
                                        <a
                                            :href="`/dashboard/app-versions/${item.id}/download`"
                                            class="inline-flex items-center gap-1 text-primary hover:underline font-medium"
                                        >
                                            <Download class="h-3 w-3" />
                                            Unduh
                                        </a>
                                        <button
                                            v-if="item.file_url"
                                            type="button"
                                            @click="copyFileUrl(item)"
                                            class="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground"
                                            title="Salin Link File"
                                        >
                                            <Check v-if="copiedId === item.id" class="h-3 w-3 text-green-600" />
                                            <Copy v-else class="h-3 w-3" />
                                            <span>{{ copiedId === item.id ? 'Tersalin' : 'Salin URL' }}</span>
                                        </button>
                                    </div>
                                </div>
                                <span v-else class="text-xs text-muted-foreground italic">
                                    Belum ada file diunggah
                                </span>
                            </td>

                            <td class="p-4 align-top max-w-xs">
                                <p v-if="item.release_notes" class="text-xs text-muted-foreground whitespace-pre-line line-clamp-3">
                                    {{ item.release_notes }}
                                </p>
                                <span v-else class="text-xs text-muted-foreground/60 italic">-</span>
                            </td>

                            <td class="p-4 align-top text-center whitespace-nowrap">
                                <div class="flex flex-col items-center gap-1.5">
                                    <Badge :variant="item.is_active ? 'default' : 'secondary'" class="text-[11px]">
                                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                    <Badge
                                        v-if="item.is_force_update"
                                        variant="destructive"
                                        class="text-[10px] gap-1"
                                    >
                                        <ShieldAlert class="h-3 w-3" />
                                        Wajib Update
                                    </Badge>
                                    <Badge
                                        v-if="item.is_maintenance"
                                        variant="outline"
                                        class="text-[10px] gap-1 border-amber-500 text-amber-600"
                                    >
                                        <Wrench class="h-3 w-3" />
                                        Maintenance
                                    </Badge>
                                </div>
                            </td>

                            <td class="p-4 align-top text-right whitespace-nowrap">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-48">
                                        <DropdownMenuItem
                                            v-if="item.file_path"
                                            as-child
                                        >
                                            <a :href="`/dashboard/app-versions/${item.id}/download`" class="cursor-pointer flex items-center">
                                                <Download class="mr-2 h-4 w-4" />
                                                Unduh File
                                            </a>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="can('edit app versions') && !item.is_latest"
                                            @click="setAsLatest(item)"
                                            class="cursor-pointer font-medium text-emerald-600"
                                        >
                                            <Star class="mr-2 h-4 w-4" />
                                            Jadikan Versi Utama (Latest)
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="can('edit app versions')"
                                            @click="toggleActive(item)"
                                            class="cursor-pointer"
                                        >
                                            <Power class="mr-2 h-4 w-4" />
                                            {{ item.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="can('edit app versions')"
                                            @click="toggleForceUpdate(item)"
                                            class="cursor-pointer"
                                        >
                                            <ShieldAlert class="mr-2 h-4 w-4" />
                                            {{ item.is_force_update ? 'Matikan Wajib Update' : 'Aktifkan Wajib Update' }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="can('edit app versions')"
                                            @click="openEditModal(item)"
                                            class="cursor-pointer"
                                        >
                                            <Edit class="mr-2 h-4 w-4" />
                                            Edit Versi
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="can('delete app versions')"
                                            @click="confirmDelete(item)"
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

            <div
                class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between"
                v-if="appVersions.data.length > 0"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ appVersions.from || 0 }} sampai {{ appVersions.to || 0 }} dari {{ appVersions.total }} data
                </div>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in appVersions.links" :key="i">
                        <Button
                            v-if="link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-8 px-3 text-xs"
                            @click="router.get(link.url, { search, type: typeFilter, status: statusFilter, per_page: perPage }, { preserveState: true })"
                        >
                            <span v-html="link.label"></span>
                        </Button>
                        <span v-else class="px-2 py-1 text-xs text-muted-foreground" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </div>

        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingItem ? 'Edit Versi Aplikasi' : 'Tambah Versi Aplikasi Baru' }}
                    </DialogTitle>
                    <DialogDescription>
                        Unggah file instalasi aplikasi (.apk / .aab / .ipa) beserta informasi versi dan catatan rilis.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4 py-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="name">Nama Aplikasi <span class="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Heritage Batik Lewayan"
                                class="bg-background"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="type">Tipe Platform <span class="text-destructive">*</span></Label>
                            <Select v-model="form.type">
                                <SelectTrigger id="type" class="bg-background">
                                    <SelectValue placeholder="Pilih Platform" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="android">Android</SelectItem>
                                    <SelectItem value="ios">iOS</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <Label for="version">Versi (SemVer) <span class="text-destructive">*</span></Label>
                            <Input
                                id="version"
                                v-model="form.version"
                                placeholder="Contoh: 1.0.0"
                                class="bg-background font-mono"
                                :class="{ 'border-destructive': form.errors.version }"
                            />
                            <p v-if="form.errors.version" class="text-xs text-destructive">{{ form.errors.version }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="version_code">Version Code / Build <span class="text-destructive">*</span></Label>
                            <Input
                                id="version_code"
                                type="number"
                                min="1"
                                v-model="form.version_code"
                                placeholder="1"
                                class="bg-background font-mono"
                                :class="{ 'border-destructive': form.errors.version_code }"
                            />
                            <p v-if="form.errors.version_code" class="text-xs text-destructive">{{ form.errors.version_code }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="min_version">Versi Minimum</Label>
                            <Input
                                id="min_version"
                                v-model="form.min_version"
                                placeholder="Contoh: 1.0.0"
                                class="bg-background font-mono"
                                :class="{ 'border-destructive': form.errors.min_version }"
                            />
                            <p v-if="form.errors.min_version" class="text-xs text-destructive">{{ form.errors.min_version }}</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="file">
                            File Aplikasi (.apk, .aab, .ipa)
                            <span v-if="!editingItem" class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="file"
                            type="file"
                            accept=".apk,.aab,.ipa,.zip"
                            ref="fileInput"
                            @change="handleFileChange"
                            class="bg-background cursor-pointer"
                            :class="{ 'border-destructive': form.errors.file }"
                        />
                        <p v-if="selectedFileName" class="text-xs text-primary font-medium">
                            File dipilih: {{ selectedFileName }}
                        </p>
                        <p v-else-if="editingItem?.file_name" class="text-xs text-muted-foreground">
                            File saat ini: <strong>{{ editingItem.file_name }}</strong> ({{ formatFileSize(editingItem.file_size) }}). Kosongkan jika tidak ingin mengganti file.
                        </p>
                        <div v-if="form.progress" class="w-full bg-muted rounded-full h-2 overflow-hidden mt-1">
                            <div
                                class="bg-primary h-full transition-all duration-200"
                                :style="{ width: `${form.progress.percentage}%` }"
                            ></div>
                        </div>
                        <p v-if="form.errors.file" class="text-xs text-destructive">{{ form.errors.file }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="release_notes">Catatan Rilis (Changelog)</Label>
                        <Textarea
                            id="release_notes"
                            v-model="form.release_notes"
                            rows="3"
                            placeholder="Tuliskan pembaruan atau perbaikan pada versi ini..."
                            class="bg-background text-sm"
                            :class="{ 'border-destructive': form.errors.release_notes }"
                        />
                        <p v-if="form.errors.release_notes" class="text-xs text-destructive">{{ form.errors.release_notes }}</p>
                    </div>

                    <div class="space-y-3 rounded-lg border p-3 bg-muted/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <Label for="is_latest" class="font-medium">Jadikan Versi Utama (isLatest)</Label>
                                <p class="text-xs text-muted-foreground">Tandai sebagai file aplikasi utama yang diunduh di Landing Page & Mobile.</p>
                            </div>
                            <label class="relative flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_latest" id="is_latest" class="peer sr-only" />
                                <div class="peer h-6 w-11 rounded-full bg-input after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between border-t pt-3">
                            <div>
                                <Label for="is_active" class="font-medium">Status Aktif</Label>
                                <p class="text-xs text-muted-foreground">Versi ini aktif dan dapat diakses oleh aplikasi mobile.</p>
                            </div>
                            <label class="relative flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" id="is_active" class="peer sr-only" />
                                <div class="peer h-6 w-11 rounded-full bg-input after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between border-t pt-3">
                            <div>
                                <Label for="is_force_update" class="font-medium">Wajib Update (Force Update)</Label>
                                <p class="text-xs text-muted-foreground">Pengguna versi lama wajib memperbarui aplikasi ke versi ini.</p>
                            </div>
                            <label class="relative flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_force_update" id="is_force_update" class="peer sr-only" />
                                <div class="peer h-6 w-11 rounded-full bg-input after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-destructive peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between border-t pt-3">
                            <div>
                                <Label for="is_maintenance" class="font-medium">Mode Maintenance</Label>
                                <p class="text-xs text-muted-foreground">Tampilkan halaman pemeliharaan pada aplikasi.</p>
                            </div>
                            <label class="relative flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_maintenance" id="is_maintenance" class="peer sr-only" />
                                <div class="peer h-6 w-11 rounded-full bg-input after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-amber-500 peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                            </label>
                        </div>

                        <div v-if="form.is_maintenance" class="space-y-1.5 border-t pt-3">
                            <Label for="maintenance_message">Pesan Maintenance</Label>
                            <Textarea
                                id="maintenance_message"
                                v-model="form.maintenance_message"
                                rows="2"
                                placeholder="Aplikasi sedang dalam pemeliharaan rutin. Silakan coba beberapa saat lagi."
                                class="bg-background text-sm"
                            />
                        </div>
                    </div>

                    <DialogFooter class="pt-2">
                        <Button type="button" variant="outline" @click="isModalOpen = false">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                            {{ editingItem ? 'Simpan Perubahan' : 'Tambah Versi' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Versi Aplikasi?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Anda yakin ingin menghapus <strong>{{ itemToDelete?.name }} ({{ itemToDelete?.type.toUpperCase() }} v{{ itemToDelete?.version }})</strong> beserta file aplikasinya? Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="executeDelete"
                    >
                        Hapus Versi
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
