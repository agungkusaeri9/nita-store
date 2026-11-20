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
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Layers, Loader2, Plus, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '#' },
    { title: 'Varian Produk', href: '/dashboard/variant-types' },
    { title: 'Tambah Varian', href: '/dashboard/variant-types/create' },
];

const form = useForm({
    name: '',
    slug: '',
    description: '',
    is_active: 'true',
    sort_order: '0',
    values: [] as string[],
});

// Auto-generate slug from name
watch(() => form.name, (newName) => {
    if (!form.slug || form.slug === autoSlug.value) {
        const generated = newName.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
        form.slug = generated;
    }
    autoSlug.value = newName.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
});
const autoSlug = ref('');

// Values management
const newValue = ref('');

const addValue = () => {
    const trimmed = newValue.value.trim();
    if (trimmed && !form.values.includes(trimmed)) {
        form.values.push(trimmed);
        newValue.value = '';
    }
};

const addValueOnEnter = (e: KeyboardEvent) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        addValue();
    }
};

const removeValue = (index: number) => {
    form.values.splice(index, 1);
};

const submit = () => {
    form.post('/dashboard/variant-types');
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Tambah Varian Produk" />

        <!-- Breadcrumbs in Content -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Tambah Varian Produk</h1>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
            <!-- Main Info -->
            <div class="lg:col-span-2 flex flex-col gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Varian</CardTitle>
                        <CardDescription>Isi detail tipe varian produk</CardDescription>
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
                                placeholder="auto-generate dari nama"
                                :class="{ 'border-destructive': form.errors.slug }"
                            />
                            <p v-if="form.errors.slug" class="text-xs text-destructive">{{ form.errors.slug }}</p>
                            <p v-else class="text-xs text-muted-foreground">Dikosongkan untuk auto-generate.</p>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="description">Deskripsi</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Deskripsi singkat tentang varian ini (opsional)"
                                class="min-h-[80px]"
                            ></Textarea>
                            <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Values -->
                <Card>
                    <CardHeader>
                        <CardTitle>Nilai Varian</CardTitle>
                        <CardDescription>
                            Tambahkan nilai/opsi untuk tipe varian ini. Contoh: S, M, L, XL untuk "Ukuran"
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <div class="flex gap-2">
                            <Input
                                v-model="newValue"
                                placeholder="Masukkan nilai lalu tekan Enter atau klik Tambah"
                                @keydown="addValueOnEnter"
                                class="flex-1"
                            />
                            <Button type="button" variant="outline" @click="addValue" class="gap-2">
                                <Plus class="h-4 w-4" />
                                Tambah
                            </Button>
                        </div>
                        <div v-if="form.values.length > 0" class="flex flex-wrap gap-2">
                            <div
                                v-for="(val, index) in form.values"
                                :key="index"
                                class="flex items-center gap-1.5 rounded-md border bg-muted/50 px-3 py-1.5 text-sm"
                            >
                                <span>{{ val }}</span>
                                <button
                                    type="button"
                                    @click="removeValue(index)"
                                    class="text-muted-foreground hover:text-destructive transition-colors ml-1"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                        <div v-else class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                            Belum ada nilai yang ditambahkan. Nilai bisa juga ditambahkan nanti setelah varian disimpan.
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
                                placeholder="0"
                                :class="{ 'border-destructive': form.errors.sort_order }"
                            />
                            <p class="text-xs text-muted-foreground">Semakin kecil angka, semakin atas posisinya.</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col gap-2">
                    <Button type="submit" class="w-full gap-2" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Layers v-else class="h-4 w-4" />
                        Simpan Varian
                    </Button>
                    <Link href="/dashboard/variant-types">
                        <Button type="button" variant="outline" class="w-full">Batal</Button>
                    </Link>
                </div>
            </div>
        </form>
    </div>
</template>
