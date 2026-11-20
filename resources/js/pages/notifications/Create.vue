<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Bell,
    Check,
    CheckCheck,
    ChevronLeft,
    Info,
    Loader2,
    Mail,
    MessageSquare,
    Send,
    Shield,
    Smartphone,
    User,
    UserCheck,
    Users,
    Search,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface NotificationSetting {
    id: number;
    code: string;
    name: string;
    description: string | null;
    recipient_type: 'ALL_USERS' | 'CUSTOMER' | 'ADMIN' | 'SPECIFIC_USER';
    push_enabled: boolean;
    email_enabled: boolean;
    whatsapp_enabled: boolean;
    is_active: boolean;
}

interface UserItem {
    id: number;
    name: string;
    email: string;
    username: string;
}

interface Props {
    settings: NotificationSetting[];
    users: UserItem[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notifikasi', href: '/dashboard/notifications' },
    { title: 'Kirim Notifikasi', href: '/dashboard/notifications/create' },
];

const form = useForm({
    notification_setting_id: 'none',
    title: '',
    message: '',
    recipient_type: 'ALL_USERS' as 'ALL_USERS' | 'CUSTOMER' | 'ADMIN' | 'SPECIFIC_USER',
    user_ids: [] as number[],
});

// Auto-fill template if selected
const selectedSetting = computed(() => {
    if (!form.notification_setting_id || form.notification_setting_id === 'none') return null;
    return props.settings.find((s) => s.id === Number(form.notification_setting_id)) || null;
});

watch(() => form.notification_setting_id, (newVal) => {
    if (newVal && newVal !== 'none') {
        const setting = props.settings.find((s) => s.id === Number(newVal));
        if (setting) {
            form.title = setting.name;
            if (setting.description && !form.message) {
                form.message = setting.description;
            }
            if (setting.recipient_type) {
                form.recipient_type = setting.recipient_type;
            }
        }
    }
});

// Search & Filter for specific users
const userSearch = ref('');
const filteredUsers = computed(() => {
    if (!userSearch.value) return props.users;
    const query = userSearch.value.toLowerCase();
    return props.users.filter(
        (u) =>
            u.name.toLowerCase().includes(query) ||
            u.email.toLowerCase().includes(query) ||
            (u.username && u.username.toLowerCase().includes(query))
    );
});

const isAllFilteredSelected = computed(() => {
    if (filteredUsers.value.length === 0) return false;
    return filteredUsers.value.every((u) => form.user_ids.includes(u.id));
});

const toggleSelectAllFiltered = () => {
    if (isAllFilteredSelected.value) {
        // Deselect only filtered users
        const filteredIds = new Set(filteredUsers.value.map((u) => u.id));
        form.user_ids = form.user_ids.filter((id) => !filteredIds.has(id));
    } else {
        // Select all filtered users
        const currentSet = new Set(form.user_ids);
        filteredUsers.value.forEach((u) => currentSet.add(u.id));
        form.user_ids = Array.from(currentSet);
    }
};

const toggleUser = (userId: number) => {
    const index = form.user_ids.indexOf(userId);
    if (index > -1) {
        form.user_ids.splice(index, 1);
    } else {
        form.user_ids.push(userId);
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        notification_setting_id: (!data.notification_setting_id || data.notification_setting_id === 'none') ? null : Number(data.notification_setting_id),
    })).post('/dashboard/notifications', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Kirim Notifikasi Baru" />

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
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Kirim Notifikasi Baru</h1>
                        <p class="text-sm text-muted-foreground">
                            Kirimkan notifikasi dan pengumuman kepada seluruh pengguna, pelanggan, admin, atau pengguna tertentu.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
            <!-- Left Column: Main Form (2 cols) -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Card: Isi Notifikasi -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Informasi Notifikasi</CardTitle>
                        <CardDescription>
                            Tentukan judul pengumuman, template referensi, dan isi pesan yang ingin disampaikan.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Template Selection -->
                        <div class="space-y-2">
                            <Label for="setting_id">Template / Jenis Notifikasi (Opsional)</Label>
                            <Select v-model="form.notification_setting_id">
                                <SelectTrigger id="setting_id" class="w-full bg-background">
                                    <SelectValue placeholder="Pilih template setting (opsional)..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">-- Tanpa Template (Kustom) --</SelectItem>
                                    <SelectItem
                                        v-for="s in settings"
                                        :key="s.id"
                                        :value="s.id.toString()"
                                    >
                                        {{ s.name }} ({{ s.code }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="selectedSetting?.description" class="text-xs text-muted-foreground">
                                {{ selectedSetting.description }}
                            </p>
                        </div>

                        <!-- Judul -->
                        <div class="space-y-2">
                            <Label for="title">
                                Judul Notifikasi <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Contoh: Promo Spesial Batik Tulis Madura 20%"
                                class="bg-background"
                            />
                            <p v-if="form.errors.title" class="text-xs text-destructive">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <!-- Pesan -->
                        <div class="space-y-2">
                            <Label for="message">
                                Isi Pesan Notifikasi <span class="text-destructive">*</span>
                            </Label>
                            <Textarea
                                id="message"
                                v-model="form.message"
                                rows="5"
                                placeholder="Tuliskan isi detail notifikasi atau pengumuman di sini..."
                                class="bg-background resize-y"
                            />
                            <p v-if="form.errors.message" class="text-xs text-destructive">
                                {{ form.errors.message }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card: Target Penerima -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Target Penerima</CardTitle>
                        <CardDescription>
                            Pilih kelompok pengguna atau tentukan user spesifik yang berhak menerima notifikasi ini.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <!-- Recipient Type Selection Grid -->
                        <div class="grid gap-3 sm:grid-cols-2">
                            <!-- ALL_USERS -->
                            <div
                                @click="form.recipient_type = 'ALL_USERS'"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-all hover:bg-muted/40"
                                :class="form.recipient_type === 'ALL_USERS' ? 'border-primary bg-primary/5 shadow-xs' : 'border-border'"
                            >
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600">
                                    <Users class="h-5 w-5" />
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-foreground">Semua Pengguna</span>
                                        <Check v-if="form.recipient_type === 'ALL_USERS'" class="h-4 w-4 text-primary" />
                                    </div>
                                    <p class="text-xs text-muted-foreground">Kirim ke seluruh user terdaftar (Customer & Admin).</p>
                                </div>
                            </div>

                            <!-- CUSTOMER -->
                            <div
                                @click="form.recipient_type = 'CUSTOMER'"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-all hover:bg-muted/40"
                                :class="form.recipient_type === 'CUSTOMER' ? 'border-primary bg-primary/5 shadow-xs' : 'border-border'"
                            >
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                    <UserCheck class="h-5 w-5" />
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-foreground">Pelanggan Saja</span>
                                        <Check v-if="form.recipient_type === 'CUSTOMER'" class="h-4 w-4 text-primary" />
                                    </div>
                                    <p class="text-xs text-muted-foreground">Kirim hanya ke akun pelanggan/customer e-commerce.</p>
                                </div>
                            </div>

                            <!-- ADMIN -->
                            <div
                                @click="form.recipient_type = 'ADMIN'"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-all hover:bg-muted/40"
                                :class="form.recipient_type === 'ADMIN' ? 'border-primary bg-primary/5 shadow-xs' : 'border-border'"
                            >
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-500/10 text-purple-600">
                                    <Shield class="h-5 w-5" />
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-foreground">Admin & Internal</span>
                                        <Check v-if="form.recipient_type === 'ADMIN'" class="h-4 w-4 text-primary" />
                                    </div>
                                    <p class="text-xs text-muted-foreground">Kirim hanya ke tim administrator dan staf pengelola.</p>
                                </div>
                            </div>

                            <!-- SPECIFIC_USER -->
                            <div
                                @click="form.recipient_type = 'SPECIFIC_USER'"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-all hover:bg-muted/40"
                                :class="form.recipient_type === 'SPECIFIC_USER' ? 'border-primary bg-primary/5 shadow-xs' : 'border-border'"
                            >
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                                    <User class="h-5 w-5" />
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-foreground">Pengguna Tertentu</span>
                                        <Check v-if="form.recipient_type === 'SPECIFIC_USER'" class="h-4 w-4 text-primary" />
                                    </div>
                                    <p class="text-xs text-muted-foreground">Pilih satu atau beberapa akun pengguna secara spesifik.</p>
                                </div>
                            </div>
                        </div>

                        <p v-if="form.errors.recipient_type" class="text-xs text-destructive">
                            {{ form.errors.recipient_type }}
                        </p>

                        <!-- Specific User Picker List -->
                        <div v-if="form.recipient_type === 'SPECIFIC_USER'" class="space-y-3 pt-2 border-t">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-2">
                                    <Label class="text-sm font-medium">Daftar Pengguna</Label>
                                    <Badge variant="secondary" class="font-mono text-xs">
                                        {{ form.user_ids.length }} Dipilih
                                    </Badge>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="h-8 text-xs"
                                        @click="toggleSelectAllFiltered"
                                    >
                                        {{ isAllFilteredSelected ? 'Batalkan Semua' : 'Pilih Semua' }}
                                    </Button>
                                </div>
                            </div>

                            <!-- User Search Filter -->
                            <div class="relative">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="userSearch"
                                    placeholder="Cari nama atau email pengguna..."
                                    class="bg-background pl-8 text-sm h-9"
                                />
                            </div>

                            <!-- User List Container -->
                            <div class="max-h-60 overflow-y-auto rounded-lg border bg-background/50 divide-y">
                                <div
                                    v-for="u in filteredUsers"
                                    :key="u.id"
                                    @click="toggleUser(u.id)"
                                    class="flex items-center justify-between p-2.5 text-sm hover:bg-muted/50 cursor-pointer transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-primary text-xs font-bold uppercase">
                                            {{ u.name.charAt(0) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-xs text-foreground">{{ u.name }}</span>
                                            <span class="text-[11px] text-muted-foreground">{{ u.email }}</span>
                                        </div>
                                    </div>
                                    <Checkbox :checked="form.user_ids.includes(u.id)" />
                                </div>

                                <div v-if="filteredUsers.length === 0" class="p-4 text-center text-xs text-muted-foreground">
                                    Tidak ada pengguna yang cocok dengan pencarian.
                                </div>
                            </div>

                            <p v-if="form.errors.user_ids" class="text-xs text-destructive">
                                {{ form.errors.user_ids }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Channels Preview & Action (1 col) -->
            <div class="space-y-6">
                <!-- Card: Channel Delivery Status -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Saluran Pengiriman</CardTitle>
                        <CardDescription>
                            Saluran aktif berdasarkan konfigurasi template notifikasi.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center justify-between rounded-lg border p-3">
                            <div class="flex items-center gap-2.5">
                                <Smartphone class="h-4 w-4 text-primary" />
                                <div class="flex flex-col">
                                    <span class="text-xs font-semibold">Web & In-App / Push</span>
                                    <span class="text-[11px] text-muted-foreground">Notifikasi lonceng & tabel inbox</span>
                                </div>
                            </div>
                            <Badge variant="default" class="bg-emerald-600 text-white text-[10px] h-5">
                                Aktif
                            </Badge>
                        </div>

                        <div class="flex items-center justify-between rounded-lg border p-3">
                            <div class="flex items-center gap-2.5">
                                <Mail class="h-4 w-4 text-primary" />
                                <div class="flex flex-col">
                                    <span class="text-xs font-semibold">Email Broadcast</span>
                                    <span class="text-[11px] text-muted-foreground">Kirim email ke inbox user</span>
                                </div>
                            </div>
                            <Badge
                                :variant="selectedSetting?.email_enabled ? 'default' : 'secondary'"
                                class="text-[10px] h-5"
                                :class="selectedSetting?.email_enabled ? 'bg-emerald-600 text-white' : ''"
                            >
                                {{ selectedSetting?.email_enabled ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                        </div>

                        <div class="flex items-center justify-between rounded-lg border p-3">
                            <div class="flex items-center gap-2.5">
                                <MessageSquare class="h-4 w-4 text-primary" />
                                <div class="flex flex-col">
                                    <span class="text-xs font-semibold">WhatsApp Gateway</span>
                                    <span class="text-[11px] text-muted-foreground">Pesan instan ke nomor HP</span>
                                </div>
                            </div>
                            <Badge
                                :variant="selectedSetting?.whatsapp_enabled ? 'default' : 'secondary'"
                                class="text-[10px] h-5"
                                :class="selectedSetting?.whatsapp_enabled ? 'bg-emerald-600 text-white' : ''"
                            >
                                {{ selectedSetting?.whatsapp_enabled ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card: Submission Actions -->
                <Card>
                    <CardContent class="p-4 space-y-3">
                        <Button
                            type="submit"
                            class="w-full gap-2 shadow-xs"
                            :disabled="form.processing"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Send v-else class="h-4 w-4" />
                            Kirimkan Notifikasi Sekarang
                        </Button>

                        <Button as-child variant="outline" class="w-full">
                            <Link href="/dashboard/notifications">
                                Batal
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </form>
    </div>
</template>
