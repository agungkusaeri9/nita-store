<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Camera, CheckCircle2, Loader2, Phone, User } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import { useInitials } from '@/composables/useInitials';
import type { StoreIdentity } from '@/types/storefront';

interface Props {
    store: StoreIdentity;
    profile: {
        name: string;
        email: string | null;
        phone: string;
        gender: string;
        avatar_url: string | null;
    };
}

const props = defineProps<Props>();
const { getInitials } = useInitials();

const avatarPreview = ref<string | null>(props.profile.avatar_url);
const avatarInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    name: props.profile.name,
    gender: props.profile.gender || 'male',
    phone: props.profile.phone ?? '',
    avatar: null as File | null,
});

const handleAvatarChange = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) {
        return;
    }

    form.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
};

const submit = (): void => {
    form.post('/complete-profile', {
        forceFormData: true,
    });
};
</script>

<template>
    <div>
        <Head :title="`Lengkapi Profil - ${store.name}`" />

        <StorefrontShell :store="store">
            <div class="mx-auto max-w-xl px-4 py-10 sm:px-6">
                <div class="overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-sm sm:p-8">
                    <div class="mb-6 space-y-1.5 text-center">
                        <div class="mx-auto mb-2 inline-flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <CheckCircle2 class="h-6 w-6" />
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">
                            Lengkapi Profil Anda
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Tambahkan informasi akun Anda sebelum mulai berbelanja di {{ store.name }}.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Upload Avatar -->
                        <div class="flex flex-col items-center gap-3 pb-2">
                            <div class="relative">
                                <Avatar class="h-24 w-24 border-2 border-primary/20 shadow-xs">
                                    <AvatarImage
                                        v-if="avatarPreview"
                                        :src="avatarPreview"
                                        :alt="form.name"
                                        class="object-cover"
                                    />
                                    <AvatarFallback class="bg-primary/10 text-lg font-bold text-primary">
                                        {{ getInitials(form.name || 'User') }}
                                    </AvatarFallback>
                                </Avatar>

                                <button
                                    type="button"
                                    @click="avatarInput?.click()"
                                    class="absolute right-0 bottom-0 flex h-8 w-8 items-center justify-center rounded-full border-2 border-background bg-primary text-primary-foreground shadow-sm transition hover:bg-primary/90"
                                    aria-label="Pilih foto profil"
                                >
                                    <Camera class="h-4 w-4" />
                                </button>
                                <input
                                    ref="avatarInput"
                                    type="file"
                                    accept="image/*"
                                    class="sr-only"
                                    @change="handleAvatarChange"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Klik ikon kamera untuk mengunggah foto profil (maks. 2MB)
                            </p>
                            <InputError :message="form.errors.avatar" />
                        </div>

                        <!-- Nama Lengkap -->
                        <div class="space-y-1.5">
                            <Label for="name">Nama Lengkap</Label>
                            <div class="relative">
                                <User class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Masukkan nama lengkap"
                                    class="pl-9.5"
                                />
                            </div>
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Jenis Kelamin (Gender) -->
                        <div class="space-y-2">
                            <Label>Jenis Kelamin</Label>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium transition"
                                    :class="
                                        form.gender === 'male'
                                            ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary/30'
                                            : 'border-border bg-background text-foreground hover:border-primary/50'
                                    "
                                >
                                    <input
                                        v-model="form.gender"
                                        type="radio"
                                        name="gender"
                                        value="male"
                                        class="sr-only"
                                    />
                                    <span>Laki-laki</span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium transition"
                                    :class="
                                        form.gender === 'female'
                                            ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary/30'
                                            : 'border-border bg-background text-foreground hover:border-primary/50'
                                    "
                                >
                                    <input
                                        v-model="form.gender"
                                        type="radio"
                                        name="gender"
                                        value="female"
                                        class="sr-only"
                                    />
                                    <span>Perempuan</span>
                                </label>
                            </div>
                            <InputError :message="form.errors.gender" />
                        </div>

                        <!-- Nomor Telepon / WhatsApp -->
                        <div class="space-y-1.5">
                            <Label for="phone">Nomor Telepon / WhatsApp</Label>
                            <div class="relative">
                                <Phone class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="text"
                                    required
                                    placeholder="08xxxxxxxxxx"
                                    class="pl-9.5"
                                />
                            </div>
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="space-y-3 pt-2">
                            <Button type="submit" size="lg" class="w-full gap-2 font-semibold" :disabled="form.processing">
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan & Selesai' }}
                            </Button>

                            <div class="text-center">
                                <Link href="/" class="text-xs font-medium text-muted-foreground hover:text-primary">
                                    Lewati untuk sekarang
                                </Link>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </StorefrontShell>
    </div>
</template>
