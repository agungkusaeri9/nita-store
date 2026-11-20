<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { AtSign, Mail, User, UserPlus } from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Buat Akun Baru',
        description: 'Daftar untuk mulai berbelanja dan melacak pesanan Anda.',
    },
});

const page = usePage<any>();
const flashError = computed(() => page.props.flash?.error ?? '');

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div>
        <Head title="Daftar Akun" />

        <div
            v-if="flashError"
            class="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-center text-xs font-medium text-destructive"
        >
            {{ flashError }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <!-- Nama Lengkap -->
            <div class="grid gap-1.5">
                <Label for="name" class="text-xs font-semibold">Nama Lengkap</Label>
                <div class="relative">
                    <User class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Masukkan nama lengkap"
                        class="h-9.5 pl-9.5 text-sm"
                    />
                </div>
                <InputError :message="form.errors.name" />
            </div>

            <!-- Username -->
            <div class="grid gap-1.5">
                <Label for="username" class="text-xs font-semibold">Username</Label>
                <div class="relative">
                    <AtSign class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        id="username"
                        v-model="form.username"
                        type="text"
                        required
                        autocomplete="username"
                        placeholder="contoh: budi_santoso"
                        class="h-9.5 pl-9.5 text-sm"
                    />
                </div>
                <InputError :message="form.errors.username" />
            </div>

            <!-- Email -->
            <div class="grid gap-1.5">
                <Label for="email" class="text-xs font-semibold">Email</Label>
                <div class="relative">
                    <Mail class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="nama@email.com"
                        class="h-9.5 pl-9.5 text-sm"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="grid gap-1.5">
                <Label for="password" class="text-xs font-semibold">Kata Sandi</Label>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    class="h-9.5 text-sm"
                />
                <InputError :message="form.errors.password" />
            </div>

            <!-- Konfirmasi Password -->
            <div class="grid gap-1.5">
                <Label for="password_confirmation" class="text-xs font-semibold">Konfirmasi Kata Sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi"
                    class="h-9.5 text-sm"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-1 h-10 w-full font-medium shadow-md shadow-primary/20 transition-all hover:shadow-lg hover:shadow-primary/30"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" class="mr-2" />
                <UserPlus v-else class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Mendaftarkan Akun...' : 'Daftar Sekarang' }}
            </Button>
        </form>

        <!-- Divider -->
        <div class="relative my-5">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t border-border" />
            </div>
            <div class="relative flex justify-center text-[11px] uppercase">
                <span class="bg-card px-2 text-muted-foreground">atau daftar dengan</span>
            </div>
        </div>

        <!-- Google OAuth Button -->
        <a href="/auth/google/redirect" class="block">
            <Button type="button" variant="outline" class="h-10 w-full gap-2.5 font-medium">
                <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="#4285F4"
                        d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17Z"
                    />
                    <path
                        fill="#34A853"
                        d="M12 24c3.3 0 6.08-1.09 8.105-2.96l-3.88-3.05c-1.09.73-2.49 1.16-4.225 1.16-3.24 0-5.99-2.19-6.97-5.14H1.03v3.14C3.05 21.15 7.21 24 12 24Z"
                    />
                    <path
                        fill="#FBBC05"
                        d="M5.03 14.01c-.25-.73-.39-1.51-.39-2.31s.14-1.58.39-2.31V6.25H1.03A11.98 11.98 0 0 0 0 11.7c0 1.94.46 3.77 1.03 5.45l4-3.14Z"
                    />
                    <path
                        fill="#EA4335"
                        d="M12 4.75c1.8 0 3.41.62 4.68 1.83l3.51-3.51C18.07 1.19 15.3 0 12 0 7.21 0 3.05 2.85 1.03 6.95l4 3.14c.98-2.95 3.73-5.34 6.97-5.34Z"
                    />
                </svg>
                Daftar dengan Google
            </Button>
        </a>

        <p class="mt-5 text-center text-xs text-muted-foreground">
            Sudah punya akun?
            <Link href="/login" class="font-semibold text-primary hover:underline">
                Masuk sekarang
            </Link>
        </p>
    </div>
</template>
