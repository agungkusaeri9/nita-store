<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Edit, MapPin, Phone, Mail, User, ShieldCheck } from 'lucide-vue-next';

interface CustomerAddress {
    id: number;
    label: string;
    recipient_name: string;
    phone: string;
    full_address: string;
    city: string | null;
    province: string | null;
    postal_code: string | null;
    is_primary: boolean;
}

interface Customer {
    id: number;
    phone: string | null;
    is_active: boolean;
    user: {
        id: number;
        name: string;
        email: string;
    };
    addresses: CustomerAddress[];
    created_at: string;
}

const props = defineProps<{
    customer: Customer;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customer', href: '/dashboard/customers' },
    { title: props.customer.user.name, href: `/dashboard/customers/${props.customer.id}` },
];
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="`Detail Customer - ${customer.user.name}`" />
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <Button variant="outline" size="icon" as-child>
                    <Link href="/dashboard/customers">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <h1 class="text-2xl font-bold tracking-tight">Detail Customer</h1>
            </div>
            <Button as-child class="gap-2">
                <Link :href="`/dashboard/customers/${customer.id}/edit`">
                    <Edit class="h-4 w-4" /> Edit Customer
                </Link>
            </Button>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Customer Profile -->
            <Card>
                <CardHeader>
                    <CardTitle>Profil Pelanggan</CardTitle>
                    <CardDescription>Informasi akun dan kontak.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground flex items-center gap-2">
                            <User class="h-4 w-4" /> Nama
                        </span>
                        <span class="font-medium">{{ customer.user.name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground flex items-center gap-2">
                            <Mail class="h-4 w-4" /> Email
                        </span>
                        <span class="font-medium">{{ customer.user.email }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground flex items-center gap-2">
                            <Phone class="h-4 w-4" /> No. HP
                        </span>
                        <span class="font-medium">{{ customer.phone || '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground flex items-center gap-2">
                            <ShieldCheck class="h-4 w-4" /> Status Akun
                        </span>
                        <Badge :variant="customer.is_active ? 'default' : 'secondary'">
                            {{ customer.is_active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Bergabung Sejak</span>
                        <span class="font-medium">{{ new Date(customer.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Addresses List -->
            <Card class="md:row-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        Daftar Alamat
                        <Badge variant="outline">{{ customer.addresses.length }} Alamat</Badge>
                    </CardTitle>
                    <CardDescription>Daftar alamat pengiriman yang tersimpan.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="customer.addresses.length === 0" class="flex flex-col items-center justify-center p-8 text-center text-muted-foreground border rounded-lg border-dashed">
                        <MapPin class="h-8 w-8 mb-2 opacity-50" />
                        <p>Belum ada alamat yang tersimpan.</p>
                    </div>
                    
                    <div v-for="address in customer.addresses" :key="address.id" class="rounded-lg border p-4 transition-colors hover:bg-muted/50">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <Badge variant="secondary" class="font-semibold">{{ address.label }}</Badge>
                                <Badge v-if="address.is_primary" variant="default">Utama</Badge>
                            </div>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="font-semibold">{{ address.recipient_name }}</p>
                            <p class="text-muted-foreground">{{ address.phone }}</p>
                            <p class="mt-2">{{ address.full_address }}</p>
                            <p class="text-muted-foreground">
                                {{ address.city ? address.city + ',' : '' }} 
                                {{ address.province }} 
                                {{ address.postal_code }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
