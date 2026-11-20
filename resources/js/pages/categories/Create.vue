<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { UploadCloud, X, ImageIcon } from 'lucide-vue-next';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '#' },
    { title: 'Kategori Produk', href: '/dashboard/categories' },
    { title: 'Tambah Kategori', href: '/dashboard/categories/create' },
];

const form = useForm({
    name: '',
    slug: '',
    description: '',
    image: null as File | null,
    is_active: true,
});

const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const handleNameInput = () => {
    // Generate slug from name
    form.slug = form.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    form.post('/dashboard/categories');
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Tambah Kategori Produk" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                Tambah Kategori Produk
            </h1>
            <p class="text-sm text-muted-foreground">
                Tambahkan kategori baru beserta gambar dan pengaturannya.
            </p>
        </div>

        <Card class="w-full">
            <CardHeader>
                <CardTitle>Informasi Kategori</CardTitle>
                <CardDescription>
                    Masukkan data lengkap untuk kategori produk baru.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="grid gap-6">
                    <!-- Image Upload -->
                    <div class="grid gap-2">
                        <Label>Gambar Kategori</Label>
                        <div class="flex flex-col sm:flex-row items-start gap-4">
                            <!-- Preview Box -->
                            <div
                                class="relative flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed bg-muted/40 transition-colors"
                                :class="{ 'border-primary/50 bg-primary/5': imagePreview }"
                            >
                                <img
                                    v-if="imagePreview"
                                    :src="imagePreview"
                                    alt="Preview"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center gap-1.5 p-2 text-center text-muted-foreground"
                                >
                                    <ImageIcon class="h-8 w-8 text-muted-foreground/60" />
                                    <span class="text-[11px]">Belum ada gambar</span>
                                </div>

                                <button
                                    v-if="imagePreview"
                                    type="button"
                                    @click="removeImage"
                                    class="absolute top-1.5 right-1.5 rounded-full bg-background/80 p-1 text-muted-foreground backdrop-blur-xs hover:bg-destructive hover:text-destructive-foreground transition-colors"
                                    title="Hapus gambar"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Upload Input Button & Help -->
                            <div class="flex flex-1 flex-col gap-2">
                                <input
                                    ref="fileInput"
                                    type="file"
                                    id="image"
                                    accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="w-fit"
                                    @click="fileInput?.click()"
                                >
                                    <UploadCloud class="mr-2 h-4 w-4" />
                                    Pilih File Gambar
                                </Button>
                                <p class="text-xs text-muted-foreground">
                                    Format yang didukung: PNG, JPG, JPEG, WEBP, atau SVG. Ukuran maksimal 2MB.
                                </p>
                                <p
                                    v-if="form.errors.image"
                                    class="text-xs font-medium text-destructive"
                                >
                                    {{ form.errors.image }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Category Name -->
                    <div class="grid gap-2">
                        <Label for="name">Nama Kategori <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            @input="handleNameInput"
                            placeholder="Contoh: Batik Tulis Premium"
                            required
                        />
                        <p
                            v-if="form.errors.name"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Category Slug -->
                    <div class="grid gap-2">
                        <Label for="slug">Slug URL</Label>
                        <div class="relative">
                            <Input
                                id="slug"
                                v-model="form.slug"
                                placeholder="batik-tulis-premium"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Slug digunakan untuk alamat URL ramah mesin pencari (SEO). Dibuat otomatis dari nama jika dikosongkan.
                        </p>
                        <p
                            v-if="form.errors.slug"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.slug }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="description">Deskripsi</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            placeholder="Penjelasan singkat mengenai koleksi atau produk dalam kategori ini..."
                        />
                        <p
                            v-if="form.errors.description"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Status Active -->
                    <div class="grid gap-2">
                        <Label>Status Publikasi</Label>
                        <div class="flex items-center space-x-3 rounded-lg border bg-muted/20 p-4">
                            <input
                                type="checkbox"
                                id="is_active"
                                v-model="form.is_active"
                                class="size-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <div class="space-y-0.5">
                                <label
                                    for="is_active"
                                    class="cursor-pointer text-sm font-medium leading-none text-foreground"
                                >
                                    Kategori Aktif
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    Kategori yang aktif akan langsung dapat diakses dan digunakan pada produk.
                                </p>
                            </div>
                        </div>
                        <p
                            v-if="form.errors.is_active"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.is_active }}
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-4 border-t pt-4">
                        <Button type="button" variant="outline" as-child>
                            <Link href="/dashboard/categories">Batal</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing ? 'Menyimpan...' : 'Simpan Kategori'
                            }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
