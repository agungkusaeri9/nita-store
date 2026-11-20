<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import InputError from '@/components/InputError.vue';
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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Check,
    ExternalLink,
    Image as ImageIcon,
    Info,
    Layers,
    Package,
    Save,
    Smartphone,
    Upload,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ProductOption {
    id: number;
    name: string;
    price: string;
}

interface CategoryOption {
    id: number;
    name: string;
}

interface BannerData {
    id: number;
    title: string;
    image: string;
    image_url: string | null;
    target_type: 'none' | 'product' | 'category' | 'url';
    product_id: number | null;
    category_id: number | null;
    target_url: string | null;
    sort_order: number;
    is_active: boolean;
    start_date: string;
    end_date: string;
    description: string | null;
}

interface Props {
    banner: BannerData;
    products: ProductOption[];
    categories: CategoryOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Banner Promosi', href: '/dashboard/banners' },
    { title: 'Edit Banner', href: `/dashboard/banners/${props.banner.id}/edit` },
];

const form = useForm({
    _method: 'put',
    title: props.banner.title || '',
    image: null as File | null,
    target_type: props.banner.target_type || 'none',
    product_id: props.banner.product_id,
    category_id: props.banner.category_id,
    target_url: props.banner.target_url || '',
    sort_order: props.banner.sort_order ?? 1,
    is_active: Boolean(props.banner.is_active),
    start_date: props.banner.start_date || '',
    end_date: props.banner.end_date || '',
    description: props.banner.description || '',
});

const imagePreview = ref<string | null>(props.banner.image_url);
const fileInputRef = ref<HTMLInputElement | null>(null);

const handleImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const removeNewImage = () => {
    form.image = null;
    imagePreview.value = props.banner.image_url;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const formatRupiah = (angka: string) => {
    const num = parseFloat(angka);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(num);
};

const submit = () => {
    form.post(`/dashboard/banners/${props.banner.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 bg-muted/20">
        <Head :title="`Edit Banner: ${banner.title}`" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="icon" as-child class="h-8 w-8">
                        <Link href="/dashboard/banners">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                        Edit Banner Promosi
                    </h1>
                </div>
                <p class="text-sm text-muted-foreground ml-10">
                    Perbarui informasi banner, gambar, target klik, atau urutan tampilan.
                </p>
            </div>

            <div class="flex items-center gap-2 ml-10 sm:ml-0">
                <Button variant="outline" as-child>
                    <Link href="/dashboard/banners">
                        Batal
                    </Link>
                </Button>
                <Button :disabled="form.processing" @click="submit">
                    <Save class="mr-2 h-4 w-4" />
                    Simpan Perubahan
                </Button>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-12">
            <!-- Left Column: Banner Form Details -->
            <div class="space-y-6 lg:col-span-7">
                <!-- Main Information Card -->
                <Card class="bg-background">
                    <CardHeader>
                        <CardTitle>Informasi Banner</CardTitle>
                        <CardDescription>
                            Judul promosi, target interaksi saat diklik di mobile, dan urutan carousel.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Judul Banner -->
                        <div class="space-y-2">
                            <Label for="title">
                                Judul Banner / Nama Promo <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Contoh: Diskon Spesial Hari Batik Nasional 30%"
                                required
                            />
                            <InputError :message="form.errors.title" />
                        </div>

                        <!-- Target Klik Action Selection -->
                        <div class="space-y-2">
                            <Label>Aksi Saat Banner Diklik (Mobile App)</Label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <button
                                    type="button"
                                    class="flex flex-col items-center justify-center p-3 rounded-lg border text-xs font-medium transition-all text-center gap-1.5"
                                    :class="form.target_type === 'none' ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary' : 'bg-background hover:bg-muted/50 text-muted-foreground'"
                                    @click="form.target_type = 'none'"
                                >
                                    <ImageIcon class="h-4 w-4" />
                                    <span>Hanya Gambar</span>
                                </button>
                                <button
                                    type="button"
                                    class="flex flex-col items-center justify-center p-3 rounded-lg border text-xs font-medium transition-all text-center gap-1.5"
                                    :class="form.target_type === 'product' ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary' : 'bg-background hover:bg-muted/50 text-muted-foreground'"
                                    @click="form.target_type = 'product'"
                                >
                                    <Package class="h-4 w-4" />
                                    <span>Buka Produk</span>
                                </button>
                                <button
                                    type="button"
                                    class="flex flex-col items-center justify-center p-3 rounded-lg border text-xs font-medium transition-all text-center gap-1.5"
                                    :class="form.target_type === 'category' ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary' : 'bg-background hover:bg-muted/50 text-muted-foreground'"
                                    @click="form.target_type = 'category'"
                                >
                                    <Layers class="h-4 w-4" />
                                    <span>Buka Kategori</span>
                                </button>
                                <button
                                    type="button"
                                    class="flex flex-col items-center justify-center p-3 rounded-lg border text-xs font-medium transition-all text-center gap-1.5"
                                    :class="form.target_type === 'url' ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary' : 'bg-background hover:bg-muted/50 text-muted-foreground'"
                                    @click="form.target_type = 'url'"
                                >
                                    <ExternalLink class="h-4 w-4" />
                                    <span>URL Kustom</span>
                                </button>
                            </div>
                        </div>

                        <!-- Target: Product Dropdown -->
                        <div v-if="form.target_type === 'product'" class="space-y-2 p-3 rounded-lg bg-muted/20 border border-border/60">
                            <Label for="product_id">Pilih Produk Tujuan <span class="text-destructive">*</span></Label>
                            <Select
                                :model-value="form.product_id ? form.product_id.toString() : ''"
                                @update:model-value="val => form.product_id = val ? parseInt(val) : null"
                            >
                                <SelectTrigger id="product_id">
                                    <SelectValue placeholder="-- Pilih Produk --" />
                                </SelectTrigger>
                                <SelectContent class="max-h-60">
                                    <SelectItem
                                        v-for="product in products"
                                        :key="product.id"
                                        :value="product.id.toString()"
                                    >
                                        {{ product.name }} ({{ formatRupiah(product.price) }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.product_id" />
                        </div>

                        <!-- Target: Category Dropdown -->
                        <div v-if="form.target_type === 'category'" class="space-y-2 p-3 rounded-lg bg-muted/20 border border-border/60">
                            <Label for="category_id">Pilih Kategori Tujuan <span class="text-destructive">*</span></Label>
                            <Select
                                :model-value="form.category_id ? form.category_id.toString() : ''"
                                @update:model-value="val => form.category_id = val ? parseInt(val) : null"
                            >
                                <SelectTrigger id="category_id">
                                    <SelectValue placeholder="-- Pilih Kategori --" />
                                </SelectTrigger>
                                <SelectContent class="max-h-60">
                                    <SelectItem
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        :value="cat.id.toString()"
                                    >
                                        {{ cat.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.category_id" />
                        </div>

                        <!-- Target: Custom URL Input -->
                        <div v-if="form.target_type === 'url'" class="space-y-2 p-3 rounded-lg bg-muted/20 border border-border/60">
                            <Label for="target_url">URL / Deeplink Tujuan <span class="text-destructive">*</span></Label>
                            <Input
                                id="target_url"
                                v-model="form.target_url"
                                placeholder="https://domain.com/promo-khusus"
                            />
                            <p class="text-xs text-muted-foreground">
                                Masukkan URL eksternal lengkap (dengan https://).
                            </p>
                            <InputError :message="form.errors.target_url" />
                        </div>

                        <!-- Sort Order & Status -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div class="space-y-2">
                                <Label for="sort_order">
                                    Urutan Slider (Sort Order)
                                </Label>
                                <Input
                                    id="sort_order"
                                    type="number"
                                    min="0"
                                    v-model.number="form.sort_order"
                                    placeholder="1"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Semakin kecil angka, semakin awal muncul di carousel (misal 1, 2, 3).
                                </p>
                                <InputError :message="form.errors.sort_order" />
                            </div>

                            <div class="space-y-2">
                                <Label class="block mb-2">Status Publikasi</Label>
                                <div class="flex items-center justify-between p-3 rounded-lg border bg-muted/10 h-11">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full" :class="form.is_active ? 'bg-emerald-500' : 'bg-muted-foreground/50'"></span>
                                        <span class="text-sm font-medium" :class="form.is_active ? 'text-foreground font-semibold' : 'text-muted-foreground'">
                                            {{ form.is_active ? 'Aktif (Ditampilkan di Mobile)' : 'Nonaktif (Disembunyikan)' }}
                                        </span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_active"
                                            class="sr-only peer"
                                        />
                                        <div class="w-11 h-6 bg-input peer-focus:outline-hidden peer-focus:ring-2 peer-focus:ring-primary/40 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Display Period (Optional) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div class="space-y-2">
                                <Label for="start_date">Mulai Tayang (Opsional)</Label>
                                <Input
                                    id="start_date"
                                    type="datetime-local"
                                    v-model="form.start_date"
                                />
                                <InputError :message="form.errors.start_date" />
                            </div>
                            <div class="space-y-2">
                                <Label for="end_date">Berakhir Tayang (Opsional)</Label>
                                <Input
                                    id="end_date"
                                    type="datetime-local"
                                    v-model="form.end_date"
                                />
                                <InputError :message="form.errors.end_date" />
                            </div>
                        </div>

                        <!-- Description / Note -->
                        <div class="space-y-2 pt-2">
                            <Label for="description">Deskripsi / Catatan Internal</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Tuliskan keterangan campaign promo atau catatan untuk banner ini..."
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Image Upload & Mobile Live Mockup Preview -->
            <div class="space-y-6 lg:col-span-5">
                <!-- Image Upload & Mobile Specs Card -->
                <Card class="bg-background">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Smartphone class="h-5 w-5 text-primary" />
                            Gambar & Preview Mobile
                        </CardTitle>
                        <CardDescription>
                            Rekomendasi rasio 2:1 (1080 x 540 px) untuk tampilan optimal di HP.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Hidden File Input -->
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/png,image/jpeg,image/jpg,image/webp"
                            class="hidden"
                            @change="handleImageChange"
                        />

                        <!-- Image Preview Box -->
                        <div class="space-y-3">
                            <div class="relative w-full aspect-[2/1] overflow-hidden rounded-xl border bg-muted shadow-sm group flex items-center justify-center">
                                <img
                                    v-if="imagePreview"
                                    :src="imagePreview"
                                    alt="Preview Banner"
                                    class="h-full w-full object-cover"
                                />
                                <ImageIcon v-else class="h-10 w-10 text-muted-foreground/40" />

                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        class="h-8 text-xs"
                                        @click="triggerFileInput"
                                    >
                                        Ganti Gambar
                                    </Button>
                                    <Button
                                        v-if="form.image"
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        class="h-8 text-xs"
                                        @click="removeNewImage"
                                    >
                                        <X class="h-3.5 w-3.5 mr-1" />
                                        Batal Ganti
                                    </Button>
                                </div>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                class="w-full text-xs"
                                @click="triggerFileInput"
                            >
                                <Upload class="h-3.5 w-3.5 mr-1.5" />
                                {{ form.image ? 'Pilih Gambar Lain' : 'Unggah / Ganti Gambar' }}
                            </Button>
                        </div>

                        <InputError :message="form.errors.image" />

                        <!-- Specifications Checklist Card -->
                        <div class="rounded-lg border bg-muted/30 p-4 space-y-2.5 text-xs text-muted-foreground">
                            <div class="font-semibold text-foreground flex items-center gap-1.5">
                                <Info class="h-4 w-4 text-primary" />
                                Spesifikasi Rekomendasi Banner Mobile:
                            </div>
                            <ul class="space-y-1.5 pl-1">
                                <li class="flex items-start gap-2">
                                    <Check class="h-3.5 w-3.5 text-emerald-600 shrink-0 mt-0.5" />
                                    <span><strong>Rasio Aspek:</strong> 2:1 (Landscape Mobile Carousel)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <Check class="h-3.5 w-3.5 text-emerald-600 shrink-0 mt-0.5" />
                                    <span><strong>Resolusi Ideal:</strong> 1080 x 540 px (atau 720 x 360 px)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <Check class="h-3.5 w-3.5 text-emerald-600 shrink-0 mt-0.5" />
                                    <span><strong>Ukuran File:</strong> Maksimal 2.048 KB (2 MB)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <Check class="h-3.5 w-3.5 text-emerald-600 shrink-0 mt-0.5" />
                                    <span><strong>Area Aman Teks:</strong> Posisikan teks promo di tengah agar tidak terpotong tepi layar HP.</span>
                                </li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </form>
    </div>
</template>
