<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { 
    ChevronLeft, 
    Receipt, 
    Truck, 
    CreditCard, 
    MapPin, 
    Package,
    CheckCircle,
    XCircle,
    Clock,
    User,
    Loader2
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ref, watch } from 'vue';
import { Copy, Check, Send } from 'lucide-vue-next';
import { usePermissions } from '@/composables/usePermissions';

interface Props {
    order: any;
}

const props = defineProps<Props>();
const { can } = usePermissions();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pesanan', href: '/dashboard/orders' },
    { title: props.order.order_number, href: `/dashboard/orders/${props.order.id}` },
];

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
        month: 'long',
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
        case 'success': return 'Berhasil / Selesai';
        case 'pending': return 'Menunggu Pembayaran';
        case 'processing': return 'Sedang Diproses';
        case 'failed': return 'Dibatalkan / Gagal';
        default: return st;
    }
};

const form = useForm({
    status: props.order.status
});

const updateStatus = (newStatus: string) => {
    form.status = newStatus;
    form.post(`/dashboard/orders/${props.order.id}/status`, {
        preserveScroll: true
    });
};

const paymentForm = useForm({
    payment_status: props.order.transaction?.status
});

const updatePaymentStatus = (newStatus: string) => {
    paymentForm.payment_status = newStatus;
    paymentForm.post(`/dashboard/orders/${props.order.id}/payment`, {
        preserveScroll: true
    });
};

const shippingForm = useForm({
    shipping_name: props.order.shipping_name || '',
    tracking_number: props.order.tracking_number || '',
});

const copiedResi = ref(false);
const copyResi = () => {
    if (props.order.tracking_number) {
        navigator.clipboard.writeText(props.order.tracking_number);
        copiedResi.value = true;
        setTimeout(() => {
            copiedResi.value = false;
        }, 2000);
    }
};

const updateShipping = () => {
    shippingForm.post(`/dashboard/orders/${props.order.id}/shipping`, {
        preserveScroll: true
    });
};

watch(() => props.order, (newOrder) => {
    if (newOrder) {
        form.status = newOrder.status;
        paymentForm.payment_status = newOrder.transaction?.status;
        shippingForm.shipping_name = newOrder.shipping_name || '';
        shippingForm.tracking_number = newOrder.tracking_number || '';
    }
}, { deep: true });
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 w-full">
        <Head :title="`Detail Pesanan ${order.order_number}`" />
        
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Button as-child variant="outline" size="icon" class="h-8 w-8">
                        <Link href="/dashboard/orders"><ChevronLeft class="h-4 w-4" /></Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            Pesanan #{{ order.order_number }}
                            <Badge variant="outline" :class="getStatusColor(order.status)">
                                {{ getStatusLabel(order.status) }}
                            </Badge>
                        </h1>
                        <p class="text-sm text-muted-foreground mt-1">
                            Dibuat pada {{ formatDate(order.created_at) }}
                        </p>
                    </div>
                </div>

                <!-- Admin Action Buttons for Status -->
                <div class="flex items-center gap-2">
                    <!-- Status action moved to below products card -->
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-2">
                <!-- Kiri: Detail Order & Items -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-xl border bg-card shadow-sm p-6">
                        <div class="flex items-center gap-2 border-b pb-4 mb-4">
                            <Package class="h-5 w-5 text-primary" />
                            <h3 class="text-lg font-semibold">Produk yang Dipesan</h3>
                        </div>

                        <div class="space-y-4">
                            <div v-for="item in order.details" :key="item.id" class="flex gap-4 items-start">
                                <div class="h-16 w-16 rounded border bg-muted flex items-center justify-center shrink-0 overflow-hidden">
                                    <img 
                                        v-if="item.product?.primary_image_url || item.product?.primary_image || item.product?.image" 
                                        :src="item.product?.primary_image_url || (item.product?.primary_image ? `/storage/${item.product.primary_image}` : `/storage/${item.product.image}`)" 
                                        :alt="item.product?.name"
                                        class="object-cover h-full w-full" 
                                    />
                                    <Package v-else class="h-8 w-8 text-muted-foreground" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium line-clamp-1 text-foreground">{{ item.product?.name || 'Produk Dihapus' }}</h4>
                                    
                                    <!-- Info Varian Produk yang Dipilih (Mendukung Multi-Varian seperti Ukuran, Warna, dll) -->
                                    <div v-if="item.variant_info" class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                                        <Badge 
                                            v-for="(variant, idx) in item.variant_info.split(',').map((v: string) => v.trim()).filter(Boolean)" 
                                            :key="idx"
                                            variant="secondary" 
                                            class="text-xs font-normal py-0.5 px-2 bg-muted text-foreground border rounded-md"
                                        >
                                            {{ variant }}
                                        </Badge>
                                    </div>
                                    
                                    <p class="text-sm text-muted-foreground mt-1">{{ formatCurrency(item.price) }} x {{ item.quantity }}</p>
                                </div>
                                <div class="font-semibold text-right">
                                    {{ formatCurrency(item.total_price) }}
                                </div>
                            </div>
                        </div>

                        <Separator class="my-6" />

                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Subtotal Produk</span>
                                <span>{{ formatCurrency(order.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Biaya Pengiriman</span>
                                <span>{{ formatCurrency(order.shipping_fee) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg pt-2 border-t mt-2">
                                <span>Total Belanja</span>
                                <span class="text-primary">{{ formatCurrency(order.total_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu Aksi Pesanan -->
                    <div v-if="can('update order status')" class="rounded-xl border bg-card shadow-sm p-6">
                        <div class="flex items-center gap-2 border-b pb-4 mb-4">
                            <Clock class="h-5 w-5 text-primary" />
                            <h3 class="font-semibold">Aksi Status Pesanan</h3>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">
                            Perbarui status pesanan saat ini ({{ getStatusLabel(order.status) }}).
                        </p>
                        
                        <div class="flex flex-wrap gap-3">
                            <Button 
                                variant="outline" 
                                class="bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100"
                                @click="updateStatus('processing')"
                                :disabled="form.processing || order.status === 'processing'"
                            >
                                <Loader2 v-if="form.processing && form.status === 'processing'" class="mr-2 h-4 w-4 animate-spin" />
                                <Clock v-else class="mr-2 h-4 w-4" />
                                Proses Pesanan
                            </Button>
                            
                            <Button 
                                variant="outline" 
                                class="bg-green-50 text-green-700 border-green-200 hover:bg-green-100"
                                @click="updateStatus('success')"
                                :disabled="form.processing || order.status === 'success'"
                            >
                                <Loader2 v-if="form.processing && form.status === 'success'" class="mr-2 h-4 w-4 animate-spin" />
                                <CheckCircle v-else class="mr-2 h-4 w-4" />
                                Selesaikan Pesanan
                            </Button>

                            <Button 
                                variant="outline" 
                                class="bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100"
                                @click="updateStatus('pending')"
                                :disabled="form.processing || order.status === 'pending'"
                            >
                                <Loader2 v-if="form.processing && form.status === 'pending'" class="mr-2 h-4 w-4 animate-spin" />
                                <Clock v-else class="mr-2 h-4 w-4" />
                                Kembalikan ke Menunggu
                            </Button>

                            <Button 
                                variant="outline" 
                                class="bg-red-50 text-red-700 border-red-200 hover:bg-red-100"
                                @click="updateStatus('failed')"
                                :disabled="form.processing || order.status === 'failed'"
                            >
                                <Loader2 v-if="form.processing && form.status === 'failed'" class="mr-2 h-4 w-4 animate-spin" />
                                <XCircle v-else class="mr-2 h-4 w-4" />
                                Batalkan Pesanan
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Kanan: Customer, Payment, Shipping -->
                <div class="space-y-6">
                    <div class="rounded-xl border bg-card shadow-sm p-6">
                        <div class="flex items-center gap-2 border-b pb-4 mb-4">
                            <User class="h-5 w-5 text-primary" />
                            <h3 class="font-semibold">Informasi Pelanggan</h3>
                        </div>
                        <div class="text-sm space-y-1">
                            <p class="font-medium text-base">{{ order.customer?.name || 'Customer' }}</p>
                            <p class="text-muted-foreground">{{ order.customer?.email }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card shadow-sm p-6">
                        <div class="flex items-center gap-2 border-b pb-4 mb-4">
                            <MapPin class="h-5 w-5 text-primary" />
                            <h3 class="font-semibold">Alamat Pengiriman</h3>
                        </div>
                        <div class="text-sm space-y-2" v-if="order.province && order.city">
                            <p class="font-medium">{{ order.customer?.name }}</p>
                            <p class="text-muted-foreground leading-relaxed">
                                {{ order.address }}<br>
                                Kec. {{ order.subdistrict?.subdistrict_name }}, {{ order.city?.city_name }}<br>
                                Prov. {{ order.province?.province_name }}
                            </p>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">Alamat tidak tersedia.</div>
                    </div>

                    <!-- Kartu Pengiriman & Nomor Resi (Aktif jika pembayaran lunas) -->
                    <div class="rounded-xl border bg-card shadow-sm p-6">
                        <div class="flex items-center justify-between border-b pb-4 mb-4">
                            <div class="flex items-center gap-2">
                                <Truck class="h-5 w-5 text-primary" />
                                <h3 class="font-semibold">Pengiriman & Nomor Resi</h3>
                            </div>
                            <Badge v-if="order.tracking_number" variant="outline" class="bg-green-50 text-green-700 border-green-200 text-xs">
                                Resi Terbit
                            </Badge>
                        </div>

                        <!-- Kondisi: Pembayaran Belum Lunas -->
                        <div v-if="order.transaction?.status !== 'paid'" class="p-3.5 rounded-lg bg-amber-50/70 border border-amber-200 text-amber-900 text-xs leading-relaxed flex items-start gap-2">
                            <Clock class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" />
                            <span>Ekspedisi dan nomor resi dapat diisi setelah pembayaran berstatus <strong>Lunas</strong> dan pesanan sedang diproses.</span>
                        </div>

                        <!-- Kondisi: Pembayaran Lunas -->
                        <div v-else class="space-y-4">
                            <form @submit.prevent="updateShipping" class="space-y-3">
                                <div class="space-y-1.5">
                                    <Label for="shipping_name" class="text-xs font-medium">Nama Ekspedisi / Pengiriman</Label>
                                    <Input 
                                        id="shipping_name"
                                        v-model="shippingForm.shipping_name"
                                        placeholder="Contoh: JNE Regular, J&T Express, SiCepat"
                                        class="text-sm h-9"
                                        :disabled="shippingForm.processing"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="tracking_number" class="text-xs font-medium">Nomor Resi Pengiriman</Label>
                                    <div class="flex gap-2">
                                        <Input 
                                            id="tracking_number"
                                            v-model="shippingForm.tracking_number"
                                            placeholder="Contoh: JNE102938484"
                                            class="text-sm h-9 font-mono uppercase"
                                            :disabled="shippingForm.processing"
                                        />
                                        <Button 
                                            v-if="order.tracking_number" 
                                            type="button" 
                                            variant="outline" 
                                            size="icon" 
                                            class="h-9 w-9 shrink-0" 
                                            @click="copyResi" 
                                            title="Salin Resi"
                                        >
                                            <Check v-if="copiedResi" class="h-4 w-4 text-green-600" />
                                            <Copy v-else class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <div v-if="can('update order shipping')" class="pt-1 flex justify-end">
                                    <Button 
                                        type="submit" 
                                        size="sm" 
                                        class="gap-1.5 text-xs w-full sm:w-auto"
                                        :disabled="shippingForm.processing || (shippingForm.shipping_name === (order.shipping_name || '') && shippingForm.tracking_number === (order.tracking_number || ''))"
                                    >
                                        <Loader2 v-if="shippingForm.processing" class="h-3.5 w-3.5 animate-spin" />
                                        <Send v-else class="h-3.5 w-3.5" />
                                        Simpan Nomor Resi
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card shadow-sm p-6">
                        <div class="flex items-center gap-2 border-b pb-4 mb-4">
                            <CreditCard class="h-5 w-5 text-primary" />
                            <h3 class="font-semibold">Pembayaran</h3>
                        </div>
                        <div class="text-sm space-y-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Metode Pembayaran</p>
                                <p class="font-medium">{{ order.payment_method?.name || '-' }} ({{ order.payment_method?.account_number || '-' }})</p>
                            </div>
                            
                            <div>
                                <p class="text-xs text-muted-foreground">Status Transaksi</p>
                                <Badge variant="secondary" class="mt-1">
                                    {{ order.transaction?.status === 'paid' ? 'Lunas' : (order.transaction?.status === 'failed' ? 'Gagal' : 'Menunggu') }}
                                </Badge>
                            </div>

                            <div v-if="order.transaction?.payment_proof">
                                <p class="text-xs text-muted-foreground mb-2">Bukti Pembayaran</p>
                                <a :href="`/storage/${order.transaction.payment_proof}`" target="_blank" class="block rounded border p-1 bg-muted/30 hover:bg-muted/50 transition cursor-pointer">
                                    <img :src="`/storage/${order.transaction.payment_proof}`" alt="Bukti Transfer" class="w-full h-32 object-cover rounded" />
                                </a>
                            </div>
                            <div v-else class="p-3 bg-muted/50 rounded border border-dashed text-center text-xs text-muted-foreground">
                                Belum ada bukti pembayaran
                            </div>
                            
                            <div v-if="can('update order payment')" class="pt-4 border-t mt-4 flex flex-col gap-2">
                                <p class="text-xs font-semibold text-muted-foreground mb-1">Aksi Pembayaran</p>
                                <div class="flex gap-2">
                                    <Button 
                                        size="sm" 
                                        variant="outline" 
                                        class="flex-1 border-green-200 bg-green-50 hover:bg-green-100 text-green-700"
                                        @click="updatePaymentStatus('paid')"
                                        :disabled="paymentForm.processing || order.transaction?.status === 'paid'"
                                    >
                                        <Loader2 v-if="paymentForm.processing && paymentForm.payment_status === 'paid'" class="mr-2 h-4 w-4 animate-spin" />
                                        <CheckCircle v-else class="mr-2 h-4 w-4" />
                                        Lunas
                                    </Button>
                                    <Button 
                                        size="sm" 
                                        variant="outline" 
                                        class="flex-1 border-red-200 bg-red-50 hover:bg-red-100 text-red-700"
                                        @click="updatePaymentStatus('failed')"
                                        :disabled="paymentForm.processing || order.transaction?.status === 'failed'"
                                    >
                                        <Loader2 v-if="paymentForm.processing && paymentForm.payment_status === 'failed'" class="mr-2 h-4 w-4 animate-spin" />
                                        <XCircle v-else class="mr-2 h-4 w-4" />
                                        Gagal
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</template>
