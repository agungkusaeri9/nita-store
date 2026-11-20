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
    Check,
    Copy,
    ExternalLink,
    Eye,
    EyeOff,
    Info,
    KeyRound,
    Loader2,
    MapPin,
    Save,
    SlidersHorizontal,
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
    { title: 'Geoapify', href: '/dashboard/configurations/geoapify' },
];

const showKey = ref(false);
const isCopied = ref(false);

const form = useForm({
    base_url: props.baseUrlConfig?.value ?? 'https://api.geoapify.com',
    api_key: props.config.value ?? '',
    description: props.config.description ?? 'API Key untuk layanan Geocoding dan Peta Geoapify',
});

const submit = () => {
    form.post('/dashboard/configurations/geoapify', {
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
        <Head title="Konfigurasi Geoapify" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <MapPin class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                            Konfigurasi Geoapify
                        </h1>
                        <p class="text-xs text-muted-foreground sm:text-sm">
                            Pengaturan API Key untuk layanan Geocoding, reverse geocoding, dan pemetaan alamat pelanggan.
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
                            Kredensial API Geoapify
                        </CardTitle>
                        <CardDescription>
                            API Key digunakan untuk melakukan pencarian koordinat (latitude & longitude), autocomplete alamat pengiriman, dan validasi lokasi pada sistem.
                        </CardDescription>
                    </CardHeader>

                    <form @submit.prevent="submit">
                        <CardContent class="space-y-4">
                            <!-- Key Identifier -->
                            <div class="space-y-1.5">
                                <Label class="text-xs text-muted-foreground">Parameter Keys</Label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <code class="rounded bg-muted px-2.5 py-1 font-mono text-xs font-semibold text-foreground">
                                        {{ baseUrlConfig?.key ?? 'geoapify_base_url' }}
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
                                    Base URL Geoapify
                                </Label>
                                <Input
                                    id="base_url"
                                    v-model="form.base_url"
                                    type="url"
                                    placeholder="https://api.geoapify.com"
                                    class="font-mono text-sm"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.base_url" class="text-xs text-destructive">
                                    {{ form.errors.base_url }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Default: <code>https://api.geoapify.com</code>. Digunakan untuk request geocoding, reverse geocoding, dan autocomplete.
                                </p>
                            </div>

                            <!-- API Key Input -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <Label for="api_key" class="text-sm font-medium">
                                        API Key Geoapify
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
                                        placeholder="Masukkan API Key Geoapify..."
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
                                    Dapatkan API Key dari dashboard <a href="https://myprojects.geoapify.com" target="_blank" class="underline hover:text-primary">geoapify.com</a>.
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
                                    placeholder="Keterangan mengenai konfigurasi Geoapify ini..."
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
                <Card class="border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base text-emerald-900 dark:text-emerald-300">
                            <Info class="h-4 w-4" />
                            Layanan Geoapify
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-xs text-emerald-950/80 dark:text-emerald-200/80 leading-relaxed">
                        <p>
                            Geoapify menyediakan API geospasial untuk pencarian lokasi yang akurat, pemetaan titik alamat pelanggan, dan integrasi peta interaktif.
                        </p>
                        <div class="rounded-md border border-emerald-200 bg-white/70 p-3 dark:border-emerald-900/60 dark:bg-emerald-950/50">
                            <span class="font-semibold block mb-1">Fitur Utama:</span>
                            <ul class="list-disc list-inside space-y-0.5 text-muted-foreground">
                                <li>Geocoding & Reverse Geocoding</li>
                                <li>Address Autocomplete untuk formulir checkout</li>
                                <li>Kalkulasi jarak rute & estimasi lokasi</li>
                                <li>Peta berbasis vector / raster tiles</li>
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
                            href="/dashboard/locations"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <MapPin class="h-4 w-4 text-emerald-600" />
                                Data Master Lokasi Wilayah
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

