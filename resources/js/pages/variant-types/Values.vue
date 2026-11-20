<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, GripVertical, Layers, Loader2, Plus, Save, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface VariantValue {
    id: number;
    value: string;
    sort_order: number;
}

interface VariantType {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    values: VariantValue[];
}

interface Props {
    variantType: VariantType;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '#' },
    { title: 'Varian Produk', href: '/dashboard/variant-types' },
    { title: 'Nilai Varian', href: '#' },
];

// Local state for editing values
const localValues = ref<string[]>(props.variantType.values.map((v) => v.value));
const newValue = ref('');

const form = useForm({
    values: [] as string[],
});

const addValue = () => {
    const trimmed = newValue.value.trim();
    if (trimmed && !localValues.value.includes(trimmed)) {
        localValues.value.push(trimmed);
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
    localValues.value.splice(index, 1);
};

const updateValue = (index: number, val: string) => {
    localValues.value[index] = val;
};

const save = () => {
    form.values = [...localValues.value];
    form.post(`/dashboard/variant-types/${props.variantType.id}/values`);
};

const isDirty = ref(false);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="`Nilai Varian: ${variantType.name}`" />

        <!-- Breadcrumbs in Content -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                        {{ variantType.name }}
                    </h1>
                    <Badge :variant="variantType.is_active ? 'default' : 'secondary'">
                        {{ variantType.is_active ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kelola nilai/opsi untuk tipe varian ini. Total: <strong>{{ localValues.length }}</strong> nilai.
                </p>
            </div>

            <Button as-child variant="outline" class="shrink-0 gap-2">
                <Link href="/dashboard/variant-types">
                    <ArrowLeft class="h-4 w-4" />
                    Kembali
                </Link>
            </Button>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Values Editor -->
            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Layers class="h-5 w-5 text-primary" />
                            Daftar Nilai Varian
                        </CardTitle>
                        <CardDescription>
                            Tambah, edit, atau hapus nilai untuk varian <strong>{{ variantType.name }}</strong>.
                            Klik Simpan untuk menyimpan semua perubahan sekaligus.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <!-- Add new value -->
                        <div class="flex gap-2">
                            <Input
                                v-model="newValue"
                                :placeholder="`Tambah nilai baru (contoh: S, M, L, XL)`"
                                @keydown="addValueOnEnter"
                                class="flex-1"
                            />
                            <Button type="button" variant="outline" @click="addValue" class="gap-2 shrink-0">
                                <Plus class="h-4 w-4" />
                                Tambah
                            </Button>
                        </div>

                        <!-- Values list -->
                        <div v-if="localValues.length > 0" class="flex flex-col gap-2">
                            <div
                                v-for="(val, index) in localValues"
                                :key="index"
                                class="flex items-center gap-3 rounded-lg border bg-card px-3 py-2 group hover:border-primary/30 transition-colors"
                            >
                                <GripVertical class="h-4 w-4 text-muted-foreground/40 shrink-0" />
                                <span class="text-xs text-muted-foreground w-6 text-right shrink-0">{{ index + 1 }}.</span>
                                <Input
                                    v-model="localValues[index]"
                                    class="flex-1 h-8 border-0 bg-transparent px-0 text-sm shadow-none focus-visible:ring-0 focus-visible:border-b focus-visible:rounded-none"
                                    :placeholder="`Nilai ${index + 1}`"
                                />
                                <button
                                    type="button"
                                    @click="removeValue(index)"
                                    class="text-muted-foreground opacity-0 group-hover:opacity-100 hover:text-destructive transition-all shrink-0"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div v-else class="rounded-lg border border-dashed p-10 text-center">
                            <Layers class="h-10 w-10 mx-auto mb-3 text-muted-foreground/30" />
                            <p class="text-sm font-medium text-muted-foreground">Belum ada nilai</p>
                            <p class="text-xs text-muted-foreground mt-1">
                                Tambahkan nilai pertama menggunakan kolom di atas.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar info + save -->
            <div class="flex flex-col gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm">Info Varian</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground">Nama</span>
                            <span class="font-medium">{{ variantType.name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground">Slug</span>
                            <code class="text-xs bg-muted px-1.5 py-0.5 rounded">{{ variantType.slug }}</code>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground">Total Nilai</span>
                            <span class="font-medium">{{ localValues.length }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground">Status</span>
                            <Badge :variant="variantType.is_active ? 'default' : 'secondary'" class="text-xs">
                                {{ variantType.is_active ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Button class="w-full gap-2" @click="save" :disabled="form.processing">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <Save v-else class="h-4 w-4" />
                    Simpan Nilai Varian
                </Button>

                <Link :href="`/dashboard/variant-types/${variantType.id}/edit`">
                    <Button variant="outline" class="w-full gap-2">
                        <Layers class="h-4 w-4" />
                        Edit Informasi Varian
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
