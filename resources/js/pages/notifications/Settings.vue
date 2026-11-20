<script setup lang="ts">
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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Bell,
    Check,
    ChevronLeft,
    Edit,
    Loader2,
    Mail,
    MessageSquare,
    MoreVertical,
    Plus,
    Search,
    Shield,
    Smartphone,
    Trash2,
    User,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface NotificationSetting {
    id: number;
    code: string;
    name: string;
    description: string | null;
    recipient_type: 'ALL_USERS' | 'CUSTOMER' | 'ADMIN' | 'SPECIFIC_USER';
    push_enabled: boolean | number;
    email_enabled: boolean | number;
    whatsapp_enabled: boolean | number;
    is_active: boolean | number;
    created_at: string;
}

interface Props {
    settings: NotificationSetting[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notifikasi', href: '/dashboard/notifications' },
    { title: 'Template & Pengaturan', href: '/dashboard/notification-settings' },
];

const search = ref('');
const filteredSettings = computed(() => {
    if (!search.value) return props.settings;
    const q = search.value.toLowerCase();
    return props.settings.filter(
        (s) =>
            s.name.toLowerCase().includes(q) ||
            s.code.toLowerCase().includes(q) ||
            (s.description && s.description.toLowerCase().includes(q))
    );
});

// Modal State (Create / Edit)
const isModalOpen = ref(false);
const editingSetting = ref<NotificationSetting | null>(null);

const form = useForm({
    id: null as number | null,
    code: '',
    name: '',
    description: '',
    recipient_type: 'ALL_USERS' as 'ALL_USERS' | 'CUSTOMER' | 'ADMIN' | 'SPECIFIC_USER',
    push_enabled: true,
    email_enabled: false,
    whatsapp_enabled: false,
    is_active: true,
});

const openCreateModal = () => {
    editingSetting.value = null;
    form.reset();
    form.clearErrors();
    form.id = null;
    form.code = '';
    form.name = '';
    form.description = '';
    form.recipient_type = 'ALL_USERS';
    form.push_enabled = true;
    form.email_enabled = false;
    form.whatsapp_enabled = false;
    form.is_active = true;
    isModalOpen.value = true;
};

const openEditModal = (setting: NotificationSetting) => {
    editingSetting.value = { ...setting };
    form.clearErrors();
    form.id = setting.id;
    form.code = setting.code;
    form.name = setting.name;
    form.description = setting.description || '';
    form.recipient_type = setting.recipient_type;
    form.push_enabled = setting.push_enabled === true || Number(setting.push_enabled) === 1;
    form.email_enabled = setting.email_enabled === true || Number(setting.email_enabled) === 1;
    form.whatsapp_enabled = setting.whatsapp_enabled === true || Number(setting.whatsapp_enabled) === 1;
    form.is_active = setting.is_active === true || Number(setting.is_active) === 1;
    isModalOpen.value = true;
};

const submitForm = () => {
    if (editingSetting.value && editingSetting.value.id) {
        form.put(`/dashboard/notification-settings/${editingSetting.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingSetting.value = null;
            },
        });
    } else {
        form.post('/dashboard/notification-settings', {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingSetting.value = null;
            },
        });
    }
};

// Quick Toggle Status in Table
const toggleStatus = (setting: NotificationSetting) => {
    router.post(`/dashboard/notification-settings/${setting.id}/toggle`, {}, {
        preserveScroll: true,
    });
};

// Delete Confirmation
const isDeleteDialogOpen = ref(false);
const settingToDelete = ref<NotificationSetting | null>(null);

const confirmDelete = (setting: NotificationSetting) => {
    settingToDelete.value = setting;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (settingToDelete.value) {
        router.delete(`/dashboard/notification-settings/${settingToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                settingToDelete.value = null;
            },
        });
    }
};

const getRecipientBadge = (type: string) => {
    switch (type) {
        case 'ALL_USERS':
            return { label: 'Semua Pengguna', icon: Users, class: 'bg-blue-600/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900' };
        case 'CUSTOMER':
            return { label: 'Pelanggan', icon: UserCheck, class: 'bg-emerald-600/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900' };
        case 'ADMIN':
            return { label: 'Admin', icon: Shield, class: 'bg-purple-600/10 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-900' };
        case 'SPECIFIC_USER':
            return { label: 'Pengguna Tertentu', icon: User, class: 'bg-amber-600/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900' };
        default:
            return { label: type, icon: Bell, class: '' };
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Template & Pengaturan Notifikasi" />

        <!-- Breadcrumbs -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <Button as-child variant="outline" size="icon" class="h-9 w-9">
                        <Link href="/dashboard/notifications">
                            <ChevronLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Template Pengaturan Notifikasi</h1>
                        <p class="text-sm text-muted-foreground">
                            Kelola template kode, default target penerima, dan aktivasi saluran pengiriman (Push, Email, WhatsApp).
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button @click="openCreateModal" class="gap-2 shadow-xs">
                    <Plus class="h-4 w-4" />
                    Tambah Template
                </Button>
            </div>
        </div>

        <!-- DataTable Container -->
        <div class="rounded-xl border bg-card shadow-xs">
            <!-- Controls -->
            <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-1 items-center gap-2 sm:max-w-xs">
                    <div class="relative w-full">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Cari nama atau kode template..."
                            class="w-full bg-background pl-8 text-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="relative overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="bg-muted/50 [&_tr]:border-b">
                        <tr class="border-b transition-colors">
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Kode</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Nama & Deskripsi</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Default Target</th>
                            <th class="h-10 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Saluran Aktif</th>
                            <th class="h-10 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                            <th class="h-10 px-4 text-right align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr
                            v-for="setting in filteredSettings"
                            :key="setting.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle font-mono text-xs font-semibold text-foreground">
                                {{ setting.code }}
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex flex-col">
                                    <span class="font-medium text-foreground">{{ setting.name }}</span>
                                    <span class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                                        {{ setting.description || 'Tidak ada deskripsi' }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 align-middle whitespace-nowrap">
                                <Badge
                                    variant="outline"
                                    :class="['text-xs font-medium px-2 py-0.5 border flex items-center gap-1.5 w-fit', getRecipientBadge(setting.recipient_type).class]"
                                >
                                    <component :is="getRecipientBadge(setting.recipient_type).icon" class="h-3 w-3" />
                                    {{ getRecipientBadge(setting.recipient_type).label }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <Badge
                                        :variant="Boolean(Number(setting.push_enabled) === 1 || setting.push_enabled === true) ? 'default' : 'secondary'"
                                        class="text-[10px] h-5 px-1.5"
                                        :class="Boolean(Number(setting.push_enabled) === 1 || setting.push_enabled === true) ? 'bg-primary' : 'opacity-50'"
                                        title="Push & Web Notification"
                                    >
                                        <Smartphone class="h-3 w-3 mr-1" />
                                        Push
                                    </Badge>
                                    <Badge
                                        :variant="Boolean(Number(setting.email_enabled) === 1 || setting.email_enabled === true) ? 'default' : 'secondary'"
                                        class="text-[10px] h-5 px-1.5"
                                        :class="Boolean(Number(setting.email_enabled) === 1 || setting.email_enabled === true) ? 'bg-emerald-600 text-white' : 'opacity-50'"
                                        title="Email Broadcast"
                                    >
                                        <Mail class="h-3 w-3 mr-1" />
                                        Email
                                    </Badge>
                                    <Badge
                                        :variant="Boolean(Number(setting.whatsapp_enabled) === 1 || setting.whatsapp_enabled === true) ? 'default' : 'secondary'"
                                        class="text-[10px] h-5 px-1.5"
                                        :class="Boolean(Number(setting.whatsapp_enabled) === 1 || setting.whatsapp_enabled === true) ? 'bg-emerald-600 text-white' : 'opacity-50'"
                                        title="WhatsApp Gateway"
                                    >
                                        <MessageSquare class="h-3 w-3 mr-1" />
                                        WA
                                    </Badge>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <button
                                    type="button"
                                    class="cursor-pointer transition-transform active:scale-95 outline-none"
                                    @click="toggleStatus(setting)"
                                    title="Klik untuk mengubah status aktif/nonaktif"
                                >
                                    <Badge
                                        :variant="Boolean(Number(setting.is_active) === 1 || setting.is_active === true) ? 'default' : 'secondary'"
                                        class="cursor-pointer font-medium text-xs px-2.5 py-0.5"
                                        :class="Boolean(Number(setting.is_active) === 1 || setting.is_active === true) ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-muted hover:bg-muted/80 text-muted-foreground'"
                                    >
                                        {{ Boolean(Number(setting.is_active) === 1 || setting.is_active === true) ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </button>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-muted-foreground hover:text-foreground"
                                        title="Edit Template"
                                        @click="openEditModal(setting)"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" class="h-8 w-8 p-0">
                                                <span class="sr-only">Buka menu</span>
                                                <MoreVertical class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="w-40">
                                            <DropdownMenuItem class="cursor-pointer" @select="openEditModal(setting)">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit Template
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                class="cursor-pointer text-destructive focus:text-destructive"
                                                @select="confirmDelete(setting)"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Hapus Template
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredSettings.length === 0">
                            <td colspan="6" class="h-32 text-center p-4 align-middle text-muted-foreground">
                                Tidak ada template notifikasi yang ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-[520px]">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingSetting ? 'Edit Template Notifikasi' : 'Tambah Template Notifikasi' }}
                    </DialogTitle>
                    <DialogDescription>
                        Konfigurasikan kode template, default kelompok penerima, dan saluran broadcast.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4 py-2">
                    <div class="grid gap-2">
                        <Label for="code">Kode Template (Unik)</Label>
                        <Input
                            id="code"
                            v-model="form.code"
                            placeholder="Contoh: ORDER_SUCCESS, PROMO_SALE"
                            class="uppercase font-mono text-sm bg-background"
                        />
                        <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Nama Notifikasi</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Contoh: Pesanan Berhasil Dibayar"
                            class="bg-background"
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Deskripsi</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            placeholder="Keterangan singkat fungsi template notifikasi ini..."
                            class="bg-background"
                        />
                    </div>

                    <!-- Target Penerima Cards Selector -->
                    <div class="space-y-2">
                        <Label>Default Target Penerima</Label>
                        <div class="grid grid-cols-2 gap-2">
                            <div
                                v-for="target in [
                                    { value: 'ALL_USERS', label: 'Semua Pengguna', icon: Users },
                                    { value: 'CUSTOMER', label: 'Pelanggan', icon: UserCheck },
                                    { value: 'ADMIN', label: 'Admin', icon: Shield },
                                    { value: 'SPECIFIC_USER', label: 'Pengguna Tertentu', icon: User },
                                ]"
                                :key="target.value"
                                @click="form.recipient_type = target.value as any"
                                class="flex items-center justify-between rounded-lg border p-2.5 cursor-pointer transition-all"
                                :class="form.recipient_type === target.value ? 'border-primary bg-primary/5 font-semibold text-primary shadow-xs' : 'border-border text-muted-foreground hover:bg-muted/40'"
                            >
                                <div class="flex items-center gap-2">
                                    <component :is="target.icon" class="h-4 w-4 shrink-0" />
                                    <span class="text-xs">{{ target.label }}</span>
                                </div>
                                <Check v-if="form.recipient_type === target.value" class="h-3.5 w-3.5 text-primary" />
                            </div>
                        </div>
                        <p v-if="form.errors.recipient_type" class="text-xs text-destructive">{{ form.errors.recipient_type }}</p>
                    </div>

                    <!-- Channel Toggles with Switch -->
                    <div class="space-y-2 pt-2 border-t">
                        <Label class="text-xs text-muted-foreground uppercase font-semibold">Saluran Pengiriman</Label>
                        <div class="space-y-2">
                            <!-- Push -->
                            <div class="flex items-center justify-between rounded-lg border p-2.5 bg-card">
                                <div class="flex items-center gap-2.5">
                                    <Smartphone class="h-4 w-4 text-primary" />
                                    <div class="flex flex-col">
                                        <span class="text-xs font-semibold text-foreground">Push & In-App Web</span>
                                        <span class="text-[11px] text-muted-foreground">Notifikasi lonceng & inbox web</span>
                                    </div>
                                </div>
                                <Switch :checked="form.push_enabled" @update:checked="(val: boolean) => form.push_enabled = val" />
                            </div>

                            <!-- Email -->
                            <div class="flex items-center justify-between rounded-lg border p-2.5 bg-card">
                                <div class="flex items-center gap-2.5">
                                    <Mail class="h-4 w-4 text-emerald-600" />
                                    <div class="flex flex-col">
                                        <span class="text-xs font-semibold text-foreground">Email Broadcast</span>
                                        <span class="text-[11px] text-muted-foreground">Kirim email ke alamat user</span>
                                    </div>
                                </div>
                                <Switch :checked="form.email_enabled" @update:checked="(val: boolean) => form.email_enabled = val" />
                            </div>

                            <!-- WhatsApp -->
                            <div class="flex items-center justify-between rounded-lg border p-2.5 bg-card">
                                <div class="flex items-center gap-2.5">
                                    <MessageSquare class="h-4 w-4 text-emerald-600" />
                                    <div class="flex flex-col">
                                        <span class="text-xs font-semibold text-foreground">WhatsApp Gateway</span>
                                        <span class="text-[11px] text-muted-foreground">Pesan WhatsApp ke nomor handphone</span>
                                    </div>
                                </div>
                                <Switch :checked="form.whatsapp_enabled" @update:checked="(val: boolean) => form.whatsapp_enabled = val" />
                            </div>
                        </div>
                    </div>

                    <!-- Status Aktif Toggle with Switch -->
                    <div class="flex items-center justify-between rounded-lg border p-3 bg-muted/20">
                        <div class="flex flex-col">
                            <span class="text-xs font-semibold text-foreground">Status Aktif Template</span>
                            <span class="text-[11px] text-muted-foreground">Template aktif dapat dipilih di form kirim notifikasi</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium" :class="form.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'">
                                {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <Switch :checked="form.is_active" @update:checked="(val: boolean) => form.is_active = val" />
                        </div>
                    </div>

                    <DialogFooter class="pt-3">
                        <Button type="button" variant="outline" @click="isModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                            {{ editingSetting ? 'Simpan Perubahan' : 'Simpan Template' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Template Notifikasi?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Anda yakin ingin menghapus template <strong>{{ settingToDelete?.name }}</strong> ({{ settingToDelete?.code }})?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="executeDelete"
                    >
                        Hapus Template
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
