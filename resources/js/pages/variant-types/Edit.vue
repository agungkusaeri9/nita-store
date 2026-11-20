<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Layers, Loader2 } from 'lucide-vue-next';

interface VariantType {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
}

interface Props {
    variantType: VariantType;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '#' },
    { title: 'Varian Produk', href: '/dashboard/variant-types' },
    { title: 'Edit Varian', href: '#' },
];

const form = useForm({
    name: props.variantType.name,
    slug: props.variantType.slug,
    description: props.variantType.description || '',
    is_active: String(props.variantType.is_active),
    sort_order: String(props.variantType.sort_order),
});

const submit = () => {
    form.put(`/dashboard/variant-types/${props.variantType.id}`);
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Edit Varian Produk" />

        <!-- Breadcrumbs in Content -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Edit Varian: {{ variantType.name }}</h1>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
            <!-- Main Info -->
            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Varian</CardTitle>
                        <CardDescription>Perbarui detail tipe varian produk</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <div class="grid gap-1.5">
                            <Label for="name">Nama Varian <span class="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="contoh: Ukuran, Warna, Motif"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="slug">Slug URL</Label>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                :class="{ 'border-destructive': form.errors.slug }"
                            />
                            <p v-if="form.errors.slug" class="text-xs text-destructive">{{ form.errors.slug }}</p>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="description">Deskripsi</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Deskripsi singkat (opsional)"
                                class="min-h-[80px]"
                            ></Textarea>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="flex flex-col gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Pengaturan</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <div class="grid gap-1.5">
                            <Label for="is_active">Status</Label>
                            <Select v-model="form.is_active">
                                <SelectTrigger id="is_active">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="true">Aktif</SelectItem>
                                    <SelectItem value="false">Nonaktif</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="sort_order">Urutan Tampil</Label>
                            <Input
                                id="sort_order"
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                                :class="{ 'border-destructive': form.errors.sort_order }"
                            />
                            <p class="text-xs text-muted-foreground">Semakin kecil angka, semakin atas posisinya.</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-primary/20 bg-primary/5">
                    <CardContent class="pt-4 pb-4">
                        <p class="text-sm text-muted-foreground mb-3">Kelola nilai/opsi untuk varian ini</p>
                        <Link :href="`/dashboard/variant-types/${variantType.id}/values`">
                            <Button type="button" variant="outline" class="w-full gap-2">
                                <Layers class="h-4 w-4" />
                                Kelola Nilai Varian
                            </Button>
                        </Link>
                    </CardContent>
                </Card>

                <div class="flex flex-col gap-2">
                    <Button type="submit" class="w-full gap-2" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Layers v-else class="h-4 w-4" />
                        Perbarui Varian
                    </Button>
                    <Link href="/dashboard/variant-types">
                        <Button type="button" variant="outline" class="w-full">Batal</Button>
                    </Link>
                </div>
            </div>
        </form>
    </div>
</template>
