<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Eye,
    FileText,
    HelpCircle,
    Info,
    Loader2,
    RotateCcw,
    Save,
    Scale,
    Shield,
    Sparkles,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface StoreSettingLegal {
    id?: number;
    privacy_policy?: string | null;
    policy?: string | null;
    updated_at?: string | null;
}

interface Props {
    setting: StoreSettingLegal;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengaturan', href: '#' },
    { title: 'Kebijakan & Privasi', href: '/dashboard/privacy-policy-settings' },
];

const form = useForm({
    privacy_policy: props.setting.privacy_policy || '',
    policy: props.setting.policy || '',
});

const activeMode = ref<'edit' | 'preview'>('edit');

const privacyCharCount = computed(() => form.privacy_policy.length);
const privacyWordCount = computed(() => {
    const text = form.privacy_policy.trim();
    return text ? text.split(/\s+/).length : 0;
});

const policyCharCount = computed(() => form.policy.length);
const policyWordCount = computed(() => {
    const text = form.policy.trim();
    return text ? text.split(/\s+/).length : 0;
});

const isDirty = computed(() => {
    return (
        form.privacy_policy !== (props.setting.privacy_policy || '') ||
        form.policy !== (props.setting.policy || '')
    );
});

const resetForm = () => {
    form.privacy_policy = props.setting.privacy_policy || '';
    form.policy = props.setting.policy || '';
    form.clearErrors();
};

const insertPrivacyTemplate = () => {
    if (form.privacy_policy && !confirm('Ganti konten Kebijakan Privasi saat ini dengan template standar?')) {
        return;
    }
    form.privacy_policy = `KEBIJAKAN PRIVASI (PRIVACY POLICY)
Toko Batik Nusantara

1. PENGUMPULAN INFORMASI
Kami mengumpulkan informasi dari Anda saat Anda mendaftar di situs kami, melakukan pemesanan, atau berlangganan newsletter kami. Informasi yang dikumpulkan meliputi nama lengkap, alamat email, alamat pengiriman, nomor telepon/WhatsApp, dan informasi transaksi pembelian produk batik.

2. PENGGUNAAN INFORMASI
Informasi yang kami kumpulkan dapat digunakan untuk:
- Memproses dan mengirimkan pesanan batik ke alamat Anda.
- Menghitung tarif ongkos kirim dan integrasi pelacakan kurir.
- Menghubungi Anda terkait pembaruan status pesanan atau konfirmasi pembayaran.
- Meningkatkan layanan pelanggan dan pengalaman berbelanja.
- Mengirimkan promosi produk, diskon eksklusif, dan katalog motif batik terbaru (jika disetujui).

3. PERLINDUNGAN INFORMASI
Kami menerapkan berbagai langkah keamanan untuk menjaga keamanan informasi pribadi Anda. Kata sandi akun Anda dienkripsi dengan standar industri dan data pembayaran diproses melalui gateway pembayaran resmi yang aman.

4. PENGUNGKAPAN KEPADA PIHAK KETIGA
Kami tidak menjual, memperdagangkan, atau mentransfer informasi pribadi Anda kepada pihak luar, kecuali kepada mitra kurir logistik dan gateway pembayaran terpercaya yang membantu operasional toko kami.

5. PERSETUJUAN
Dengan menggunakan website dan layanan toko kami, Anda menyetujui kebijakan privasi ini.

Terakhir diperbarui: ${new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}`;
};

const insertPolicyTemplate = () => {
    if (form.policy && !confirm('Ganti konten Syarat & Kebijakan Layanan saat ini dengan template standar?')) {
        return;
    }
    form.policy = `SYARAT, KETENTUAN & KEBIJAKAN TOKO (TERMS & POLICY)
Toko Batik Nusantara

1. KETENTUAN UMUM
- Pelanggan wajib memberikan data nama, nomor kontak, dan alamat pengiriman yang lengkap dan valid.
- Semua pesanan yang telah dikonfirmasi dan dibayar akan diproses dalam waktu 1-2 hari kerja.

2. KEBIJAKAN PRODUK & WARNA
- Produk batik kami merupakan karya autentik (batik tulis, cap, dan printing berkualitas).
- Terdapat kemungkinan sedikit perbedaan gradasi warna pada foto produk akibat efek pencahayaan studio atau kalibrasi layar monitor perangkat Anda.

3. PEMBAYARAN & PEMBATALAN
- Pembayaran dapat dilakukan melalui metode transfer bank, virtual account, e-wallet, atau QRIS yang tersedia.
- Batas waktu pembayaran adalah 1x24 jam setelah checkout. Pesanan yang belum dibayar melewati batas waktu akan dibatalkan secara otomatis oleh sistem.

4. PENGIRIMAN & EKSPEDISI
- Estimasi waktu pengiriman tergantung pada lokasi tujuan dan kurir pengiriman yang dipilih.
- Nomor resi pengiriman akan diperbarui otomatis di sistem akun pelanggan setelah paket diserahkan ke pihak ekspedisi.

5. KEBIJAKAN PENGEMBALIAN & PENUKARAN (RETURN & REFUND)
- Pengembalian barang hanya diterima jika terjadi kesalahan kirim motif/ukuran atau produk cacat produksi mayor.
- Pembeli wajib menyertakan video unboxing utuh tanpa jeda saat membuka paket.
- Konfirmasi komplain maksimal 2x24 jam sejak paket dinyatakan diterima oleh kurir.

Terakhir diperbarui: ${new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}`;
};

const submitForm = () => {
    form.post('/dashboard/privacy-policy-settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Pengaturan Kebijakan & Privasi" />

        <!-- Breadcrumbs -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Scale class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                            Kebijakan & Privasi
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Kelola teks Kebijakan Privasi (Privacy Policy) dan Syarat Ketentuan / Kebijakan Toko (Policy).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg border bg-card p-1 shadow-xs">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="activeMode === 'edit' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                        @click="activeMode = 'edit'"
                    >
                        <FileText class="h-3.5 w-3.5" />
                        Editor
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="activeMode === 'preview' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                        @click="activeMode = 'preview'"
                    >
                        <Eye class="h-3.5 w-3.5" />
                        Pratinjau
                    </button>
                </div>

                <Badge v-if="setting.updated_at" variant="outline" class="hidden sm:inline-flex gap-1 py-1 text-xs text-muted-foreground">
                    <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500" />
                    Diperbarui: {{ setting.updated_at }}
                </Badge>
            </div>
        </div>

        <!-- Info Card Banner -->
        <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50/50 p-4 text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/20 dark:text-blue-200">
            <Info class="h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400 mt-0.5" />
            <div class="space-y-1 text-xs sm:text-sm">
                <p class="font-semibold text-blue-950 dark:text-blue-100">
                    Dua Kolom Pengaturan Legalitas Toko
                </p>
                <p class="text-blue-800/90 dark:text-blue-300">
                    Gunakan kolom <strong>Privacy (Kebijakan Privasi)</strong> untuk aturan perlindungan data pembeli, dan kolom <strong>Policy (Syarat & Kebijakan)</strong> untuk aturan pembelian, pengembalian batik, serta pengiriman.
                </p>
            </div>
        </div>

        <!-- Main Form with Two Columns -->
        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Edit Mode: Two Column Textareas -->
            <div v-show="activeMode === 'edit'" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- COLUMN 1: Privacy Policy -->
                <Card class="flex flex-col shadow-xs">
                    <CardHeader class="border-b pb-4 bg-muted/20">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                    <Shield class="h-4 w-4" />
                                </div>
                                <div>
                                    <CardTitle class="text-base font-semibold">Kebijakan Privasi (Privacy Policy)</CardTitle>
                                    <CardDescription class="text-xs">
                                        Perlindungan data pribadi, cookie, dan informasi pembeli
                                    </CardDescription>
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-8 gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                                @click="insertPrivacyTemplate"
                                title="Isi dengan template standar privasi"
                            >
                                <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                                Template
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent class="flex-1 space-y-3 p-4 sm:p-6">
                        <div class="flex items-center justify-between">
                            <Label for="privacy_policy" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Konten Privacy
                            </Label>
                            <span class="text-[11px] text-muted-foreground">
                                {{ privacyWordCount }} kata &bull; {{ privacyCharCount }} karakter
                            </span>
                        </div>

                        <Textarea
                            id="privacy_policy"
                            v-model="form.privacy_policy"
                            placeholder="Tuliskan kebijakan privasi toko batik Anda di sini..."
                            class="min-h-[440px] font-sans text-sm leading-relaxed resize-y bg-background"
                            :class="{ 'border-destructive': form.errors.privacy_policy }"
                        />
                        <p v-if="form.errors.privacy_policy" class="text-xs text-destructive">
                            {{ form.errors.privacy_policy }}
                        </p>
                    </CardContent>
                </Card>

                <!-- COLUMN 2: Terms & Policy -->
                <Card class="flex flex-col shadow-xs">
                    <CardHeader class="border-b pb-4 bg-muted/20">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    <FileText class="h-4 w-4" />
                                </div>
                                <div>
                                    <CardTitle class="text-base font-semibold">Syarat & Kebijakan Toko (Policy)</CardTitle>
                                    <CardDescription class="text-xs">
                                        Ketentuan transaksi, garansi produk batik, dan retur
                                    </CardDescription>
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-8 gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                                @click="insertPolicyTemplate"
                                title="Isi dengan template standar syarat & kebijakan"
                            >
                                <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                                Template
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent class="flex-1 space-y-3 p-4 sm:p-6">
                        <div class="flex items-center justify-between">
                            <Label for="policy" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Konten Policy
                            </Label>
                            <span class="text-[11px] text-muted-foreground">
                                {{ policyWordCount }} kata &bull; {{ policyCharCount }} karakter
                            </span>
                        </div>

                        <Textarea
                            id="policy"
                            v-model="form.policy"
                            placeholder="Tuliskan syarat, ketentuan dan kebijakan operasional toko di sini..."
                            class="min-h-[440px] font-sans text-sm leading-relaxed resize-y bg-background"
                            :class="{ 'border-destructive': form.errors.policy }"
                        />
                        <p v-if="form.errors.policy" class="text-xs text-destructive">
                            {{ form.errors.policy }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Preview Mode: Two Column Formatted Reading View -->
            <div v-show="activeMode === 'preview'" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Preview Privacy -->
                <Card class="shadow-xs">
                    <CardHeader class="border-b bg-muted/20">
                        <div class="flex items-center gap-2">
                            <Shield class="h-4 w-4 text-blue-600" />
                            <CardTitle class="text-base font-semibold">Pratinjau: Kebijakan Privasi</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="p-6">
                        <div v-if="form.privacy_policy.trim()" class="whitespace-pre-line text-sm leading-relaxed text-foreground bg-muted/10 p-4 rounded-lg border">
                            {{ form.privacy_policy }}
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground">
                            <HelpCircle class="h-10 w-10 stroke-1 mb-2 opacity-50" />
                            <p class="text-sm font-medium">Belum ada konten Kebijakan Privasi</p>
                            <p class="text-xs mt-1">Kembali ke tab Editor untuk menambahkan isi teks.</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Preview Policy -->
                <Card class="shadow-xs">
                    <CardHeader class="border-b bg-muted/20">
                        <div class="flex items-center gap-2">
                            <FileText class="h-4 w-4 text-emerald-600" />
                            <CardTitle class="text-base font-semibold">Pratinjau: Syarat & Kebijakan</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="p-6">
                        <div v-if="form.policy.trim()" class="whitespace-pre-line text-sm leading-relaxed text-foreground bg-muted/10 p-4 rounded-lg border">
                            {{ form.policy }}
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground">
                            <HelpCircle class="h-10 w-10 stroke-1 mb-2 opacity-50" />
                            <p class="text-sm font-medium">Belum ada konten Syarat & Kebijakan</p>
                            <p class="text-xs mt-1">Kembali ke tab Editor untuk menambahkan isi teks.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Footer Actions Bar -->
            <div class="sticky bottom-4 z-10 flex items-center justify-between rounded-xl border bg-card/95 backdrop-blur-md p-4 shadow-lg">
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <span v-if="isDirty" class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-medium">
                        <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Ada perubahan yang belum disimpan
                    </span>
                    <span v-else class="inline-flex items-center gap-1.5 text-muted-foreground">
                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500" />
                        Semua data telah tersimpan
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        @click="resetForm"
                        :disabled="form.processing || !isDirty"
                        class="gap-1.5"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Reset
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="gap-2 min-w-[140px]"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        Simpan Kebijakan
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
