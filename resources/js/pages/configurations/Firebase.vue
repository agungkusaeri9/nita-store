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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    ExternalLink,
    Info,
    KeyRound,
    Loader2,
    Save,
    SlidersHorizontal,
    BellRing,
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
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Konfigurasi', href: '#' },
    { title: 'Firebase (FCM)', href: '/dashboard/configurations/firebase' },
];

const isCopied = ref(false);

const form = useForm({
    service_account_json: props.config.value ?? '',
    description: props.config.description ?? 'Konfigurasi kredensial Service Account JSON untuk Push Notification FCM',
});

const submit = () => {
    form.post('/dashboard/configurations/firebase', {
        preserveScroll: true,
    });
};

const copyJson = async (text: string) => {
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
        <Head title="Konfigurasi Firebase (FCM)" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                        <BellRing class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                            Konfigurasi Firebase (FCM)
                        </h1>
                        <p class="text-xs text-muted-foreground sm:text-sm">
                            Pengaturan Service Account JSON untuk integrasi Push Notification Firebase Cloud Messaging.
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
                            Firebase Service Account JSON
                        </CardTitle>
                        <CardDescription>
                            Tempelkan isi file JSON kredensial Service Account dari Firebase Console untuk mengaktifkan pengiriman push notification server-side.
                        </CardDescription>
                    </CardHeader>

                    <form @submit.prevent="submit">
                        <CardContent class="space-y-4">
                            <!-- Key Identifier -->
                            <div class="space-y-1.5">
                                <Label class="text-xs text-muted-foreground">Parameter Key</Label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <code class="rounded bg-muted px-2.5 py-1 font-mono text-xs font-semibold text-foreground">
                                        {{ config.key }}
                                    </code>
                                    <span class="text-xs text-muted-foreground">(Disimpan dalam tabel app_configs & di-cache)</span>
                                </div>
                            </div>

                            <!-- JSON Input -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <Label for="service_account_json" class="text-sm font-medium">
                                        JSON Credentials
                                    </Label>
                                    <button
                                        v-if="form.service_account_json"
                                        type="button"
                                        @click="copyJson(form.service_account_json)"
                                        class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
                                    >
                                        <Check v-if="isCopied" class="h-3 w-3 text-green-600" />
                                        <Copy v-else class="h-3 w-3" />
                                        <span>{{ isCopied ? 'Tersalin' : 'Salin JSON' }}</span>
                                    </button>
                                </div>

                                <Textarea
                                    id="service_account_json"
                                    v-model="form.service_account_json"
                                    rows="12"
                                    placeholder='{"type": "service_account", "project_id": "...", ...}'
                                    class="font-mono text-xs"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.service_account_json" class="text-xs text-destructive">
                                    {{ form.errors.service_account_json }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Dapatkan file JSON ini dari Firebase Console > Project Settings > Service Accounts > Generate new private key.
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
                                    placeholder="Keterangan mengenai konfigurasi Firebase ini..."
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
                <Card class="border-amber-200 bg-amber-50/50 dark:border-amber-900/40 dark:bg-amber-950/20">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base text-amber-900 dark:text-amber-300">
                            <Info class="h-4 w-4" />
                            Tentang FCM v1 API
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-xs text-amber-950/80 dark:text-amber-200/80 leading-relaxed">
                        <p>
                            Firebase Cloud Messaging (FCM) HTTP v1 menggunakan token autentikasi OAuth2 dari Google Service Account untuk mengirim push notification secara aman dan cepat.
                        </p>
                        <div class="rounded-md border border-amber-200 bg-white/70 p-3 dark:border-amber-900/60 dark:bg-amber-950/50">
                            <span class="font-semibold block mb-1">Panduan Singkat:</span>
                            <ul class="list-disc list-inside space-y-0.5 text-muted-foreground">
                                <li>Buka Firebase Console</li>
                                <li>Masuk ke Project Settings > Service Accounts</li>
                                <li>Klik Generate New Private Key</li>
                                <li>Salin isi JSON & tempelkan di form sebelah</li>
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
                            href="/dashboard/notifications"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <BellRing class="h-4 w-4 text-amber-600" />
                                Daftar Notifikasi Sistem
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
