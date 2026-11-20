<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Loader2,
    MapPin,
    Search,
    X,
    Eye,
    ArrowLeft
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Province {
    province_id: number;
    province_name: string;
}

interface City {
    city_id: number;
    province_id: number;
    city_name: string;
    postal_code: string;
}

interface PaginationData {
    data: City[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    province: Province;
    cities: PaginationData;
    filters: {
        search?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Lokasi (Provinsi)', href: '/dashboard/locations' },
    { title: `Kota di ${props.province.province_name}`, href: '#' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page?.toString() || '10');
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout>;

const fetchCities = () => {
    isLoading.value = true;
    router.get(`/dashboard/locations/provinces/${props.province.province_id}/cities`, {
        search: search.value,
        per_page: perPage.value
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => (isLoading.value = false),
    });
};

watch(search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchCities, 400);
    }
});

watch(perPage, () => {
    fetchCities();
});

const perPageOptions = ['10', '20', '30', '40', '50', '100'];

const clearSearch = () => {
    search.value = '';
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="`Daftar Kota - ${province.province_name}`" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <Link href="/dashboard/locations">
                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-full">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                        Daftar Kota / Kabupaten
                    </h1>
                </div>
                <p class="text-sm text-muted-foreground ml-11">
                    Provinsi: <strong>{{ province.province_name }}</strong>
                </p>
            </div>
        </div>

        <div class="flex flex-col rounded-xl border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Tampilkan</span>
                    <Select v-model="perPage">
                        <SelectTrigger class="h-8 w-[70px] bg-background text-xs">
                            <SelectValue :placeholder="perPage" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in perPageOptions" :key="option" :value="option">
                                {{ option }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span class="text-sm text-muted-foreground">data</span>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative w-full max-w-xs sm:w-64">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            type="search"
                            placeholder="Cari kota / kabupaten..."
                            class="h-8 w-full bg-background pl-8 pr-8 text-xs"
                        />
                        <button
                            v-if="search"
                            type="button"
                            class="absolute top-2 right-2 text-muted-foreground hover:text-foreground"
                            @click="clearSearch"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="relative overflow-x-auto">
                <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-background/50 backdrop-blur-sm">
                    <Loader2 class="h-8 w-8 animate-spin text-primary" />
                </div>
                <table class="w-full caption-bottom text-sm">
                    <thead class="bg-muted/50 [&_tr]:border-b">
                        <tr class="border-b transition-colors">
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">ID Kota</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Nama Kota / Kabupaten</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Kode Pos</th>
                            <th class="h-10 px-4 text-right align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="cities.data.length === 0">
                            <td colspan="4" class="h-32 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <MapPin class="h-8 w-8 text-muted-foreground/50" />
                                    <p>Tidak ada Kota / Kabupaten ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="city in cities.data"
                            :key="city.city_id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                {{ city.city_id }}
                            </td>
                            <td class="p-4 align-middle font-medium">
                                {{ city.city_name }}
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ city.postal_code }}
                            </td>
                            <td class="p-4 align-middle text-right">
                                <Link :href="`/dashboard/locations/cities/${city.city_id}/subdistricts`">
                                    <Button variant="outline" size="sm" class="h-8 gap-1">
                                        <Eye class="h-3.5 w-3.5" />
                                        <span class="sr-only sm:not-sr-only">Lihat Kecamatan</span>
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t p-4" v-if="cities.data.length > 0">
                <div class="text-xs text-muted-foreground">
                    Menampilkan <strong>{{ cities.from }}</strong> sampai <strong>{{ cities.to }}</strong> dari <strong>{{ cities.total }}</strong> data
                </div>
                
                <div class="flex items-center gap-1">
                    <Link
                        v-for="(link, index) in cities.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'inline-flex h-8 items-center justify-center rounded-md px-3 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50',
                            link.active ? 'bg-primary text-primary-foreground hover:bg-primary/90' : 'hover:bg-accent hover:text-accent-foreground',
                            !link.url ? 'pointer-events-none opacity-50' : ''
                        ]"
                        :disabled="!link.url"
                    >
                        <span v-if="link.label.includes('Previous')" class="flex items-center">
                            <ChevronLeft class="h-4 w-4" />
                        </span>
                        <span v-else-if="link.label.includes('Next')" class="flex items-center">
                            <ChevronRight class="h-4 w-4" />
                        </span>
                        <span v-else v-html="link.label"></span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
