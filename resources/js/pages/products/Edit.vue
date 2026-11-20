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
import { ImagePlus, Loader2, Package, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
}

interface VariantValue {
    id: number;
    value: string;
}

interface VariantType {
    id: number;
    name: string;
    values: VariantValue[];
}

interface ProductImage {
    id: number;
    url: string | null;
    sort_order: number;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    category_id: number | null;
    description: string | null;
    primary_image_url: string | null;
    price: string;
    stock: number;
    sku: string | null;
    weight: number;
    is_active: boolean;
    images: ProductImage[];
    variant_values: number[];
}

interface Props {
    product: Product;
    categories: Category[];
    variantTypes: VariantType[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '#' },
    { title: 'Semua Produk', href: '/dashboard/products' },
    { title: 'Edit Produk', href: '#' },
];

const form = useForm({
    _method: 'PUT',
    name: props.product.name,
    slug: props.product.slug,
    category_id: props.product.category_id?.toString() || '',
    description: props.product.description || '',
    price: parseFloat(props.product.price).toString(), // format as string without .00
    stock: props.product.stock.toString(),
    sku: props.product.sku || '',
    weight: props.product.weight.toString(),
    is_active: props.product.is_active ? 'true' : 'false',
    primary_image: null as File | null,
    gallery_images: [] as File[],
    variant_values: [...props.product.variant_values],
    deleted_images: [] as number[],
});

// Primary Image Preview
const imagePreview = ref<string | null>(props.product.primary_image_url);
const fileInput = ref<HTMLInputElement | null>(null);

const handleImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        form.primary_image = file;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    form.primary_image = null;
    imagePreview.value = null; // Doesn't remove from DB yet, but visually removed
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

// Existing Gallery Images
const existingGallery = ref<ProductImage[]>([...props.product.images]);

const removeExistingGalleryImage = (index: number) => {
    const img = existingGallery.value[index];
    form.deleted_images.push(img.id);
    existingGallery.value.splice(index, 1);
};

// New Gallery Images
const galleryPreviews = ref<{ url: string, file: File }[]>([]);
const galleryInput = ref<HTMLInputElement | null>(null);

const handleGalleryChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        Array.from(target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                galleryPreviews.value.push({
                    url: e.target?.result as string,
                    file: file
                });
                form.gallery_images.push(file);
            };
            reader.readAsDataURL(file);
        });
        
        if (galleryInput.value) galleryInput.value.value = '';
    }
};

const removeNewGalleryImage = (index: number) => {
    galleryPreviews.value.splice(index, 1);
    form.gallery_images.splice(index, 1);
};

// Variants handling
const toggleVariantValue = (id: number) => {
    const index = form.variant_values.indexOf(id);
    if (index === -1) {
        form.variant_values.push(id);
    } else {
        form.variant_values.splice(index, 1);
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        is_active: data.is_active === 'true' ? true : false,
    })).post(`/dashboard/products/${props.product.id}`);
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Edit Produk" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Edit Produk: {{ product.name }}</h1>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 flex flex-col gap-6">
                <!-- Basic Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Dasar</CardTitle>
                        <CardDescription>Detail utama dari produk ini.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <div class="grid gap-1.5">
                            <Label for="name">Nama Produk <span class="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Contoh: Kemeja Batik Mega Mendung"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="grid gap-1.5">
                                <Label for="category_id">Kategori</Label>
                                <Select v-model="form.category_id" >
                                    <SelectTrigger id="category_id">
                                        <SelectValue placeholder="Pilih Kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id.toString()">
                                            {{ cat.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.category_id" class="text-xs text-destructive">{{ form.errors.category_id }}</p>
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
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="description">Deskripsi</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Tuliskan detail spesifikasi, bahan, dll."
                                class="min-h-[120px]"
                            ></Textarea>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pricing and Stock -->
                <Card>
                    <CardHeader>
                        <CardTitle>Harga & Manajemen Stok</CardTitle>
                        <CardDescription>Informasi harga dasar dan persediaan.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="grid gap-1.5">
                                <Label for="price">Harga (Rp) <span class="text-destructive">*</span></Label>
                                <Input
                                    id="price"
                                    v-model="form.price"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    :class="{ 'border-destructive': form.errors.price }"
                                />
                                <p v-if="form.errors.price" class="text-xs text-destructive">{{ form.errors.price }}</p>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="stock">Stok <span class="text-destructive">*</span></Label>
                                <Input
                                    id="stock"
                                    v-model="form.stock"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    :class="{ 'border-destructive': form.errors.stock }"
                                />
                                <p v-if="form.errors.stock" class="text-xs text-destructive">{{ form.errors.stock }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="grid gap-1.5">
                                <Label for="sku">SKU (Stock Keeping Unit)</Label>
                                <Input
                                    id="sku"
                                    v-model="form.sku"
                                    placeholder="Contoh: BTK-MM-001"
                                    :class="{ 'border-destructive': form.errors.sku }"
                                />
                                <p v-if="form.errors.sku" class="text-xs text-destructive">{{ form.errors.sku }}</p>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="weight">Berat (Gram) <span class="text-destructive">*</span></Label>
                                <Input
                                    id="weight"
                                    v-model="form.weight"
                                    type="number"
                                    min="0"
                                    placeholder="Contoh: 250"
                                    :class="{ 'border-destructive': form.errors.weight }"
                                />
                                <p v-if="form.errors.weight" class="text-xs text-destructive">{{ form.errors.weight }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Variants -->
                <Card v-if="variantTypes.length > 0">
                    <CardHeader>
                        <CardTitle>Varian Produk</CardTitle>
                        <CardDescription>Pilih nilai varian apa saja yang tersedia untuk produk ini.</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-6">
                        <div v-for="vt in variantTypes" :key="vt.id" class="grid gap-3 border-b pb-4 last:border-0 last:pb-0">
                            <Label class="text-sm font-semibold">{{ vt.name }}</Label>
                            <div class="flex flex-wrap gap-2">
                                <div 
                                    v-for="val in vt.values" 
                                    :key="val.id"
                                    @click="toggleVariantValue(val.id)"
                                    class="cursor-pointer select-none rounded-md border px-3 py-1.5 text-sm transition-colors"
                                    :class="form.variant_values.includes(val.id) ? 'bg-primary border-primary text-primary-foreground' : 'bg-background hover:bg-muted'"
                                >
                                    {{ val.value }}
                                </div>
                                <div v-if="vt.values.length === 0" class="text-sm text-muted-foreground italic">
                                    Belum ada nilai varian.
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="flex flex-col gap-6">
                <!-- Status & Try-On -->
                <Card>
                    <CardHeader>
                        <CardTitle>Pengaturan & Fitur</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-6">
                        <div class="grid gap-1.5">
                            <Label for="is_active">Status Publikasi</Label>
                            <Select v-model="form.is_active">
                                <SelectTrigger id="is_active">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="true">Aktif (Ditampilkan)</SelectItem>
                                    <SelectItem value="false">Nonaktif (Disembunyikan)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <!-- Media -->
                <Card>
                    <CardHeader>
                        <CardTitle>Gambar Utama</CardTitle>
                        <CardDescription>Gambar yang muncul pertama kali.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <div 
                            v-if="!imagePreview" 
                            class="flex flex-col items-center justify-center rounded-lg border border-dashed border-muted-foreground/25 px-6 py-8 text-center"
                        >
                            <ImagePlus class="mx-auto h-8 w-8 text-muted-foreground/50" />
                            <div class="mt-4 flex text-sm leading-6 text-muted-foreground">
                                <label for="file-upload" class="relative cursor-pointer rounded-md bg-background font-semibold text-primary hover:text-primary/80">
                                    <span>Upload gambar</span>
                                    <input id="file-upload" type="file" class="sr-only" accept="image/*" @change="handleImageChange" ref="fileInput">
                                </label>
                            </div>
                            <p class="text-xs leading-5 text-muted-foreground">PNG, JPG up to 2MB</p>
                        </div>
                        
                        <div v-else class="relative overflow-hidden rounded-lg border">
                            <img :src="imagePreview" alt="Preview" class="h-48 w-full object-cover" />
                            <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity hover:opacity-100">
                                <Button type="button" variant="destructive" size="sm" @click="removeImage" class="gap-2">
                                    <Trash2 class="h-4 w-4" /> Hapus
                                </Button>
                            </div>
                        </div>
                        <p v-if="form.errors.primary_image" class="text-xs text-destructive">{{ form.errors.primary_image }}</p>
                    </CardContent>
                </Card>

                <!-- Gallery -->
                <Card>
                    <CardHeader>
                        <CardTitle>Galeri Gambar</CardTitle>
                        <CardDescription>Tambahkan gambar tambahan.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <div class="grid grid-cols-3 gap-2">
                            <!-- Existing Previews -->
                            <div v-for="(preview, index) in existingGallery" :key="'ex-'+index" class="relative aspect-square overflow-hidden rounded-md border group">
                                <img :src="preview.url || ''" class="h-full w-full object-cover" />
                                <button type="button" @click="removeExistingGalleryImage(index)" class="absolute top-1 right-1 h-5 w-5 bg-black/60 rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-destructive">
                                    <X class="h-3 w-3" />
                                </button>
                            </div>
                            
                            <!-- New Previews -->
                            <div v-for="(preview, index) in galleryPreviews" :key="'new-'+index" class="relative aspect-square overflow-hidden rounded-md border group border-primary/50">
                                <img :src="preview.url" class="h-full w-full object-cover" />
                                <button type="button" @click="removeNewGalleryImage(index)" class="absolute top-1 right-1 h-5 w-5 bg-black/60 rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-destructive">
                                    <X class="h-3 w-3" />
                                </button>
                                <span class="absolute bottom-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground">
                                    <Plus class="h-2 w-2" />
                                </span>
                            </div>
                            
                            <!-- Add button -->
                            <label class="flex aspect-square cursor-pointer flex-col items-center justify-center rounded-md border border-dashed hover:bg-muted/50 transition-colors" :class="{ 'border-destructive': Object.keys(form.errors).some(k => k.startsWith('gallery_images')) }">
                                <Plus class="h-6 w-6 text-muted-foreground" />
                                <input type="file" multiple class="sr-only" accept="image/*" @change="handleGalleryChange" ref="galleryInput">
                            </label>
                        </div>
                        
                        <!-- Validation Errors -->
                        <div v-if="Object.keys(form.errors).some(k => k.startsWith('gallery_images'))" class="mt-2 text-xs text-destructive flex flex-col gap-1">
                            <span v-for="(error, key) in form.errors" :key="key">
                                <template v-if="key.toString().startsWith('gallery_images')">{{ error }}</template>
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Submit -->
                <div class="flex flex-col gap-2">
                    <Button type="submit" class="w-full gap-2" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Package v-else class="h-4 w-4" />
                        Simpan Perubahan
                    </Button>
                    <Link href="/dashboard/products">
                        <Button type="button" variant="outline" class="w-full">Batal</Button>
                    </Link>
                </div>
            </div>
        </form>
    </div>
</template>
