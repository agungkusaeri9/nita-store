<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    ChevronRight,
    KeyRound,
    Loader2,
    LogOut,
    ShoppingBag,
    User as UserIcon,
} from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import StorefrontShell from '@/components/user/StorefrontShell.vue';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import type { StoreIdentity } from '@/types/storefront';

interface Props {
    store: StoreIdentity;
    profile: {
        name: string;
        username: string;
        email: string | null;
        phone: string;
        gender: string;
        avatar_url: string | null;
    };
}

const props = defineProps<Props>();
const { getInitials } = useInitials();

const profileForm = useForm({
    name: props.profile.name,
    username: props.profile.username,
    email: props.profile.email ?? '',
    phone: props.profile.phone ?? '',
    gender: props.profile.gender ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitProfile = (): void => {
    profileForm.patch('/profile', {
        preserveScroll: true,
    });
};

const submitPassword = (): void => {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

const handleLogout = (): void => {
    router.flushAll();
};
</script>

<template>
    <div>
        <Head :title="`Profil Saya - ${store.name}`" />

        <StorefrontShell :store="store">
            <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
                <nav class="mb-6 flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Link href="/" class="hover:text-primary">Beranda</Link>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <span class="font-medium text-foreground">Profil Saya</span>
                </nav>

                <div class="grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)] lg:items-start">
                    <!-- Sidebar Akun User -->
                    <aside class="space-y-4 rounded-2xl border border-border bg-card p-5 shadow-xs">
                        <div class="flex items-center gap-3 border-b border-border pb-4">
                            <Avatar class="h-12 w-12 border border-border">
                                <AvatarImage
                                    v-if="profile.avatar_url"
                                    :src="profile.avatar_url"
                                    :alt="profile.name"
                                />
                                <AvatarFallback class="bg-primary/10 text-sm font-semibold text-primary">
                                    {{ getInitials(profile.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-foreground">{{ profile.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">@{{ profile.username }}</p>
                            </div>
                        </div>

                        <nav class="space-y-1 text-sm">
                            <Link
                                href="/profile"
                                class="flex items-center gap-2.5 rounded-lg bg-primary/10 px-3 py-2 font-semibold text-primary"
                            >
                                <UserIcon class="h-4 w-4" />
                                Profil Saya
                            </Link>
                            <Link
                                href="/my-orders"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-foreground transition hover:bg-muted"
                            >
                                <ShoppingBag class="h-4 w-4" />
                                Pesanan Saya
                            </Link>
                            <a
                                href="#keamanan"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-foreground transition hover:bg-muted"
                            >
                                <KeyRound class="h-4 w-4" />
                                Ubah Kata Sandi
                            </a>
                            <Link
                                :href="logout()"
                                method="post"
                                as="button"
                                @click="handleLogout"
                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-destructive transition hover:bg-destructive/10"
                            >
                                <LogOut class="h-4 w-4" />
                                Keluar
                            </Link>
                        </nav>
                    </aside>

                    <!-- Form Profil & Kata Sandi -->
                    <div class="min-w-0 space-y-6">
                        <!-- Informasi Profil -->
                        <section class="rounded-2xl border border-border bg-card p-6 shadow-xs">
                            <div class="mb-5 border-b border-border pb-4">
                                <h1 class="text-lg font-bold text-foreground">Informasi Profil</h1>
                                <p class="text-xs text-muted-foreground sm:text-sm">
                                    Perbarui data akun dan kontak Anda.
                                </p>
                            </div>

                            <form @submit.prevent="submitProfile" class="space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <Label for="name">Nama Lengkap</Label>
                                        <Input id="name" v-model="profileForm.name" type="text" required />
                                        <InputError :message="profileForm.errors.name" />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label for="username">Username</Label>
                                        <Input id="username" v-model="profileForm.username" type="text" required />
                                        <InputError :message="profileForm.errors.username" />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label for="email">Email</Label>
                                        <Input id="email" v-model="profileForm.email" type="email" />
                                        <InputError :message="profileForm.errors.email" />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label for="phone">Nomor Telepon / WhatsApp</Label>
                                        <Input id="phone" v-model="profileForm.phone" type="text" placeholder="08xxxxxxxxxx" />
                                        <InputError :message="profileForm.errors.phone" />
                                    </div>

                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label for="gender">Jenis Kelamin</Label>
                                        <Select v-model="profileForm.gender">
                                            <SelectTrigger id="gender" class="w-full sm:w-64">
                                                <SelectValue placeholder="Pilih jenis kelamin" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="male">Laki-laki</SelectItem>
                                                <SelectItem value="female">Perempuan</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="profileForm.errors.gender" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 pt-2">
                                    <Button type="submit" :disabled="profileForm.processing" class="gap-2">
                                        <Loader2 v-if="profileForm.processing" class="h-4 w-4 animate-spin" />
                                        Simpan Perubahan
                                    </Button>
                                    <span
                                        v-if="profileForm.recentlySuccessful"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600"
                                    >
                                        <Check class="h-3.5 w-3.5" />
                                        Tersimpan
                                    </span>
                                </div>
                            </form>
                        </section>

                        <!-- Keamanan & Ubah Kata Sandi -->
                        <section id="keamanan" class="rounded-2xl border border-border bg-card p-6 shadow-xs">
                            <div class="mb-5 border-b border-border pb-4">
                                <h2 class="text-lg font-bold text-foreground">Ubah Kata Sandi</h2>
                                <p class="text-xs text-muted-foreground sm:text-sm">
                                    Gunakan kata sandi yang kuat untuk menjaga keamanan akun Anda.
                                </p>
                            </div>

                            <form @submit.prevent="submitPassword" class="space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label for="current_password">Kata Sandi Saat Ini</Label>
                                        <Input
                                            id="current_password"
                                            v-model="passwordForm.current_password"
                                            type="password"
                                            required
                                        />
                                        <InputError :message="passwordForm.errors.current_password" />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label for="password">Kata Sandi Baru</Label>
                                        <Input
                                            id="password"
                                            v-model="passwordForm.password"
                                            type="password"
                                            required
                                        />
                                        <InputError :message="passwordForm.errors.password" />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label for="password_confirmation">Konfirmasi Kata Sandi Baru</Label>
                                        <Input
                                            id="password_confirmation"
                                            v-model="passwordForm.password_confirmation"
                                            type="password"
                                            required
                                        />
                                        <InputError :message="passwordForm.errors.password_confirmation" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 pt-2">
                                    <Button type="submit" :disabled="passwordForm.processing" class="gap-2">
                                        <Loader2 v-if="passwordForm.processing" class="h-4 w-4 animate-spin" />
                                        Perbarui Kata Sandi
                                    </Button>
                                    <span
                                        v-if="passwordForm.recentlySuccessful"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600"
                                    >
                                        <Check class="h-3.5 w-3.5" />
                                        Kata sandi diperbarui
                                    </span>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </StorefrontShell>
    </div>
</template>
