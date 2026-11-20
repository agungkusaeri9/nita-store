<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    Users, 
    ShoppingBag, 
    Package, 
    CreditCard, 
    TrendingUp,
    Clock,
    CheckCircle,
    XCircle,
    Eye
} from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { computed } from 'vue';

// Chart.js imports
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
} from 'chart.js';
import { Line, Bar, Pie } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

const props = defineProps<{
    currentFilter: string;
    availableYears: number[];
    summary: {
        totalOrders: number;
        totalRevenue: number;
        totalCustomers: number;
        totalProducts: number;
    };
    chartData: {
        labels: string[];
        revenue: number[];
        orders: number[];
        status: number[]; // [pending, processing, success, failed]
    };
    recentOrders: any[];
}>();

const updateFilter = (filter: string | number) => {
    if (filter !== props.currentFilter) {
        router.get('/dashboard', { filter: filter.toString() }, { preserveState: true, preserveScroll: true });
    }
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
        case 'failed': return 'Batal';
        default: return st;
    }
};

// Chart Options & Data
const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: function(value: any) {
                    if (value >= 1000000) return 'Rp ' + (value / 1000000) + ' Jt';
                    if (value >= 1000) return 'Rp ' + (value / 1000) + ' Rb';
                    return 'Rp ' + value;
                }
            }
        }
    }
};

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
        }
    }
};

const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
        }
    }
};

const revenueChartData = computed(() => ({
    labels: props.chartData.labels,
    datasets: [
        {
            label: 'Pendapatan',
            backgroundColor: 'rgba(59, 130, 246, 0.2)', // Blue
            borderColor: '#3b82f6',
            pointBackgroundColor: '#3b82f6',
            data: props.chartData.revenue,
            fill: true,
            tension: 0.4
        }
    ]
}));

const ordersChartData = computed(() => ({
    labels: props.chartData.labels,
    datasets: [
        {
            label: 'Total Pesanan',
            backgroundColor: '#8b5cf6', // Violet
            data: props.chartData.orders,
        }
    ]
}));

const statusChartData = computed(() => ({
    labels: ['Menunggu', 'Diproses', 'Berhasil', 'Batal'],
    datasets: [
        {
            backgroundColor: ['#facc15', '#3b82f6', '#22c55e', '#ef4444'], // Yellow, Blue, Green, Red
            data: props.chartData.status,
            borderWidth: 0
        }
    ]
}));

</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 w-full">
        <Head title="Dashboard" />

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Dashboard</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Ringkasan aktivitas dan performa toko Anda.
                </p>
            </div>
            
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <Tabs :model-value="!currentFilter.startsWith('year-') ? currentFilter : ''" @update:model-value="updateFilter" class="flex-1 sm:flex-initial">
                    <TabsList class="grid w-full grid-cols-3 sm:flex">
                        <TabsTrigger value="today">Hari Ini</TabsTrigger>
                        <TabsTrigger value="this_week">Minggu Ini</TabsTrigger>
                        <TabsTrigger value="this_month">Bulan Ini</TabsTrigger>
                    </TabsList>
                </Tabs>
                
                <Select :model-value="currentFilter.startsWith('year-') ? currentFilter : ''" @update:model-value="updateFilter">
                    <SelectTrigger class="w-[120px] bg-background">
                        <SelectValue placeholder="Pilih Tahun" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="year in availableYears" :key="year" :value="`year-${year}`">{{ year }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Pendapatan</CardTitle>
                    <CreditCard class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-primary">{{ formatCurrency(summary.totalRevenue) }}</div>
                    <p class="text-xs text-muted-foreground mt-1">Pendapatan kotor pesanan sukses</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Pesanan</CardTitle>
                    <ShoppingBag class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ summary.totalOrders }}</div>
                    <p class="text-xs text-muted-foreground mt-1">Seluruh pesanan masuk</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Pelanggan</CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ summary.totalCustomers }}</div>
                    <p class="text-xs text-muted-foreground mt-1">Pelanggan terdaftar</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Produk Aktif</CardTitle>
                    <Package class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ summary.totalProducts }}</div>
                    <p class="text-xs text-muted-foreground mt-1">Total jenis produk</p>
                </CardContent>
            </Card>
        </div>

        <!-- Charts Row -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            
            <!-- Revenue Line Chart -->
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Trend Pendapatan</CardTitle>
                    <CardDescription>Grafik penjualan berdasarkan pesanan yang berhasil.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="h-72 w-full">
                        <Line :data="revenueChartData" :options="lineChartOptions" />
                    </div>
                </CardContent>
            </Card>

            <!-- Order Status Pie Chart -->
            <Card>
                <CardHeader>
                    <CardTitle>Status Pesanan</CardTitle>
                    <CardDescription>Proporsi status seluruh pesanan.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="h-72 w-full flex items-center justify-center">
                        <Pie :data="statusChartData" :options="pieChartOptions" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            
            <!-- Orders Bar Chart -->
            <Card class="lg:col-span-1">
                <CardHeader>
                    <CardTitle>Volume Pesanan Masuk</CardTitle>
                    <CardDescription>Total pesanan pada periode terpilih.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="h-72 w-full">
                        <Bar :data="ordersChartData" :options="barChartOptions" />
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Orders Table -->
            <Card class="lg:col-span-2">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Pesanan Terbaru</CardTitle>
                        <CardDescription>5 transaksi terakhir yang masuk.</CardDescription>
                    </div>
                    <Button variant="outline" size="sm" as-child>
                        <Link href="/dashboard/orders">Lihat Semua</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50 text-muted-foreground">
                                <tr>
                                    <th class="h-10 px-4 text-left font-medium">No. Pesanan</th>
                                    <th class="h-10 px-4 text-left font-medium">Pelanggan</th>
                                    <th class="h-10 px-4 text-left font-medium">Nominal</th>
                                    <th class="h-10 px-4 text-center font-medium">Status</th>
                                    <th class="h-10 px-4 text-right font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-if="recentOrders.length === 0">
                                    <td colspan="5" class="p-4 text-center text-muted-foreground">Belum ada pesanan masuk.</td>
                                </tr>
                                <tr v-for="order in recentOrders" :key="order.id" class="transition-colors hover:bg-muted/50">
                                    <td class="p-4 align-middle font-medium text-primary">
                                        {{ order.order_number }}
                                        <div class="text-[10px] text-muted-foreground font-normal mt-0.5">{{ formatDate(order.created_at) }}</div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        {{ order.customer?.name || 'Customer' }}
                                    </td>
                                    <td class="p-4 align-middle font-semibold">
                                        {{ formatCurrency(order.total_amount) }}
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
                </CardContent>
            </Card>
        </div>

    </div>
</template>
