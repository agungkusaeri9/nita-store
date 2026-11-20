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
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import {
    Bell,
    BellRing,
    CheckCheck,
    ChevronLeft,
    ChevronRight,
    Clock,
    Eye,
    Filter,
    Loader2,
    Mail,
    MessageSquare,
    MoreVertical,
    Plus,
    Search,
    Settings,
    Shield,
    Smartphone,
    Trash2,
    User,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface NotificationSetting {
    id: number;
    code: string;
    name: string;
    description: string | null;
    recipient_type: string;
    push_enabled: boolean;
    email_enabled: boolean;
    whatsapp_enabled: boolean;
    is_active: boolean;
}

interface Creator {
    id: number;
    name: string;
    email: string;
}

interface NotificationRecipientItem {
    id: number;
    user_id: number;
    is_read: boolean;
    read_at: string | null;
    user?: {
        id: number;
        name: string;
        email: string;
    };
}

interface NotificationItem {
    id: number;
    notification_setting_id: number | null;
    title: string;
    message: string;
    recipient_type: 'ALL_USERS' | 'CUSTOMER' | 'ADMIN' | 'SPECIFIC_USER';
    created_by: number | null;
    created_at: string;
    recipients_count?: number;
    read_recipients_count?: number;
    unread_recipients_count?: number;
    setting?: NotificationSetting | null;
    creator?: Creator | null;
}

interface PaginationData {
    data: NotificationItem[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    notifications: PaginationData;
    filters: {
        search?: string;
        recipient_type?: string;
        read_status?: string;
        sort?: string;
        direction?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notifikasi', href: '#' },
    { title: 'Daftar Notifikasi', href: '/dashboard/notifications' },
];

const search = ref(props.filters.search || '');
const recipientType = ref(props.filters.recipient_type || 'all');
const readStatus = ref(props.filters.read_status || 'all');
const perPage = ref(props.filters.per_page?.toString() || '10');
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout>;

const buildQuery = () => {
    const query: Record<string, string> = {};
    if (search.value) query.search = search.value;
    if (recipientType.value !== 'all') query.recipient_type = recipientType.value;
    if (readStatus.value !== 'all') query.read_status = readStatus.value;
    if (perPage.value !== '10') query.per_page = perPage.value;
    if (props.filters.sort) query.sort = props.filters.sort;
    if (props.filters.direction) query.direction = props.filters.direction;

    return query;
};

const fetchNotifications = () => {
    isLoading.value = true;
    router.get('/dashboard/notifications', buildQuery(), {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => (isLoading.value = false),
    });
};

watch(search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchNotifications, 400);
    }
});

watch([recipientType, readStatus, perPage], () => {
    fetchNotifications();
});

// Delete Confirmation
const isDeleteDialogOpen = ref(false);
const notificationToDelete = ref<NotificationItem | null>(null);

const confirmDelete = (item: NotificationItem) => {
    notificationToDelete.value = item;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (notificationToDelete.value) {
        router.delete(`/dashboard/notifications/${notificationToDelete.value.id}`, {
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                notificationToDelete.value = null;
            },
        });
    }
};

// Mark All As Read
const markAllAsRead = (notification: NotificationItem) => {
    router.post(`/dashboard/notifications/${notification.id}/mark-all-read`, {}, {
        preserveScroll: true,
    });
};

// Detail Quick View Modal
const isDetailModalOpen = ref(false);
const activeDetail = ref<NotificationItem | null>(null);

const openDetailModal = (item: NotificationItem) => {
    activeDetail.value = item;
    isDetailModalOpen.value = true;
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const getRecipientBadge = (type: string) => {
    switch (type) {
        case 'ALL_USERS':
            return { label: 'Semua Pengguna', variant: 'default', icon: Users, class: 'bg-blue-600/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900' };
        case 'CUSTOMER':
            return { label: 'Pelanggan', variant: 'default', icon: UserCheck, class: 'bg-emerald-600/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900' };
        case 'ADMIN':
            return { label: 'Admin', variant: 'default', icon: Shield, class: 'bg-purple-600/10 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-900' };
        case 'SPECIFIC_USER':
            return { label: 'Pengguna Tertentu', variant: 'default', icon: User, class: 'bg-amber-600/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900' };
        default:
            return { label: type, variant: 'outline', icon: Bell, class: '' };
    }
};

const calculateReadPercentage = (read: number = 0, total: number = 0) => {
    if (total === 0) return 0;
    return Math.round((read / total) * 100);
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Daftar Notifikasi" />

        <!-- Breadcrumbs -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <BellRing class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Daftar Notifikasi</h1>
                        <p class="text-sm text-muted-foreground">
                            Kelola pesan notifikasi, target penerima, dan pantau status pembacaan user secara real-time.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button as-child variant="outline" class="gap-2">
                    <Link href="/dashboard/notification-settings">
                        <Settings class="h-4 w-4" />
                        Template & Pengaturan
                    </Link>
                </Button>
                <Button as-child class="gap-2 shadow-xs">
                    <Link href="/dashboard/notifications/create">
                        <Plus class="h-4 w-4" />
                        Kirim Notifikasi
                    </Link>
                </Button>
            </div>
        </div>

        <!-- DataTable Container -->
        <div class="rounded-xl border bg-card shadow-xs">
            <!-- Table Controls -->
            <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-1 items-center gap-2 sm:max-w-md">
                    <div class="relative w-full">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Cari judul, pesan, atau pembuat..."
                            class="w-full bg-background pl-8"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2">
                        <Filter class="h-4 w-4 text-muted-foreground" />
                        <Select v-model="recipientType">
                            <SelectTrigger class="h-9 w-[160px] bg-background">
                                <SelectValue placeholder="Target Penerima" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Target</SelectItem>
                                <SelectItem value="ALL_USERS">Semua Pengguna</SelectItem>
                                <SelectItem value="CUSTOMER">Pelanggan</SelectItem>
                                <SelectItem value="ADMIN">Admin</SelectItem>
                                <SelectItem value="SPECIFIC_USER">Pengguna Tertentu</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-center gap-2 border-r pr-2 mr-1">
                        <Select v-model="readStatus">
                            <SelectTrigger class="h-9 w-[150px] bg-background">
                                <SelectValue placeholder="Status Baca" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="all_read">Semua Dibaca</SelectItem>
                                <SelectItem value="has_unread">Ada Belum Dibaca</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground hidden sm:inline-block">Tampilkan</span>
                        <Select v-model="perPage">
                            <SelectTrigger class="h-9 w-[75px] bg-background">
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
                <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-background/50 backdrop-blur-xs">
                    <Loader2 class="h-8 w-8 animate-spin text-primary" />
                </div>
                <table class="w-full caption-bottom text-sm">
                    <thead class="bg-muted/50 [&_tr]:border-b">
                        <tr class="border-b transition-colors">
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Notifikasi</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Target Penerima</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Pengirim</th>
                            <th class="h-10 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status Dibaca</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Waktu Dibuat</th>
                            <th class="h-10 px-4 text-right align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr
                            v-for="item in notifications.data"
                            :key="item.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <!-- Notifikasi Title & Message -->
                            <td class="p-4 align-middle">
                                <div class="flex flex-col gap-1 max-w-md">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-foreground text-sm line-clamp-1">{{ item.title }}</span>
                                        <Badge
                                            v-if="item.setting"
                                            variant="outline"
                                            class="text-[10px] h-4 px-1.5 py-0 border-primary/20 bg-primary/5 text-primary shrink-0"
                                        >
                                            {{ item.setting.name }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                                        {{ item.message }}
                                    </p>
                                </div>
                            </td>

                            <!-- Target Penerima -->
                            <td class="p-4 align-middle whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <Badge
                                        variant="outline"
                                        :class="['text-xs font-medium px-2 py-0.5 border flex items-center gap-1.5', getRecipientBadge(item.recipient_type).class]"
                                    >
                                        <component :is="getRecipientBadge(item.recipient_type).icon" class="h-3 w-3" />
                                        {{ getRecipientBadge(item.recipient_type).label }}
                                    </Badge>
                                </div>
                            </td>

                            <!-- Pengirim -->
                            <td class="p-4 align-middle whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium text-foreground">{{ item.creator?.name || 'Sistem' }}</span>
                                    <span class="text-[11px] text-muted-foreground">{{ item.creator?.email || 'admin@system.local' }}</span>
                                </div>
                            </td>

                            <!-- Status Dibaca -->
                            <td class="p-4 align-middle text-center">
                                <div class="inline-flex flex-col items-center gap-1.5 min-w-[130px]">
                                    <div class="flex items-center justify-between w-full text-[11px] font-medium">
                                        <span class="text-muted-foreground">
                                            {{ item.read_recipients_count ?? 0 }} / {{ item.recipients_count ?? 0 }}
                                        </span>
                                        <span :class="(item.read_recipients_count === item.recipients_count && item.recipients_count! > 0) ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-muted-foreground'">
                                            {{ calculateReadPercentage(item.read_recipients_count, item.recipients_count) }}%
                                        </span>
                                    </div>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                        <div
                                            class="h-full rounded-full transition-all duration-300"
                                            :class="(item.read_recipients_count === item.recipients_count && item.recipients_count! > 0) ? 'bg-emerald-500' : 'bg-primary'"
                                            :style="{ width: `${calculateReadPercentage(item.read_recipients_count, item.recipients_count)}%` }"
                                        />
                                    </div>
                                    <Badge
                                        v-if="item.recipients_count === 0"
                                        variant="outline"
                                        class="text-[10px] h-4 text-muted-foreground"
                                    >
                                        Tanpa Penerima
                                    </Badge>
                                    <Badge
                                        v-else-if="item.read_recipients_count === item.recipients_count"
                                        variant="outline"
                                        class="text-[10px] h-4 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900"
                                    >
                                        Semua Dibaca
                                    </Badge>
                                    <Badge
                                        v-else-if="(item.read_recipients_count ?? 0) > 0"
                                        variant="outline"
                                        class="text-[10px] h-4 bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200 dark:border-amber-900"
                                    >
                                        Sebagian Dibaca
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="outline"
                                        class="text-[10px] h-4 bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200 dark:border-rose-900"
                                    >
                                        Belum Dibaca
                                    </Badge>
                                </div>
                            </td>

                            <!-- Tanggal Dibuat -->
                            <td class="p-4 align-middle whitespace-nowrap text-xs text-muted-foreground">
                                <div class="flex items-center gap-1.5">
                                    <Clock class="h-3.5 w-3.5 text-muted-foreground/70" />
                                    <span>{{ formatDate(item.created_at) }}</span>
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td class="p-4 align-middle text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" class="h-8 w-8 p-0">
                                            <span class="sr-only">Buka menu</span>
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-48">
                                        <Link :href="`/dashboard/notifications/${item.id}`">
                                            <DropdownMenuItem class="cursor-pointer">
                                                <Eye class="mr-2 h-4 w-4" />
                                                Lihat Penerima
                                            </DropdownMenuItem>
                                        </Link>
                                        <DropdownMenuItem
                                            v-if="(item.unread_recipients_count ?? 0) > 0"
                                            class="cursor-pointer"
                                            @click="markAllAsRead(item)"
                                        >
                                            <CheckCheck class="mr-2 h-4 w-4 text-emerald-600" />
                                            Tandai Semua Dibaca
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            class="cursor-pointer text-destructive focus:text-destructive"
                                            @click="confirmDelete(item)"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Hapus Notifikasi
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>

                        <tr v-if="notifications.data.length === 0">
                            <td colspan="6" class="h-40 text-center p-4 align-middle">
                                <div class="flex flex-col items-center justify-center text-muted-foreground">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted mb-3">
                                        <Bell class="h-6 w-6 opacity-40" />
                                    </div>
                                    <p class="font-medium text-foreground">Tidak ada notifikasi ditemukan</p>
                                    <p class="text-xs text-muted-foreground mt-1">Coba sesuaikan kata kunci pencarian atau buat notifikasi baru.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- DataTable Footer / Pagination -->
            <div class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ notifications.from || 0 }} sampai {{ notifications.to || 0 }} dari
                    {{ notifications.total }} data
                </div>

                <!-- Numbered Pagination -->
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in notifications.links" :key="i">
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
                            v-else-if="i === notifications.links.length - 1"
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
                    <AlertDialogTitle>Hapus Notifikasi?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Anda yakin ingin menghapus notifikasi <strong>{{ notificationToDelete?.title }}</strong>?
                        Tindakan ini akan menghapus riwayat notifikasi beserta status pembacaan seluruh penerima terkait.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="executeDelete"
                    >
                        Hapus Notifikasi
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
