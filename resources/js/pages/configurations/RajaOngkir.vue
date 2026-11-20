<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    Copy,
    ExternalLink,
    Eye,
    EyeOff,
    Info,
    KeyRound,
    Loader2,
    Save,
    SlidersHorizontal,
    Truck,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface AppConfigItem {
    id: number;
    key: string;
    value: string | null;
    description: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    config: AppConfigItem;
    baseUrlConfig?: AppConfigItem;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Konfigurasi', href: '#' },
    { title: 'RajaOngkir', href: '/dashboard/configurations/rajaongkir' },
];

const showKey = ref(false);
const isCopied = ref(false);

const form = useForm({
    base_url: props.baseUrlConfig?.value ?? 'https://rajaongkir.komerce.id',
    api_key: props.config.value ?? '',
    description: props.config.description ?? 'API Key untuk layanan integrasi RajaOngkir / Komerce',
});

const submit = () => {
    form.post('/dashboard/configurations/rajaongkir', {
        preserveScroll: true,
    });
};

const copyKey = async (text: string) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    } catch {
        // fallback
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Konfigurasi RajaOngkir" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                        <Truck class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                            Konfigurasi RajaOngkir
                        </h1>
                        <p class="text-xs text-muted-foreground sm:text-sm">
                            Pengaturan API Key untuk layanan pengecekan ongkos kirim ekspedisi.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Badge
                    v-if="config.value"
                    variant="outline"
                    class="gap-1.5 border-emerald-500/40 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 py-1 px-3 text-xs"
                >
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Terkonfigurasi
                </Badge>
                <Badge
                    v-else
                    variant="outline"
                    class="gap-1.5 border-amber-500/40 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 py-1 px-3 text-xs"
                >
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Belum Dikonfigurasi
                </Badge>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Form Card (2 columns) -->
            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <KeyRound class="h-5 w-5 text-primary" />
                            Kredensial API RajaOngkir
                        </CardTitle>
                        <CardDescription>
                            Kunci API ini akan digunakan oleh sistem untuk menghitung ongkos kirim real-time saat pelanggan melakukan checkout.
                        </CardDescription>
                    </CardHeader>

                    <form @submit.prevent="submit">
                        <CardContent class="space-y-4">
                            <!-- Key Identifier -->
                            <div class="space-y-1.5">
                                <Label class="text-xs text-muted-foreground">Parameter Keys</Label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <code class="rounded bg-muted px-2.5 py-1 font-mono text-xs font-semibold text-foreground">
                                        {{ baseUrlConfig?.key ?? 'rajaongkir_base_url' }}
                                    </code>
                                    <code class="rounded bg-muted px-2.5 py-1 font-mono text-xs font-semibold text-foreground">
                                        {{ config.key }}
                                    </code>
                                    <span class="text-xs text-muted-foreground">(Disimpan dalam tabel app_configs & di-cache)</span>
                                </div>
                            </div>

                            <!-- Base URL Input -->
                            <div class="space-y-1.5">
                                <Label for="base_url" class="text-sm font-medium">
                                    Base URL RajaOngkir
                                </Label>
                                <Input
                                    id="base_url"
                                    v-model="form.base_url"
                                    type="url"
                                    placeholder="https://rajaongkir.komerce.id"
                                    class="font-mono text-sm"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.base_url" class="text-xs text-destructive">
                                    {{ form.errors.base_url }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Default: <code>https://rajaongkir.komerce.id</code>. Digunakan untuk pemanggilan endpoint kalkulasi tarif ongkos kirim.
                                </p>
                            </div>

                            <!-- API Key Input -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <Label for="api_key" class="text-sm font-medium">
                                        API Key RajaOngkir / Komerce
                                    </Label>
                                    <button
                                        v-if="form.api_key"
                                        type="button"
                                        @click="copyKey(form.api_key)"
                                        class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
                                    >
                                        <Check v-if="isCopied" class="h-3 w-3 text-green-600" />
                                        <Copy v-else class="h-3 w-3" />
                                        <span>{{ isCopied ? 'Tersalin' : 'Salin Key' }}</span>
                                    </button>
                                </div>

                                <div class="relative">
                                    <Input
                                        id="api_key"
                                        v-model="form.api_key"
                                        :type="showKey ? 'text' : 'password'"
                                        placeholder="Masukkan API key RajaOngkir Anda..."
                                        class="pr-10 font-mono text-sm"
                                        :disabled="form.processing"
                                    />
                                    <button
                                        type="button"
                                        @click="showKey = !showKey"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                        :title="showKey ? 'Sembunyikan' : 'Tampilkan'"
                                    >
                                        <EyeOff v-if="showKey" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="form.errors.api_key" class="text-xs text-destructive">
                                    {{ form.errors.api_key }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Dapatkan API Key dari dashboard Komerce / RajaOngkir Anda. Biarkan kosong jika ingin menonaktifkan sementara.
                                </p>
                            </div>

                            <!-- Description Input -->
                            <div class="space-y-1.5">
                                <Label for="description" class="text-sm font-medium">
                                    Catatan / Deskripsi
                                </Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Keterangan mengenai API key ini..."
                                    :disabled="form.processing"
                                    class="text-xs"
                                />
                                <p v-if="form.errors.description" class="text-xs text-destructive">
                                    {{ form.errors.description }}
                                </p>
                            </div>
                        </CardContent>

                        <CardFooter class="flex items-center justify-between border-t pt-4">
                            <span class="text-xs text-muted-foreground">
                                Terakhir diperbarui: {{ new Date(config.updated_at).toLocaleString('id-ID') }}
                            </span>
                            <Button type="submit" :disabled="form.processing" class="gap-1.5">
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                <Save v-else class="h-4 w-4" />
                                Simpan Konfigurasi
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>

            <!-- Side Info Card (1 column) -->
            <div class="space-y-6">
                <!-- Info Guide -->
                <Card class="border-blue-200 bg-blue-50/50 dark:border-blue-900/40 dark:bg-blue-950/20">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base text-blue-900 dark:text-blue-300">
                            <Info class="h-4 w-4" />
                            Tentang Layanan RajaOngkir
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-xs text-blue-950/80 dark:text-blue-200/80 leading-relaxed">
                        <p>
                            Aplikasi menggunakan integrasi RajaOngkir (Komerce) untuk menghitung tarif pengiriman kurir domestik secara otomatis berdasarkan berat dan lokasi tujuan.
                        </p>
                        <div class="rounded-md border border-blue-200 bg-white/70 p-3 dark:border-blue-900/60 dark:bg-blue-950/50">
                            <span class="font-semibold block mb-1">Kurir yang didukung:</span>
                            <ul class="list-disc list-inside space-y-0.5 text-muted-foreground">
                                <li>JNE (Reguler, OKE, YES)</li>
                                <li>SiCepat (REG, BEST, Cargo)</li>
                                <li>J&T Express</li>
                                <li>POS Indonesia</li>
                                <li>TIKI</li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>

                <!-- Navigation Quick Links -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold">Tautan Terkait</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-xs">
                        <Link
                            href="/dashboard/couriers"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <Truck class="h-4 w-4 text-blue-600" />
                                Kelola Daftar Kurir
                            </span>
                            <ExternalLink class="h-3.5 w-3.5 text-muted-foreground" />
                        </Link>
                        <Link
                            href="/dashboard/configurations"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <SlidersHorizontal class="h-4 w-4 text-primary" />
                                Semua Konfigurasi Aplikasi
                            </span>
                            <ExternalLink class="h-3.5 w-3.5 text-muted-foreground" />
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

