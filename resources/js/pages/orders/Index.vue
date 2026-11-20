<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Eye, 
    Search,
    ShoppingBag,
    Filter,
    X
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Order {
    id: number;
    order_number: string;
    subtotal: string;
    shipping_fee: string;
    total_amount: string;
    status: 'pending' | 'processing' | 'success' | 'failed';
    created_at: string;
    customer: {
        name: string;
        email: string;
    };
    payment_method: {
        name: string;
    } | null;
    courier: {
        name: string;
    } | null;
}

interface PaginationData {
    data: Order[];
    current_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    orders: PaginationData;
    filters: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
        per_page?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pesanan', href: '/dashboard/orders' },
];

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const perPage = ref(props.filters.per_page || '10');
const perPageOptions = ['10', '25', '50', '100'];

const applyFilters = () => {
    router.get(
        '/dashboard/orders',
        {
            search: search.value,
            status: status.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const hasActiveFilter = computed(
    () => !!search.value || status.value !== 'all' || !!dateFrom.value || !!dateTo.value
);

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const formatCurrency = (amount: number | string) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(Number(amount));
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(dateString));
};

const getStatusColor = (st: string) => {
    switch (st) {
        case 'success': return 'bg-green-100 text-green-800 border-green-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'processing': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'failed': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getStatusLabel = (st: string) => {
    switch (st) {
        case 'success': return 'Berhasil';
        case 'pending': return 'Menunggu';
        case 'processing': return 'Diproses';
        case 'failed': return 'Gagal / Batal';
        default: return st;
    }
};

let searchTimeout: ReturnType<typeof setTimeout>;
watch(perPage, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Manajemen Pesanan" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <ShoppingBag class="h-8 w-8 text-primary" />
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Pesanan</h1>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Kelola pesanan dari pelanggan, pantau status, dan verifikasi pembayaran.
                    </p>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="rounded-xl border bg-card p-4 shadow-xs">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-1.5">
                        <Label for="filter-status" class="text-xs font-medium text-muted-foreground">Status Pesanan</Label>
                        <Select v-model="status">
                            <SelectTrigger id="filter-status" class="h-9 w-full bg-background text-sm">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="pending">Menunggu</SelectItem>
                                <SelectItem value="processing">Diproses</SelectItem>
                                <SelectItem value="success">Berhasil</SelectItem>
                                <SelectItem value="failed">Gagal / Batal</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="filter-date-from" class="text-xs font-medium text-muted-foreground">Tanggal Mulai</Label>
                        <Input
                            id="filter-date-from"
                            v-model="dateFrom"
                            type="date"
                            class="h-9 w-full bg-background text-sm"
                            :max="dateTo || undefined"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="filter-date-to" class="text-xs font-medium text-muted-foreground">Tanggal Akhir</Label>
                        <Input
                            id="filter-date-to"
                            v-model="dateTo"
                            type="date"
                            class="h-9 w-full bg-background text-sm"
                            :min="dateFrom || undefined"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="filter-search" class="text-xs font-medium text-muted-foreground">Pencarian</Label>
                        <div class="relative">
                            <Search class="absolute left-2.5 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="filter-search"
                                v-model="search"
                                type="search"
                                placeholder="No Pesanan / Pelanggan..."
                                class="h-9 w-full bg-background pl-8 text-sm"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2 border-t pt-4">
                    <Button
                        v-if="hasActiveFilter"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                        @click="resetFilters"
                    >
                        <X class="h-3.5 w-3.5" />
                        Reset
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        class="gap-1.5 text-xs"
                        @click="applyFilters"
                    >
                        <Filter class="h-3.5 w-3.5" />
                        Terapkan Filter
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
                                <SelectItem v-for="opt in perPageOptions" :key="opt" :value="opt">
                                    {{ opt }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <span>data</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-muted-foreground">
                            <tr>
                                <th class="h-10 px-4 text-left font-medium">No. Pesanan / Tanggal</th>
                                <th class="h-10 px-4 text-left font-medium">Pelanggan</th>
                                <th class="h-10 px-4 text-left font-medium">Total Harga</th>
                                <th class="h-10 px-4 text-left font-medium">Pembayaran & Kurir</th>
                                <th class="h-10 px-4 text-center font-medium">Status</th>
                                <th class="h-10 px-4 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-if="orders.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-muted-foreground">
                                    Tidak ada data pesanan ditemukan.
                                </td>
                            </tr>
                            <tr v-for="order in orders.data" :key="order.id" class="transition-colors hover:bg-muted/50">
                                <td class="p-4 align-middle">
                                    <div class="font-medium text-primary">{{ order.order_number }}</div>
                                    <div class="text-xs text-muted-foreground">{{ formatDate(order.created_at) }}</div>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="font-medium">{{ order.customer?.name || 'Tidak diketahui' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ order.customer?.email }}</div>
                                </td>
                                <td class="p-4 align-middle font-semibold">
                                    {{ formatCurrency(order.total_amount) }}
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="text-xs">
                                        <span class="font-medium">Bank:</span> {{ order.payment_method?.name || '-' }}
                                    </div>
                                    <div class="text-xs mt-1">
                                        <span class="font-medium">Kurir:</span> {{ order.courier?.name || '-' }}
                                    </div>
                                </td>
                                <td class="p-4 align-middle text-center">
                                    <Badge variant="outline" :class="getStatusColor(order.status)">
                                        {{ getStatusLabel(order.status) }}
                                    </Badge>
                                </td>
                                <td class="p-4 align-middle text-right">
                                    <Button as-child variant="ghost" size="icon" class="h-8 w-8">
                                        <Link :href="`/dashboard/orders/${order.id}`">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between" v-if="orders.data.length > 0">
                    <div class="text-xs text-muted-foreground">
                        Menampilkan {{ orders.from || 0 }} sampai {{ orders.to || 0 }} dari {{ orders.total }} data
                    </div>
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="(link, i) in orders.links" :key="i">
                            <Button
                                v-if="link.url"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                class="h-8 px-3 text-xs"
                                @click="router.get(link.url, { search, status, date_from: dateFrom, date_to: dateTo, per_page: perPage }, { preserveState: true })"
                                v-html="link.label"
                            />
                            <span v-else class="px-2 py-1 text-xs text-muted-foreground" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
        </div>
    </div>
</template>
