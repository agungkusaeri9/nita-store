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
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    CheckCheck,
    ChevronLeft,
    Clock,
    Eye,
    Filter,
    Mail,
    MessageSquare,
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
    recipient_type: string;
    push_enabled: boolean;
    email_enabled: boolean;
    whatsapp_enabled: boolean;
}

interface Creator {
    id: number;
    name: string;
    email: string;
}

interface RecipientItem {
    id: number;
    user_id: number;
    is_read: boolean;
    read_at: string | null;
    user?: {
        id: number;
        name: string;
        email: string;
        username?: string;
    };
}

interface NotificationDetail {
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
    recipients: RecipientItem[];
}

interface Props {
    notification: NotificationDetail;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notifikasi', href: '/dashboard/notifications' },
    { title: 'Detail Notifikasi', href: '#' },
];

const recipientSearch = ref('');
const recipientStatus = ref('all');

const filteredRecipients = computed(() => {
    let list = props.notification.recipients || [];

    if (recipientStatus.value === 'read') {
        list = list.filter((r) => r.is_read);
    } else if (recipientStatus.value === 'unread') {
        list = list.filter((r) => !r.is_read);
    }

    if (recipientSearch.value) {
        const query = recipientSearch.value.toLowerCase();
        list = list.filter(
            (r) =>
                r.user?.name.toLowerCase().includes(query) ||
                r.user?.email.toLowerCase().includes(query)
        );
    }

    return list;
});

const isDeleteDialogOpen = ref(false);

const executeDelete = () => {
    router.delete(`/dashboard/notifications/${props.notification.id}`, {
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
        },
    });
};

const markAllAsRead = () => {
    router.post(`/dashboard/notifications/${props.notification.id}/mark-all-read`, {}, {
        preserveScroll: true,
    });
};

const formatDate = (dateString: string | null) => {
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

const readPercentage = computed(() => {
    const total = props.notification.recipients_count ?? props.notification.recipients.length;
    const read = props.notification.read_recipients_count ?? props.notification.recipients.filter(r => r.is_read).length;
    if (total === 0) return 0;
    return Math.round((read / total) * 100);
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="`Detail Notifikasi - ${notification.title}`" />

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
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{{ notification.title }}</h1>
                        <p class="text-sm text-muted-foreground">
                            Dikirim oleh <strong class="text-foreground">{{ notification.creator?.name || 'Sistem' }}</strong> pada {{ formatDate(notification.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button
                    v-if="(notification.unread_recipients_count ?? 0) > 0"
                    variant="outline"
                    class="gap-2 text-emerald-600 hover:text-emerald-700 border-emerald-200 dark:border-emerald-900"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="h-4 w-4" />
                    Tandai Semua Dibaca
                </Button>
                <Button
                    variant="destructive"
                    class="gap-2"
                    @click="isDeleteDialogOpen = true"
                >
                    <Trash2 class="h-4 w-4" />
                    Hapus
                </Button>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Card 1: Target -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Target Penerima</CardTitle>
                    <component :is="getRecipientBadge(notification.recipient_type).icon" class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-lg font-bold">
                        <Badge
                            variant="outline"
                            :class="['text-xs font-semibold px-2 py-0.5 border', getRecipientBadge(notification.recipient_type).class]"
                        >
                            {{ getRecipientBadge(notification.recipient_type).label }}
                        </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ notification.recipients_count ?? notification.recipients.length }} penerima terdaftar
                    </p>
                </CardContent>
            </Card>

            <!-- Card 2: Status Baca -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Progress Pembacaan</CardTitle>
                    <CheckCheck class="h-4 w-4 text-emerald-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ readPercentage }}%</div>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ notification.read_recipients_count ?? 0 }} dari {{ notification.recipients_count ?? notification.recipients.length }} telah membaca
                    </p>
                </CardContent>
            </Card>

            <!-- Card 3: Belum Dibaca -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Belum Membaca</CardTitle>
                    <Clock class="h-4 w-4 text-amber-600" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-amber-600">
                        {{ notification.unread_recipients_count ?? (notification.recipients.length - (notification.read_recipients_count ?? 0)) }}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">User belum membuka pesan</p>
                </CardContent>
            </Card>

            <!-- Card 4: Template -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-xs font-medium text-muted-foreground">Template Setting</CardTitle>
                    <Bell class="h-4 w-4 text-primary" />
                </CardHeader>
                <CardContent>
                    <div class="text-sm font-semibold truncate">
                        {{ notification.setting?.name || 'Kustom' }}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1 font-mono">
                        {{ notification.setting?.code || '-' }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Left: Message Content (1 col) -->
            <div class="space-y-6 lg:col-span-1">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Isi Pesan Notifikasi</CardTitle>
                        <CardDescription>Pesan aktual yang diterima oleh pengguna.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="rounded-lg bg-muted/40 p-4 border text-sm leading-relaxed whitespace-pre-line text-foreground">
                            {{ notification.message }}
                        </div>

                        <div class="space-y-2 pt-2 border-t text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Saluran Web / Push:</span>
                                <Badge variant="default" class="bg-emerald-600 text-[10px]">Aktif</Badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Saluran Email:</span>
                                <Badge :variant="notification.setting?.email_enabled ? 'default' : 'secondary'" class="text-[10px]">
                                    {{ notification.setting?.email_enabled ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Saluran WhatsApp:</span>
                                <Badge :variant="notification.setting?.whatsapp_enabled ? 'default' : 'secondary'" class="text-[10px]">
                                    {{ notification.setting?.whatsapp_enabled ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right: Recipients Table (2 cols) -->
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border bg-card shadow-xs">
                    <!-- Controls -->
                    <div class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-1 items-center gap-2 sm:max-w-xs">
                            <div class="relative w-full">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="recipientSearch"
                                    placeholder="Cari nama penerima..."
                                    class="w-full bg-background pl-8 text-sm h-9"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Filter class="h-4 w-4 text-muted-foreground" />
                            <Select v-model="recipientStatus">
                                <SelectTrigger class="h-9 w-[150px] bg-background">
                                    <SelectValue placeholder="Status Baca" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="read">Sudah Dibaca</SelectItem>
                                    <SelectItem value="unread">Belum Dibaca</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="relative overflow-x-auto">
                        <table class="w-full caption-bottom text-sm">
                            <thead class="bg-muted/50 [&_tr]:border-b">
                                <tr class="border-b transition-colors">
                                    <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Pengguna / Penerima</th>
                                    <th class="h-10 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status Dibaca</th>
                                    <th class="h-10 px-4 text-right align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Waktu Dibaca</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr
                                    v-for="recipient in filteredRecipients"
                                    :key="recipient.id"
                                    class="border-b transition-colors hover:bg-muted/50"
                                >
                                    <td class="p-4 align-middle">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary text-xs font-bold uppercase">
                                                {{ recipient.user?.name?.charAt(0) || 'U' }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-medium text-xs text-foreground">{{ recipient.user?.name || 'User Terhapus' }}</span>
                                                <span class="text-[11px] text-muted-foreground">{{ recipient.user?.email || '-' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-4 align-middle text-center">
                                        <Badge
                                            v-if="recipient.is_read"
                                            variant="default"
                                            class="bg-emerald-600/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900 text-xs gap-1"
                                        >
                                            <CheckCheck class="h-3 w-3" />
                                            Sudah Dibaca
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="outline"
                                            class="text-xs text-muted-foreground border-dashed gap-1"
                                        >
                                            <Clock class="h-3 w-3" />
                                            Belum Dibaca
                                        </Badge>
                                    </td>

                                    <td class="p-4 align-middle text-right text-xs text-muted-foreground whitespace-nowrap">
                                        {{ recipient.is_read ? formatDate(recipient.read_at) : '-' }}
                                    </td>
                                </tr>

                                <tr v-if="filteredRecipients.length === 0">
                                    <td colspan="3" class="h-28 text-center p-4 align-middle text-muted-foreground text-xs">
                                        Tidak ada data penerima yang sesuai dengan filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Notifikasi?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Anda yakin ingin menghapus notifikasi <strong>{{ notification.title }}</strong>?
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
