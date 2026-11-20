<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { Lock, User } from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Selamat Datang Kembali',
        description: 'Masukkan username dan kata sandi Anda untuk masuk ke sistem.',
    },
});

defineProps<{
    status?: string;
}>();

const page = usePage<any>();
const flashError = computed(() => page.props.flash?.error ?? '');
</script>

<template>
    <div>
        <Head title="Masuk" />

        <div
            v-if="status"
            class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50/80 p-3 text-center text-xs font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
        >
            {{ status }}
        </div>

        <div
            v-if="flashError"
            class="mb-5 rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-center text-xs font-medium text-destructive"
        >
            {{ flashError }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <div class="grid gap-4">
                <!-- Username Input -->
                <div class="grid gap-2">
                    <Label for="username" class="text-xs font-semibold">Username</Label>
                    <div class="relative">
                        <User class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            id="username"
                            type="text"
                            name="username"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="username"
                            placeholder="Masukkan username"
                            class="h-9.5 pl-9.5 text-sm"
                        />
                    </div>
                    <InputError :message="errors.username" />
                </div>

                <!-- Password Input -->
                <div class="grid gap-2">
                    <Label for="password" class="text-xs font-semibold">Kata Sandi</Label>
                    <div class="relative">
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi"
                            class="h-9.5 text-sm"
                        />
                    </div>
                    <InputError :message="errors.password" />
                </div>

                <!-- Remember me -->
                <div class="flex items-center justify-between pt-1">
                    <Label for="remember" class="flex cursor-pointer items-center space-x-2.5">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span class="text-xs text-muted-foreground select-none">Ingat saya di perangkat ini</span>
                    </Label>
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    class="mt-2 h-10 w-full font-medium shadow-md shadow-primary/20 transition-all hover:shadow-lg hover:shadow-primary/30"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    <Lock v-else class="mr-2 h-4 w-4" />
                    {{ processing ? 'Memproses Masuk...' : 'Masuk ke Akun' }}
                </Button>
            </div>
        </Form>

        <!-- Divider -->
        <div class="relative my-5">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t border-border" />
            </div>
            <div class="relative flex justify-center text-[11px] uppercase">
                <span class="bg-card px-2 text-muted-foreground">atau masuk dengan</span>
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
                Masuk dengan Google
            </Button>
        </a>

        <p class="mt-5 text-center text-xs text-muted-foreground">
            Belum punya akun?
            <Link href="/register" class="font-semibold text-primary hover:underline">
                Daftar sekarang
            </Link>
        </p>
    </div>
</template>
