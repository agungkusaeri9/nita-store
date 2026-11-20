<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Edit,
    Filter,
    HelpCircle,
    Loader2,
    Plus,
    Search,
    Trash2,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

interface Faq {
    id: number;
    title: string;
    description: string;
    category: string | null;
    order: number;
    created_at: string;
}

interface PaginationData {
    data: Faq[];
    current_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    faqs: PaginationData;
    categories: string[];
    filters: {
        search?: string;
        category?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengaturan', href: '#' },
    { title: 'FAQ (Tanya Jawab)', href: '/dashboard/faqs' },
];

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || 'all');
const perPage = ref(props.filters.per_page || '10');
const perPageOptions = ['10', '25', '50', '100'];

let searchTimeout: ReturnType<typeof setTimeout>;
watch([search, category, perPage], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/dashboard/faqs',
            {
                search: search.value,
                category: category.value,
                per_page: perPage.value,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});

// Modal State
const isModalOpen = ref(false);
const editingFaq = ref<Faq | null>(null);

const form = useForm({
    title: '',
    description: '',
    category: 'Umum',
    order: 0,
});

const openCreateModal = () => {
    editingFaq.value = null;
    form.reset();
    form.clearErrors();
    form.title = '';
    form.description = '';
    form.category = 'Umum';
    form.order = 0;
    isModalOpen.value = true;
};

const openEditModal = (faq: Faq) => {
    editingFaq.value = faq;
    form.clearErrors();
    form.title = faq.title;
    form.description = faq.description;
    form.category = faq.category || 'Umum';
    form.order = faq.order ?? 0;
    isModalOpen.value = true;
};

const submitForm = () => {
    if (editingFaq.value) {
        form.put(`/dashboard/faqs/${editingFaq.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingFaq.value = null;
            },
        });
    } else {
        form.post('/dashboard/faqs', {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingFaq.value = null;
            },
        });
    }
};

// Delete Alert State
const isDeleteDialogOpen = ref(false);
const faqToDelete = ref<Faq | null>(null);

const confirmDelete = (faq: Faq) => {
    faqToDelete.value = faq;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (faqToDelete.value) {
        router.delete(`/dashboard/faqs/${faqToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                faqToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Manajemen FAQ (Tanya Jawab)" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <HelpCircle class="h-8 w-8 text-primary" />
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                        FAQ (Tanya Jawab)
                    </h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kelola daftar pertanyaan dan jawaban seputar pemesanan, produk batik, dan pengiriman.
                </p>
            </div>

            <Button v-if="can('create faqs')" @click="openCreateModal" class="gap-2">
                <Plus class="h-4 w-4" />
                Tambah FAQ
            </Button>
        </div>

        <!-- Card Container with Filters & Table -->
        <div class="rounded-xl border bg-card shadow-xs">
            <!-- Filter & Search Controls -->
            <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Per Page Selector -->
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Tampilkan</span>
                    <Select v-model="perPage">
                        <SelectTrigger class="h-8 w-[72px] text-xs">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="opt in perPageOptions" :key="opt" :value="opt">
                                {{ opt }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span>data</span>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Category Filter -->
                    <div class="flex items-center gap-2 border-r pr-2 mr-2">
                        <Filter class="h-4 w-4 text-muted-foreground" />
                        <Select v-model="category">
                            <SelectTrigger class="h-8 w-[150px] bg-background text-xs">
                                <SelectValue placeholder="Kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Kategori</SelectItem>
                                <SelectItem v-for="cat in categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full max-w-xs sm:w-64">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            type="search"
                            placeholder="Cari pertanyaan / jawaban..."
                            class="h-8 w-full bg-background pl-8 text-xs"
                        />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground text-xs uppercase">
                        <tr>
                            <th class="h-10 px-4 w-16 text-center font-medium">Urutan</th>
                            <th class="h-10 px-4 w-36 text-left font-medium">Kategori</th>
                            <th class="h-10 px-4 text-left font-medium">Pertanyaan & Jawaban</th>
                            <th class="h-10 px-4 w-24 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="faqs.data.length === 0">
                            <td colspan="4" class="p-8 text-center text-muted-foreground">
                                Tidak ada data FAQ ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="faq in faqs.data"
                            :key="faq.id"
                            class="transition-colors hover:bg-muted/50"
                        >
                            <!-- Urutan -->
                            <td class="p-4 text-center font-mono text-xs text-muted-foreground">
                                {{ faq.order }}
                            </td>

                            <!-- Kategori -->
                            <td class="p-4 align-top whitespace-nowrap">
                                <Badge variant="secondary" class="text-xs font-normal">
                                    {{ faq.category || 'Umum' }}
                                </Badge>
                            </td>

                            <!-- Pertanyaan & Jawaban -->
                            <td class="p-4 align-top">
                                <div class="space-y-1">
                                    <div class="font-medium text-foreground text-sm">
                                        {{ faq.title }}
                                    </div>
                                    <div class="text-xs text-muted-foreground leading-relaxed">
                                        {{ faq.description }}
                                    </div>
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td class="p-4 text-right align-top whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        v-if="can('edit faqs')"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-muted-foreground hover:text-foreground"
                                        @click="openEditModal(faq)"
                                        title="Edit FAQ"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        v-if="can('delete faqs')"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-muted-foreground hover:text-destructive"
                                        @click="confirmDelete(faq)"
                                        title="Hapus FAQ"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (Same as Orders) -->
            <div class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between" v-if="faqs.data.length > 0">
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ faqs.from || 0 }} sampai {{ faqs.to || 0 }} dari {{ faqs.total }} data
                </div>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in faqs.links" :key="i">
                        <Button
                            v-if="link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-8 px-3 text-xs"
                            @click="router.get(link.url, { search, category, per_page: perPage }, { preserveState: true })"
                            v-html="link.label"
                        />
                        <span v-else class="px-2 py-1 text-xs text-muted-foreground" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Create / Edit Dialog -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-[560px]">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingFaq ? 'Edit FAQ' : 'Tambah FAQ Baru' }}
                    </DialogTitle>
                    <DialogDescription>
                        Isi pertanyaan, jawaban, dan kategori untuk membantu pelanggan toko.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4 py-2">
                    <!-- Title -->
                    <div class="space-y-1.5">
                        <Label for="title">Pertanyaan (Judul) <span class="text-destructive">*</span></Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="Contoh: Bagaimana cara memesan batik seragam?"
                            class="bg-background"
                            :class="{ 'border-destructive': form.errors.title }"
                        />
                        <p v-if="form.errors.title" class="text-xs text-destructive">{{ form.errors.title }}</p>
                    </div>

                    <!-- Category & Order Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Category (Input biasa) -->
                        <div class="space-y-1.5">
                            <Label for="category">Kategori (Input Bebas)</Label>
                            <Input
                                id="category"
                                v-model="form.category"
                                placeholder="Contoh: Pemesanan, Pengiriman, Retur"
                                class="bg-background"
                                :class="{ 'border-destructive': form.errors.category }"
                            />
                            <p v-if="form.errors.category" class="text-xs text-destructive">{{ form.errors.category }}</p>
                        </div>

                        <!-- Order -->
                        <div class="space-y-1.5">
                            <Label for="order">Urutan Tampil</Label>
                            <Input
                                id="order"
                                type="number"
                                v-model="form.order"
                                min="0"
                                placeholder="0"
                                class="bg-background"
                                :class="{ 'border-destructive': form.errors.order }"
                            />
                            <p v-if="form.errors.order" class="text-xs text-destructive">{{ form.errors.order }}</p>
                        </div>
                    </div>

                    <!-- Description / Answer -->
                    <div class="space-y-1.5">
                        <Label for="description">Jawaban / Penjelasan <span class="text-destructive">*</span></Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            rows="5"
                            placeholder="Tuliskan jawaban lengkap di sini..."
                            class="bg-background text-sm"
                            :class="{ 'border-destructive': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                    </div>

                    <DialogFooter class="pt-2">
                        <Button type="button" variant="outline" @click="isModalOpen = false">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                            {{ editingFaq ? 'Simpan Perubahan' : 'Tambah FAQ' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus FAQ?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Anda yakin ingin menghapus FAQ: <strong>"{{ faqToDelete?.title }}"</strong>? Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="executeDelete"
                    >
                        Hapus FAQ
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
