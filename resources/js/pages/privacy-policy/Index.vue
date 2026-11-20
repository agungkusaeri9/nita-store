<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

interface StoreSettingLegal {
    id?: number;
    privacy_policy?: string | null;
    policy?: string | null;
    cancellation_policy?: string | null;
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
    privacy_policy: props.setting?.privacy_policy || '',
    policy: props.setting?.policy || '',
    cancellation_policy: props.setting?.cancellation_policy || '',
});

const submitForm = () => {
    form.post('/dashboard/privacy-policy-settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Kebijakan & Privasi" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                Kebijakan & Privasi
            </h1>
            <p class="text-sm text-muted-foreground">
                Atur isi teks kebijakan privasi, syarat ketentuan toko, dan kebijakan pembatalan pesanan.
            </p>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Kolom 1: Cancellation Policy -->
                <div class="rounded-xl border bg-card p-5 space-y-3">
                    <div class="space-y-1">
                        <Label for="cancellation_policy" class="text-base font-semibold text-foreground">
                            Kebijakan Pembatalan (Cancellation Policy)
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            Ketentuan pembatalan pesanan sebelum diproses/dikirim.
                        </p>
                    </div>

                    <Textarea
                        id="cancellation_policy"
                        v-model="form.cancellation_policy"
                        placeholder="Tuliskan ketentuan pembatalan pesanan di sini..."
                        class="min-h-[380px] bg-background text-sm"
                        :class="{ 'border-destructive': form.errors.cancellation_policy }"
                    />
                    <p v-if="form.errors.cancellation_policy" class="text-xs text-destructive">
                        {{ form.errors.cancellation_policy }}
                    </p>
                </div>

                <!-- Kolom 2: Terms & Conditions / Policy -->
                <div class="rounded-xl border bg-card p-5 space-y-3">
                    <div class="space-y-1">
                        <Label for="policy" class="text-base font-semibold text-foreground">
                            Syarat & Ketentuan Toko (Terms & Policy)
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            Ketentuan transaksi, pembelian, dan kebijakan retur.
                        </p>
                    </div>

                    <Textarea
                        id="policy"
                        v-model="form.policy"
                        placeholder="Tuliskan syarat dan kebijakan toko di sini..."
                        class="min-h-[380px] bg-background text-sm"
                        :class="{ 'border-destructive': form.errors.policy }"
                    />
                    <p v-if="form.errors.policy" class="text-xs text-destructive">
                        {{ form.errors.policy }}
                    </p>
                </div>

                <!-- Kolom 3: Privacy Policy -->
                <div class="rounded-xl border bg-card p-5 space-y-3">
                    <div class="space-y-1">
                        <Label for="privacy_policy" class="text-base font-semibold text-foreground">
                            Kebijakan Privasi (Privacy Policy)
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            Ketentuan perlindungan data dan privasi pengguna.
                        </p>
                    </div>

                    <Textarea
                        id="privacy_policy"
                        v-model="form.privacy_policy"
                        placeholder="Tuliskan kebijakan privasi di sini..."
                        class="min-h-[380px] bg-background text-sm"
                        :class="{ 'border-destructive': form.errors.privacy_policy }"
                    />
                    <p v-if="form.errors.privacy_policy" class="text-xs text-destructive">
                        {{ form.errors.privacy_policy }}
                    </p>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="flex items-center justify-end gap-3 rounded-xl border bg-card p-4">
                <Button
                    type="button"
                    variant="outline"
                    @click="() => form.reset()"
                    :disabled="form.processing"
                >
                    Reset
                </Button>
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    <Loader2 v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                    Simpan Perubahan
                </Button>
            </div>
        </form>
    </div>
</template>
