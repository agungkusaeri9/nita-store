<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Check,
    CheckCircle2,
    ChevronRight,
    Clock,
    Copy,
    CreditCard,
    ImagePlus,
    Loader2,
    MapPin,
    MessageCircle,
    Package,
    Receipt,
    Truck,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import type { StoreIdentity } from '@/types/storefront';

interface OrderDetailItem {
    id: number;
    quantity: number;
    price: number;
    total_price: number;
    variant_info: string | null;
    product_name: string;
    product_slug: string | null;
    product_image_url: string | null;
}

interface OrderDetailData {
    id: number;
    order_number: string;
    status: string;
    created_at: string | null;
    subtotal: number;
    shipping_fee: number;
    total_amount: number;
    address: string | null;
    region: string | null;
    shipping_name: string | null;
    tracking_number: string | null;
    courier: {
        name: string;
        code: string | null;
    } | null;
    payment_method: {
        name: string;
        account_number: string | null;
        account_name: string | null;
        logo_url: string | null;
    } | null;
    transaction: {
        status: string;
        payment_proof_url: string | null;
    } | null;
    items: OrderDetailItem[];
}

const props = defineProps<{
    store: StoreIdentity;
    order: OrderDetailData;
}>();

const page = usePage<any>();
const flashSuccess = computed<string>(() => page.props.flash?.success ?? '');
const flashError = computed<string>(() => page.props.flash?.error ?? '');

const formatRupiah = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const statusLabel = (status: string): string => {
    switch (status) {
        case 'pending':
            return 'Menunggu Pembayaran';
        case 'processing':
            return 'Diproses';
        case 'success':
        case 'completed':
            return 'Selesai';
        case 'failed':
            return 'Dibatalkan / Gagal';
        default:
            return status;
    }
};

const statusVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (status) {
        case 'success':
        case 'completed':
            return 'default';
        case 'processing':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
};

// Copy account number
const copiedAccount = ref(false);
const copyAccountNumber = async (): Promise<void> => {
    const accountNumber = props.order.payment_method?.account_number;
    if (!accountNumber || typeof navigator === 'undefined') {
        return;
    }

    try {
        await navigator.clipboard.writeText(accountNumber);
        copiedAccount.value = true;
        setTimeout(() => {
            copiedAccount.value = false;
        }, 2000);
    } catch {
        // Ignore clipboard failure
    }
};

// Upload payment proof
const proofPreviewUrl = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const proofForm = useForm<{ payment_proof: File | null }>({
    payment_proof: null,
});

const handleFileChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    proofForm.payment_proof = file;
    proofForm.clearErrors('payment_proof');

    if (proofPreviewUrl.value) {
        URL.revokeObjectURL(proofPreviewUrl.value);
    }

    proofPreviewUrl.value = file ? URL.createObjectURL(file) : null;
};

const submitPaymentProof = (): void => {
    if (!proofForm.payment_proof || proofForm.processing) {
        return;
    }

    proofForm.post(`/my-orders/${props.order.order_number}/payment-proof`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            proofForm.reset();
            if (proofPreviewUrl.value) {
                URL.revokeObjectURL(proofPreviewUrl.value);
                proofPreviewUrl.value = null;
            }
            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
        },
    });
};

const whatsappConfirmUrl = computed<string>(() => {
    if (!props.store.whatsapp) {
        return '#';
    }

    const digits = props.store.whatsapp.replace(/\D/g, '');
    const normalized = digits.startsWith('0') ? `62${digits.slice(1)}` : digits;

    const itemLines = props.order.items.map((item, idx) => {
        const variant = item.variant_info ? ` (${item.variant_info})` : '';

        return `${idx + 1}. ${item.product_name}${variant} — ${item.quantity} x ${formatRupiah(item.price)} = ${formatRupiah(item.total_price)}`;
    });

    const shippingLabel = props.order.shipping_name || props.order.courier?.name || '-';
    const paymentLabel = props.order.payment_method
        ? [
              props.order.payment_method.name,
              props.order.payment_method.account_number
                  ? `(${props.order.payment_method.account_number}${
                        props.order.payment_method.account_name
                            ? ` a.n. ${props.order.payment_method.account_name}`
                            : ''
                    })`
                  : null,
          ]
              .filter(Boolean)
              .join(' ')
        : '-';
    const fullAddress = [props.order.address, props.order.region].filter(Boolean).join(', ');

    const lines = [
        `Halo *${props.store.name}*, saya ingin konfirmasi bahwa saya sudah melakukan pembayaran untuk pesanan berikut:`,
        '',
        `*No. Pesanan:* ${props.order.order_number}`,
        props.order.created_at ? `*Tanggal:* ${props.order.created_at}` : null,
        `*Status Saat Ini:* ${statusLabel(props.order.status)}`,
        '',
        '*Detail Produk:*',
        ...itemLines,
        '',
        '*Ringkasan Biaya:*',
        `• Subtotal Produk: ${formatRupiah(props.order.subtotal)}`,
        `• Ongkos Kirim (${shippingLabel}): ${formatRupiah(props.order.shipping_fee)}`,
        `• *Total Pembayaran:* *${formatRupiah(props.order.total_amount)}*`,
        '',
        `*Metode Pembayaran:* ${paymentLabel}`,
        fullAddress ? `*Alamat Pengiriman:* ${fullAddress}` : null,
        '',
        'Mohon bantuannya untuk verifikasi pembayaran dan proses pengirimannya. Terima kasih!',
    ].filter((line): line is string => line !== null);

    return `https://wa.me/${normalized}?text=${encodeURIComponent(lines.join('\n'))}`;
});
</script>

<template>
    <div>
        <Head :title="`Detail Pesanan ${order.order_number} - ${store.name}`" />

        <StorefrontShell :store="store">
            <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                    <Link href="/" class="hover:text-primary">Beranda</Link>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <Link href="/my-orders" class="hover:text-primary">Pesanan Saya</Link>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <span class="font-mono font-medium text-foreground">{{ order.order_number }}</span>
                </nav>

                <!-- Alert Sukses Checkout / Upload Bukti -->
                <div
                    v-if="flashSuccess"
                    class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-emerald-900 shadow-xs dark:border-emerald-800/70 dark:bg-emerald-950/50 dark:text-emerald-200"
                >
                    <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                    <div class="text-sm">
                        <p class="font-bold">{{ flashSuccess }}</p>
                        <p class="mt-0.5 text-xs opacity-90">
                            Detail pesanan Anda telah tersimpan. Pantau status pesanan Anda langsung dari halaman ini.
                        </p>
                    </div>
                </div>

                <!-- Alert Error -->
                <div
                    v-if="flashError"
                    class="mb-5 flex items-start gap-3 rounded-2xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive"
                >
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0" />
                    <span>{{ flashError }}</span>
                </div>

                <!-- Info Pengingat Segera Upload Bukti Pembayaran -->
                <div
                    v-if="order.status === 'pending' && !order.transaction?.payment_proof_url"
                    class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50/80 p-4 text-amber-950 shadow-xs dark:border-amber-800/70 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    <Clock class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
                    <div class="text-sm">
                        <p class="font-bold">Segera Lakukan Pembayaran &amp; Unggah Bukti Pembayaran</p>
                        <p class="mt-1 text-xs leading-relaxed opacity-90">
                            Silakan transfer sesuai total tagihan ke rekening tujuan di bawah ini, lalu unggah bukti
                            pembayaran Anda agar pesanan dapat segera kami verifikasi, dikemas, dan dikirim.
                        </p>
                    </div>
                </div>

                <!-- Info Bukti Sudah Diunggah & Menunggu Verifikasi -->
                <div
                    v-else-if="order.status === 'pending' && order.transaction?.payment_proof_url"
                    class="mb-6 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50/80 p-4 text-blue-950 shadow-xs dark:border-blue-800/70 dark:bg-blue-950/40 dark:text-blue-200"
                >
                    <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" />
                    <div class="text-sm">
                        <p class="font-bold">Bukti Pembayaran Telah Diunggah</p>
                        <p class="mt-1 text-xs leading-relaxed opacity-90">
                            Terima kasih! Bukti pembayaran Anda sudah kami terima dan sedang menunggu verifikasi admin
                            untuk diproses ke tahap pengemasan dan pengiriman.
                        </p>
                    </div>
                </div>

                <!-- Header Order -->
                <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-border bg-card p-5 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Receipt class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="font-mono text-lg font-bold text-foreground sm:text-xl">
                                    {{ order.order_number }}
                                </h1>
                                <Badge :variant="statusVariant(order.status)">
                                    {{ statusLabel(order.status) }}
                                </Badge>
                            </div>
                            <p v-if="order.created_at" class="mt-0.5 text-xs text-muted-foreground">
                                Dibuat pada {{ order.created_at }}
                            </p>
                        </div>
                    </div>

                    <Link href="/my-orders">
                        <Button variant="outline" size="sm" class="gap-1.5">
                            <ArrowLeft class="h-4 w-4" />
                            Semua Pesanan
                        </Button>
                    </Link>
                </div>

                <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                    <!-- KIRI: PRODUK, PENGIRIMAN & ALAMAT -->
                    <div class="space-y-6 lg:col-span-7">
                        <!-- Daftar Produk -->
                        <section class="rounded-2xl border border-border bg-card p-5 shadow-xs">
                            <h2 class="text-base font-bold text-foreground">Daftar Produk</h2>
                            <Separator class="my-4" />

                            <div class="divide-y divide-border">
                                <div
                                    v-for="item in order.items"
                                    :key="item.id"
                                    class="flex items-center justify-between gap-4 py-3.5 first:pt-0 last:pb-0"
                                >
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
                                            <img
                                                v-if="item.product_image_url"
                                                :src="item.product_image_url"
                                                :alt="item.product_name"
                                                class="h-full w-full object-cover"
                                            />
                                            <Package v-else class="h-5 w-5 text-muted-foreground/60" />
                                        </div>
                                        <div class="min-w-0">
                                            <Link
                                                v-if="item.product_slug"
                                                :href="`/products/${item.product_slug}`"
                                                class="block truncate text-sm font-semibold text-foreground hover:text-primary"
                                            >
                                                {{ item.product_name }}
                                            </Link>
                                            <span v-else class="block truncate text-sm font-semibold text-foreground">
                                                {{ item.product_name }}
                                            </span>
                                            <p v-if="item.variant_info" class="text-xs text-muted-foreground">
                                                Varian: {{ item.variant_info }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ item.quantity }} x {{ formatRupiah(item.price) }}
                                            </p>
                                        </div>
                                    </div>

                                    <span class="shrink-0 text-sm font-semibold text-foreground">
                                        {{ formatRupiah(item.total_price) }}
                                    </span>
                                </div>
                            </div>
                        </section>

                        <!-- Alamat & Pengiriman -->
                        <section class="rounded-2xl border border-border bg-card p-5 shadow-xs">
                            <h2 class="text-base font-bold text-foreground">Informasi Pengiriman</h2>
                            <Separator class="my-4" />

                            <div class="space-y-4 text-xs sm:text-sm">
                                <div class="flex items-start gap-3">
                                    <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                    <div class="space-y-1">
                                        <p class="font-semibold text-foreground">Alamat Tujuan</p>
                                        <p class="leading-relaxed text-muted-foreground">
                                            {{ order.address || '-' }}
                                        </p>
                                        <p v-if="order.region" class="text-xs text-muted-foreground">
                                            {{ order.region }}
                                        </p>
                                    </div>
                                </div>

                                <Separator />

                                <div class="flex items-start gap-3">
                                    <Truck class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                    <div class="space-y-1">
                                        <p class="font-semibold text-foreground">Kurir &amp; Layanan</p>
                                        <p class="text-muted-foreground">
                                            {{ order.shipping_name || order.courier?.name || '-' }}
                                        </p>
                                        <p v-if="order.tracking_number" class="font-mono text-xs text-foreground">
                                            No. Resi: <strong>{{ order.tracking_number }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- KANAN: RINGKASAN HARGA & UPLOAD BUKTI PEMBAYARAN -->
                    <div class="space-y-6 lg:col-span-5">
                        <!-- Ringkasan Pembayaran -->
                        <section class="rounded-2xl border border-border bg-card p-5 shadow-xs">
                            <h2 class="text-base font-bold text-foreground">Ringkasan Tagihan</h2>
                            <Separator class="my-4" />

                            <div class="space-y-2.5 text-sm">
                                <div class="flex items-center justify-between text-muted-foreground">
                                    <span>Subtotal Produk</span>
                                    <span class="font-medium text-foreground">{{ formatRupiah(order.subtotal) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-muted-foreground">
                                    <span>Ongkos Kirim</span>
                                    <span class="font-medium text-foreground">{{ formatRupiah(order.shipping_fee) }}</span>
                                </div>
                            </div>

                            <Separator class="my-4" />

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-foreground">Total Pembayaran</span>
                                <span class="text-xl font-extrabold text-primary">
                                    {{ formatRupiah(order.total_amount) }}
                                </span>
                            </div>
                        </section>

                        <!-- Tujuan Transfer & Upload Bukti Pembayaran -->
                        <section class="rounded-2xl border border-border bg-card p-5 shadow-xs">
                            <div class="flex items-center gap-2.5">
                                <CreditCard class="h-4.5 w-4.5 text-primary" />
                                <h2 class="text-base font-bold text-foreground">Pembayaran &amp; Bukti Transfer</h2>
                            </div>
                            <Separator class="my-4" />

                            <div v-if="order.payment_method" class="mb-4 rounded-xl border border-border bg-muted/30 p-3.5">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="order.payment_method.logo_url"
                                        :src="order.payment_method.logo_url"
                                        :alt="order.payment_method.name"
                                        class="h-8 w-12 shrink-0 rounded border border-border bg-white object-contain p-1"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-foreground">
                                            {{ order.payment_method.name }}
                                        </p>
                                        <p v-if="order.payment_method.account_name" class="text-xs text-muted-foreground">
                                            a.n. {{ order.payment_method.account_name }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="order.payment_method.account_number"
                                    class="mt-3 flex items-center justify-between gap-2 rounded-lg border border-border bg-background px-3 py-2"
                                >
                                    <span class="font-mono text-sm font-bold text-foreground">
                                        {{ order.payment_method.account_number }}
                                    </span>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="copyAccountNumber"
                                        class="h-7 gap-1 px-2 text-xs"
                                    >
                                        <Check v-if="copiedAccount" class="h-3.5 w-3.5 text-emerald-600" />
                                        <Copy v-else class="h-3.5 w-3.5" />
                                        {{ copiedAccount ? 'Disalin' : 'Salin' }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Preview Bukti Tersimpan -->
                            <div v-if="order.transaction?.payment_proof_url" class="mb-4 space-y-2">
                                <p class="text-xs font-semibold text-foreground">Bukti Pembayaran Saat Ini:</p>
                                <a
                                    :href="order.transaction.payment_proof_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block overflow-hidden rounded-xl border border-border bg-muted"
                                >
                                    <img
                                        :src="order.transaction.payment_proof_url"
                                        alt="Bukti Pembayaran"
                                        class="max-h-60 w-full object-contain"
                                    />
                                </a>
                            </div>

                            <!-- Form Upload / Ganti Bukti -->
                            <form
                                v-if="order.status === 'pending'"
                                @submit.prevent="submitPaymentProof"
                                class="space-y-3"
                            >
                                <label
                                    for="payment_proof_input"
                                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/20 p-4 text-center transition hover:border-primary/60 hover:bg-muted/40"
                                >
                                    <img
                                        v-if="proofPreviewUrl"
                                        :src="proofPreviewUrl"
                                        alt="Preview Bukti Pembayaran"
                                        class="mb-2 max-h-44 rounded-lg object-contain"
                                    />
                                    <ImagePlus v-else class="mb-2 h-7 w-7 text-muted-foreground" />
                                    <span class="text-xs font-semibold text-foreground">
                                        {{
                                            proofForm.payment_proof
                                                ? proofForm.payment_proof.name
                                                : order.transaction?.payment_proof_url
                                                  ? 'Pilih foto baru untuk mengganti bukti pembayaran'
                                                  : 'Pilih foto / screenshot bukti transfer'
                                        }}
                                    </span>
                                    <span class="mt-0.5 text-[11px] text-muted-foreground">
                                        Format JPG, PNG, atau WebP (maks. 5 MB)
                                    </span>
                                    <input
                                        id="payment_proof_input"
                                        ref="fileInputRef"
                                        type="file"
                                        accept="image/jpeg,image/png,image/jpg,image/webp"
                                        class="sr-only"
                                        @change="handleFileChange"
                                    />
                                </label>

                                <InputError :message="proofForm.errors.payment_proof" />

                                <Button
                                    type="submit"
                                    class="w-full gap-2 font-semibold"
                                    :disabled="!proofForm.payment_proof || proofForm.processing"
                                >
                                    <Loader2 v-if="proofForm.processing" class="h-4 w-4 animate-spin" />
                                    <Upload v-else class="h-4 w-4" />
                                    {{
                                        proofForm.processing
                                            ? 'Mengunggah...'
                                            : order.transaction?.payment_proof_url
                                              ? 'Perbarui Bukti Pembayaran'
                                              : 'Unggah Bukti Pembayaran'
                                    }}
                                </Button>
                            </form>

                            <div v-if="store.whatsapp" class="mt-4 border-t border-border pt-4">
                                <a
                                    :href="whatsappConfirmUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block"
                                >
                                    <Button
                                        type="button"
                                        class="w-full gap-2 bg-emerald-600 font-semibold text-white hover:bg-emerald-700"
                                    >
                                        <MessageCircle class="h-4 w-4" />
                                        Konfirmasi Sudah Bayar via WhatsApp
                                    </Button>
                                </a>
                                <p class="mt-1.5 text-center text-[11px] text-muted-foreground">
                                    Format detail pesanan &amp; total tagihan akan terisi otomatis.
                                </p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </StorefrontShell>
    </div>
</template>
