<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Shield,
    CheckSquare,
    Square,
    Search,
    Users,
    UserCheck,
    Lock,
    Package,
    FolderTree,
    Layers,
    ShoppingCart,
    Image,
    CreditCard,
    Truck,
    MapPin,
    Bell,
    Star,
    Store,
    FileText,
    HelpCircle,
    History,
    LayoutDashboard,
} from 'lucide-vue-next';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import { computed, ref } from 'vue';

interface Permission {
    id: number;
    name: string;
}

const props = defineProps<{
    permissions: Permission[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Peran & Hak Akses', href: '#' },
    { title: 'Manajemen Peran', href: '/dashboard/roles' },
    { title: 'Tambah Peran', href: '/dashboard/roles/create' },
];

const form = useForm({
    name: '',
    permissions: [] as string[],
});

const searchQuery = ref('');

interface ModuleMeta {
    title: string;
    description: string;
    icon: any;
}

// Module categorization mapping
const getModuleMeta = (permName: string): ModuleMeta => {
    if (permName.includes('dashboard')) {
        return { title: 'Dashboard', description: 'Akses statistik & ringkasan', icon: LayoutDashboard };
    }
    if (permName.includes('user')) {
        return { title: 'Pengguna (Admin)', description: 'Kelola akun staff & administrator', icon: Users };
    }
    if (permName.includes('customer')) {
        return { title: 'Pelanggan (Customer)', description: 'Kelola data pelanggan terdaftar', icon: UserCheck };
    }
    if (permName.includes('role') || permName.includes('permission')) {
        return { title: 'Peran & Hak Akses', description: 'Kelola role & permissions sistem', icon: Lock };
    }
    if (permName.includes('product review') || permName.includes('review')) {
        return { title: 'Ulasan Produk', description: 'Moderasi ulasan & rating pembeli', icon: Star };
    }
    if (permName.includes('categor')) {
        return { title: 'Kategori Produk', description: 'Struktur kategori produk', icon: FolderTree };
    }
    if (permName.includes('variant')) {
        return { title: 'Varian Produk', description: 'Ukuran, warna, & opsi varian', icon: Layers };
    }
    if (permName.includes('product')) {
        return { title: 'Katalog Produk', description: 'Kelola produk, stok & harga', icon: Package };
    }
    if (permName.includes('order')) {
        return { title: 'Pesanan & Transaksi', description: 'Proses checkout, bayar & pengiriman', icon: ShoppingCart };
    }
    if (permName.includes('banner')) {
        return { title: 'Banner Promosi', description: 'Kelola banner slider homepage', icon: Image };
    }
    if (permName.includes('payment')) {
        return { title: 'Metode Pembayaran', description: 'Bank transfer & payment gateway', icon: CreditCard };
    }
    if (permName.includes('courier')) {
        return { title: 'Kurir Pengiriman', description: 'Ekspedisi & tarif ongkir', icon: Truck };
    }
    if (permName.includes('location')) {
        return { title: 'Lokasi & Wilayah', description: 'Provinsi, kota, & kecamatan', icon: MapPin };
    }
    if (permName.includes('notification')) {
        return { title: 'Notifikasi', description: 'Kirim notifikasi & template pesan', icon: Bell };
    }
    if (permName.includes('store setting')) {
        return { title: 'Pengaturan Toko', description: 'Profil toko, logo & kontak', icon: Store };
    }
    if (permName.includes('privacy') || permName.includes('policy')) {
        return { title: 'Kebijakan & Privasi', description: 'Syarat ketentuan & kebijakan', icon: FileText };
    }
    if (permName.includes('faq')) {
        return { title: 'FAQ (Tanya Jawab)', description: 'Daftar pertanyaan umum', icon: HelpCircle };
    }
    if (permName.includes('activity')) {
        return { title: 'Log Aktivitas', description: 'Audit trail aksi pengguna', icon: History };
    }
    return { title: 'Lainnya', description: 'Fitur tambahan sistem', icon: Shield };
};

// Formats individual action inside each module
const formatAction = (permName: string) => {
    if (permName.startsWith('view ')) {
        return {
            label: 'Lihat (View)',
            badgeColor: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900',
        };
    }
    if (permName.startsWith('create ')) {
        return {
            label: 'Tambah (Create)',
            badgeColor: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900',
        };
    }
    if (permName.startsWith('edit ')) {
        return {
            label: 'Ubah (Edit)',
            badgeColor: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900',
        };
    }
    if (permName.startsWith('delete ')) {
        return {
            label: 'Hapus (Delete)',
            badgeColor: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900',
        };
    }
    if (permName.startsWith('import ')) {
        return {
            label: 'Import Data',
            badgeColor: 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-900',
        };
    }
    if (permName.startsWith('toggle ')) {
        return {
            label: 'Aktif/Nonaktif (Toggle)',
            badgeColor: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border-cyan-200 dark:border-cyan-900',
        };
    }
    if (permName === 'update order status') {
        return {
            label: 'Ubah Status Pesanan',
            badgeColor: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-900',
        };
    }
    if (permName === 'update order payment') {
        return {
            label: 'Update Pembayaran',
            badgeColor: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900',
        };
    }
    if (permName === 'update order shipping') {
        return {
            label: 'Input Resi & Kurir',
            badgeColor: 'bg-sky-500/10 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-900',
        };
    }
    if (permName === 'manage notification settings') {
        return {
            label: 'Pengaturan Notifikasi',
            badgeColor: 'bg-violet-500/10 text-violet-700 dark:text-violet-400 border-violet-200 dark:border-violet-900',
        };
    }

    return {
        label: permName,
        badgeColor: 'bg-muted text-muted-foreground border-border',
    };
};

interface GroupedModule {
    meta: ModuleMeta;
    permissions: Permission[];
}

// Grouped permissions computed
const groupedModules = computed(() => {
    const map = new Map<string, GroupedModule>();

    props.permissions.forEach((perm) => {
        if (
            searchQuery.value &&
            !perm.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        ) {
            return;
        }

        const meta = getModuleMeta(perm.name);
        if (!map.has(meta.title)) {
            map.set(meta.title, {
                meta,
                permissions: [],
            });
        }
        map.get(meta.title)!.permissions.push(perm);
    });

    return Array.from(map.values());
});

const togglePermission = (name: string) => {
    const index = form.permissions.indexOf(name);
    if (index === -1) {
        form.permissions.push(name);
    } else {
        form.permissions.splice(index, 1);
    }
};

const selectAll = () => {
    form.permissions = props.permissions.map((p) => p.name);
};

const clearAll = () => {
    form.permissions = [];
};

const toggleModule = (modulePermissions: Permission[]) => {
    const names = modulePermissions.map((p) => p.name);
    const allSelected = names.every((n) => form.permissions.includes(n));

    if (allSelected) {
        form.permissions = form.permissions.filter((p) => !names.includes(p));
    } else {
        names.forEach((n) => {
            if (!form.permissions.includes(n)) {
                form.permissions.push(n);
            }
        });
    }
};

const isModuleFullySelected = (modulePermissions: Permission[]) => {
    return modulePermissions.length > 0 && modulePermissions.every((p) => form.permissions.includes(p.name));
};

const isModulePartiallySelected = (modulePermissions: Permission[]) => {
    const selectedCount = modulePermissions.filter((p) => form.permissions.includes(p.name)).length;
    return selectedCount > 0 && selectedCount < modulePermissions.length;
};

const submit = () => {
    form.post('/dashboard/roles');
};
</script>

<template>
    <Head title="Tambah Peran" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Breadcrumbs -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                Tambah Peran Baru
            </h1>
            <p class="text-sm text-muted-foreground">
                Tentukan nama peran dan pilih hak akses (permissions) yang dikelompokkan per modul.
            </p>
        </div>

        <form @submit.prevent="submit" class="grid gap-6">
            <!-- Informasi Peran -->
            <Card>
                <CardHeader>
                    <CardTitle>Informasi Peran</CardTitle>
                    <CardDescription>Masukkan nama unik untuk peran ini (contoh: manager, kasir, admin gudang).</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2 max-w-md">
                        <Label for="name">Nama Peran <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Contoh: staff gudang, customer service"
                            :class="{ 'border-destructive': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Grouped Permissions -->
            <Card>
                <CardHeader class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b">
                    <div>
                        <div class="flex items-center gap-2">
                            <CardTitle>Hak Akses (Permissions)</CardTitle>
                            <Badge variant="secondary" class="font-normal text-xs">
                                {{ form.permissions.length }} dari {{ permissions.length }} dipilih
                            </Badge>
                        </div>
                        <CardDescription class="mt-1">
                            Centang tindakan yang diizinkan untuk setiap modul di bawah ini.
                        </CardDescription>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Cari aksi / izin..."
                                class="pl-8 h-9 text-xs"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-9 text-xs"
                            @click="selectAll"
                        >
                            <CheckSquare class="mr-1.5 h-3.5 w-3.5 text-primary" />
                            Pilih Semua
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-9 text-xs"
                            @click="clearAll"
                        >
                            <Square class="mr-1.5 h-3.5 w-3.5 text-muted-foreground" />
                            Hapus Pilihan
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="pt-6">
                    <p v-if="form.errors.permissions" class="text-xs text-destructive mb-4">
                        {{ form.errors.permissions }}
                    </p>

                    <!-- Grouped Modules Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <div
                            v-for="group in groupedModules"
                            :key="group.meta.title"
                            class="flex flex-col rounded-xl border bg-card/60 transition-all hover:border-primary/40 shadow-xs overflow-hidden"
                            :class="{
                                'border-primary/30 ring-1 ring-primary/10': isModuleFullySelected(group.permissions),
                            }"
                        >
                            <!-- Module Header -->
                            <div class="flex items-center justify-between px-4 py-3 bg-muted/40 border-b">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="p-1.5 rounded-md bg-background border text-primary shrink-0">
                                        <component :is="group.meta.icon" class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-semibold text-sm text-foreground truncate">
                                                {{ group.meta.title }}
                                            </h4>
                                            <Badge
                                                variant="outline"
                                                class="text-[10px] px-1.5 py-0 font-normal shrink-0"
                                                :class="{
                                                    'bg-primary/10 text-primary border-primary/20': isModuleFullySelected(group.permissions),
                                                    'bg-amber-500/10 text-amber-600 border-amber-500/20': isModulePartiallySelected(group.permissions),
                                                }"
                                            >
                                                {{ group.permissions.filter((p) => form.permissions.includes(p.name)).length }}/{{ group.permissions.length }}
                                            </Badge>
                                        </div>
                                        <p class="text-[11px] text-muted-foreground truncate">
                                            {{ group.meta.description }}
                                        </p>
                                    </div>
                                </div>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs font-medium text-muted-foreground hover:text-foreground shrink-0 ml-2"
                                    @click="toggleModule(group.permissions)"
                                >
                                    {{ isModuleFullySelected(group.permissions) ? 'Batalkan' : 'Pilih Semua' }}
                                </Button>
                            </div>

                            <!-- Action Permissions List -->
                            <div class="p-3 space-y-2 flex-1 flex flex-col justify-center">
                                <div
                                    v-for="perm in group.permissions"
                                    :key="perm.id"
                                    class="flex items-center justify-between rounded-lg border p-2.5 transition-all cursor-pointer select-none"
                                    :class="form.permissions.includes(perm.name)
                                        ? 'bg-primary/5 border-primary/30 shadow-2xs'
                                        : 'bg-background hover:bg-muted/40 border-border/70'"
                                    @click="togglePermission(perm.name)"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                        <Checkbox
                                            :id="`perm-${perm.id}`"
                                            :model-value="form.permissions.includes(perm.name)"
                                            @click.stop
                                            @update:model-value="() => togglePermission(perm.name)"
                                        />
                                        <div class="grid gap-0.5 min-w-0">
                                            <label
                                                :for="`perm-${perm.id}`"
                                                class="text-xs font-semibold cursor-pointer truncate"
                                                :class="form.permissions.includes(perm.name) ? 'text-foreground' : 'text-muted-foreground'"
                                                @click.stop="togglePermission(perm.name)"
                                            >
                                                {{ formatAction(perm.name).label }}
                                            </label>
                                            <span class="text-[10px] text-muted-foreground font-mono truncate">
                                                {{ perm.name }}
                                            </span>
                                        </div>
                                    </div>

                                    <Badge
                                        variant="outline"
                                        class="text-[10px] px-2 py-0.5 font-medium shrink-0 ml-2"
                                        :class="formatAction(perm.name).badgeColor"
                                    >
                                        {{ perm.name.split(' ')[0] }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="groupedModules.length === 0" class="py-12 text-center text-muted-foreground text-sm">
                        Tidak ada hak akses yang cocok dengan pencarian "{{ searchQuery }}".
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center justify-end gap-3 pb-8">
                <Button type="button" variant="outline" as-child>
                    <Link href="/dashboard/roles">Batal</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    <Shield class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Peran' }}
                </Button>
            </div>
        </form>
    </div>
</template>
