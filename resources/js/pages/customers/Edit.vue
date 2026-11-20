<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Save } from 'lucide-vue-next';

interface Customer {
    id: number;
    phone: string | null;
    is_active: boolean;
    user: {
        id: number;
        name: string;
        email: string;
    };
}

const props = defineProps<{
    customer: Customer;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customer', href: '/dashboard/customers' },
    { title: props.customer.user.name, href: `/dashboard/customers/${props.customer.id}` },
    { title: 'Edit', href: `/dashboard/customers/${props.customer.id}/edit` },
];

const form = useForm({
    _method: 'PUT',
    name: props.customer.user.name,
    email: props.customer.user.email,
    phone: props.customer.phone || '',
    password: '',
    password_confirmation: '',
    is_active: props.customer.is_active,
});

const submit = () => {
    form.post(`/dashboard/customers/${props.customer.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="`Edit Customer - ${customer.user.name}`" />
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <Button variant="outline" size="icon" as-child>
                    <Link :href="`/dashboard/customers/${customer.id}`">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <h1 class="text-2xl font-bold tracking-tight">Edit Customer</h1>
            </div>
        </div>

        <form @submit.prevent="submit" class="max-w-2xl">
            <Card>
                <CardHeader>
                    <CardTitle>Data Pelanggan</CardTitle>
                    <CardDescription>Perbarui informasi akun dan profil pelanggan.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="space-y-2">
                        <Label for="name">Nama Lengkap <span class="text-destructive">*</span></Label>
                        <Input id="name" v-model="form.name" required />
                        <span class="text-xs text-destructive" v-if="form.errors.name">{{ form.errors.name }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email <span class="text-destructive">*</span></Label>
                        <Input id="email" type="email" v-model="form.email" required />
                        <span class="text-xs text-destructive" v-if="form.errors.email">{{ form.errors.email }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">No. HP</Label>
                        <Input id="phone" type="tel" v-model="form.phone" />
                        <span class="text-xs text-destructive" v-if="form.errors.phone">{{ form.errors.phone }}</span>
                    </div>

                    <div class="space-y-4 rounded-lg border p-4 bg-muted/20">
                        <h3 class="font-medium text-sm">Ganti Password (Opsional)</h3>
                        <p class="text-xs text-muted-foreground">Kosongkan jika tidak ingin mengubah password.</p>
                        
                        <div class="space-y-2">
                            <Label for="password">Password Baru</Label>
                            <Input id="password" type="password" v-model="form.password" autocomplete="new-password" />
                            <span class="text-xs text-destructive" v-if="form.errors.password">{{ form.errors.password }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">Konfirmasi Password Baru</Label>
                            <Input id="password_confirmation" type="password" v-model="form.password_confirmation" autocomplete="new-password" />
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label class="text-base">Status Akun</Label>
                            <p class="text-sm text-muted-foreground">
                                Tentukan apakah pelanggan ini aktif atau tidak.
                            </p>
                        </div>
                        <!-- Using native checkbox styled nicely due to Reka-UI bug mentioned before -->
                        <div class="relative flex items-center">
                            <input 
                                type="checkbox" 
                                v-model="form.is_active" 
                                id="is_active" 
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-input after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white rtl:peer-checked:after:-translate-x-full cursor-pointer" @click="form.is_active = !form.is_active"></div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 pt-4">
                        <Button type="button" variant="outline" as-child>
                            <Link :href="`/dashboard/customers/${customer.id}`">Batal</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="gap-2">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            Simpan Perubahan
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    </div>
</template>
