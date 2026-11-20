<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ExternalLink,
    LogIn,
    LogOut,
    Mail,
    MapPin,
    MessageCircle,
    Phone,
    Search,
    ShieldCheck,
    ShoppingBag,
    ShoppingCart,
    Truck,
    User as UserIcon,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import type { StoreIdentity } from '@/types/storefront';

interface Props {
    store: StoreIdentity;
    initialSearch?: string;
    activeNav?: 'home' | 'products' | 'categories' | 'cart';
}

const props = withDefaults(defineProps<Props>(), {
    initialSearch: '',
    activeNav: 'home',
});

const emit = defineEmits<{
    search: [query: string];
}>();

const page = usePage<any>();
const { getInitials } = useInitials();

const authUser = computed(() => page.props.auth?.user ?? null);
const cartCount = computed<number>(() => Number(page.props.cartCount ?? 0));

const searchInput = ref(props.initialSearch);
const logoFailed = ref(false);

watch(
    () => props.initialSearch,
    (val) => {
        searchInput.value = val ?? '';
    },
);

const storeLogo = computed(() =>
    !logoFailed.value && props.store.logo_url ? props.store.logo_url : '/images/logo.png',
);

const formatWhatsappUrl = (phone: string | null, text?: string): string => {
    if (!phone) {
        return '#';
    }

    const digits = phone.replace(/\D/g, '');
    const normalized = digits.startsWith('0') ? `62${digits.slice(1)}` : digits;
    const base = `https://wa.me/${normalized}`;

    return text ? `${base}?text=${encodeURIComponent(text)}` : base;
};

const handleSearchSubmit = (): void => {
    const trimmed = searchInput.value.trim();

    if (props.activeNav === 'products') {
        emit('search', trimmed);
        return;
    }

    router.get('/products', trimmed ? { search: trimmed } : {});
};

const clearSearchInput = (): void => {
    searchInput.value = '';

    if (props.activeNav === 'products') {
        emit('search', '');
    }
};

const handleLogout = (): void => {
    router.flushAll();
};

// Policy Modal
const policyModalTitle = ref('');
const policyModalContent = ref('');
const isPolicyModalOpen = ref(false);

const openPolicyModal = (title: string, content: string | null): void => {
    policyModalTitle.value = title;
    policyModalContent.value = content || 'Informasi belum tersedia.';
    isPolicyModalOpen.value = true;
};
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- TOPBAR INFORMASI -->
        <div class="border-b border-primary/20 bg-primary text-xs text-primary-foreground">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <Truck class="h-3.5 w-3.5" />
                        Pengiriman ke Seluruh Indonesia
                    </span>
                    <span v-if="store.phone" class="hidden items-center gap-1 md:inline-flex">
                        <Phone class="h-3.5 w-3.5" />
                        {{ store.phone }}
                    </span>
                    <a
                        v-if="store.email"
                        :href="`mailto:${store.email}`"
                        class="hidden items-center gap-1 hover:underline lg:inline-flex"
                    >
                        <Mail class="h-3.5 w-3.5" />
                        {{ store.email }}
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <Link href="/#identitas-toko" class="hidden hover:underline sm:inline">
                        Tentang Toko
                    </Link>
                    <template v-if="store.whatsapp">
                        <span class="hidden opacity-40 sm:inline">|</span>
                        <a
                            :href="formatWhatsappUrl(store.whatsapp, `Halo ${store.name}, saya ingin bertanya mengenai produk.`)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 font-medium hover:underline"
                        >
                            <MessageCircle class="h-3.5 w-3.5" />
                            Chat Toko
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <!-- STICKY NAVBAR & PENCARIAN PRODUK -->
        <header class="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <!-- Identitas Brand Toko -->
                    <Link href="/" class="flex shrink-0 items-center gap-2.5">
                        <img
                            :src="storeLogo"
                            :alt="store.name"
                            @error="logoFailed = true"
                            class="h-10 w-auto max-w-[140px] object-contain sm:h-11"
                        />
                        <div class="hidden flex-col sm:flex">
                            <span class="text-base leading-tight font-bold tracking-tight text-foreground">
                                {{ store.name }}
                            </span>
                            <span class="text-[11px] text-muted-foreground">
                                Toko Online Resmi
                            </span>
                        </div>
                    </Link>

                    <!-- Search Bar Desktop -->
                    <form
                        @submit.prevent="handleSearchSubmit"
                        class="relative hidden max-w-xl flex-1 md:flex"
                    >
                        <Search class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchInput"
                            type="text"
                            placeholder="Cari produk, motif, atau SKU..."
                            class="h-10 rounded-full border-border bg-muted/40 pr-28 pl-10 focus-visible:bg-background"
                        />
                        <button
                            v-if="searchInput"
                            type="button"
                            @click="clearSearchInput"
                            class="absolute top-1/2 right-20 -translate-y-1/2 rounded-full p-1 text-muted-foreground hover:text-foreground"
                            aria-label="Hapus pencarian"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                        <Button
                            type="submit"
                            size="sm"
                            class="absolute top-1/2 right-1 h-8 -translate-y-1/2 rounded-full px-4"
                        >
                            Cari
                        </Button>
                    </form>

                    <!-- Nav Menu + Cart + Login / Profile -->
                    <div class="flex items-center gap-3 lg:gap-5">
                        <nav class="hidden items-center gap-5 text-sm font-medium lg:flex">
                            <Link
                                href="/"
                                class="transition-colors hover:text-primary"
                                :class="activeNav === 'home' ? 'font-semibold text-primary' : 'text-muted-foreground'"
                            >
                                Beranda
                            </Link>
                            <Link
                                href="/products"
                                class="transition-colors hover:text-primary"
                                :class="activeNav === 'products' ? 'font-semibold text-primary' : 'text-muted-foreground'"
                            >
                                Produk
                            </Link>
                        </nav>

                        <!-- Cart Icon with Badge -->
                        <Link
                            href="/cart"
                            class="relative shrink-0"
                            aria-label="Keranjang Belanja"
                            title="Keranjang Belanja"
                        >
                            <Button
                                variant="outline"
                                size="icon"
                                class="relative h-9 w-9 rounded-full"
                                :class="activeNav === 'cart' ? 'border-primary text-primary' : ''"
                            >
                                <ShoppingCart class="h-4 w-4" />
                                <span
                                    class="absolute -top-1 -right-1 flex h-4.5 min-w-4.5 items-center justify-center rounded-full px-1 text-[10px] leading-none font-bold shadow-xs"
                                    :class="
                                        cartCount > 0
                                            ? 'bg-primary text-primary-foreground'
                                            : 'border border-border bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ cartCount > 99 ? '99+' : cartCount }}
                                </span>
                            </Button>
                        </Link>

                        <!-- Auth: Login jika belum login, Profile Icon Dropdown jika sudah login -->
                        <DropdownMenu v-if="authUser">
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    class="h-9 w-9 overflow-hidden rounded-full p-0"
                                    aria-label="Menu Profil"
                                    :title="authUser.name"
                                >
                                    <Avatar class="h-8 w-8">
                                        <AvatarImage
                                            v-if="authUser.avatar"
                                            :src="authUser.avatar"
                                            :alt="authUser.name"
                                        />
                                        <AvatarFallback class="bg-primary/10 text-xs font-semibold text-primary">
                                            {{ getInitials(authUser.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <DropdownMenuLabel class="font-normal">
                                    <div class="flex flex-col space-y-0.5">
                                        <p class="truncate text-sm font-semibold text-foreground">
                                            {{ authUser.name }}
                                        </p>
                                        <p class="truncate text-xs text-muted-foreground">
                                            {{ authUser.email || authUser.username }}
                                        </p>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuGroup>
                                    <DropdownMenuItem :as-child="true">
                                        <Link href="/profile" class="flex w-full cursor-pointer items-center">
                                            <UserIcon class="h-4 w-4" />
                                            Profil Saya
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem :as-child="true">
                                        <Link href="/cart" class="flex w-full cursor-pointer items-center">
                                            <ShoppingCart class="h-4 w-4" />
                                            Keranjang
                                            <span
                                                v-if="cartCount > 0"
                                                class="ml-auto rounded-full bg-primary/15 px-1.5 py-0.5 text-[10px] leading-none font-semibold text-primary"
                                            >
                                                {{ cartCount > 99 ? '99+' : cartCount }}
                                            </span>
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem :as-child="true">
                                        <Link href="/my-orders" class="flex w-full cursor-pointer items-center">
                                            <ShoppingBag class="h-4 w-4" />
                                            Pesanan
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem :as-child="true">
                                        <Link href="/profile#keamanan" class="flex w-full cursor-pointer items-center">
                                            <ShieldCheck class="h-4 w-4" />
                                            Keamanan Akun
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem :as-child="true">
                                    <Link
                                        :href="logout()"
                                        method="post"
                                        as="button"
                                        @click="handleLogout"
                                        class="flex w-full cursor-pointer items-center text-destructive focus:text-destructive"
                                    >
                                        <LogOut class="h-4 w-4" />
                                        Keluar
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Link v-else href="/login" class="shrink-0">
                            <Button size="sm" class="gap-1.5 rounded-full px-4">
                                <LogIn class="h-4 w-4" />
                                <span>Login</span>
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Search Bar + Nav Mobile -->
                <div class="mt-3 space-y-2.5 md:hidden">
                    <form @submit.prevent="handleSearchSubmit" class="relative flex">
                        <Search class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="searchInput"
                            type="text"
                            placeholder="Cari produk atau SKU..."
                            class="h-10 rounded-full border-border bg-muted/40 pr-20 pl-10"
                        />
                        <Button
                            type="submit"
                            size="sm"
                            class="absolute top-1/2 right-1 h-8 -translate-y-1/2 rounded-full px-4"
                        >
                            Cari
                        </Button>
                    </form>
                </div>

                <nav class="mt-2.5 flex items-center gap-5 border-t border-border/50 pt-2 text-xs font-medium lg:hidden">
                    <Link
                        href="/"
                        class="transition-colors hover:text-primary"
                        :class="activeNav === 'home' ? 'font-semibold text-primary' : 'text-muted-foreground'"
                    >
                        Beranda
                    </Link>
                    <Link
                        href="/products"
                        class="transition-colors hover:text-primary"
                        :class="activeNav === 'products' ? 'font-semibold text-primary' : 'text-muted-foreground'"
                    >
                        Produk
                    </Link>
                </nav>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- FOOTER TOKO ONLINE -->
        <footer class="border-t border-zinc-800 bg-zinc-950 text-zinc-300">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Kolom 1: Identitas Toko -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <img
                                :src="storeLogo"
                                :alt="store.name"
                                class="h-10 w-auto max-w-[140px] rounded bg-white p-1 object-contain"
                            />
                            <span class="text-lg font-bold text-white">{{ store.name }}</span>
                        </div>
                        <p v-if="store.description" class="text-xs leading-relaxed text-zinc-400">
                            {{ store.description }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <a
                                v-if="store.instagram_url"
                                :href="store.instagram_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-md border border-zinc-800 bg-zinc-900 px-2.5 py-1 text-xs text-zinc-300 transition hover:border-zinc-700 hover:text-white"
                            >
                                Instagram
                            </a>
                            <a
                                v-if="store.facebook_url"
                                :href="store.facebook_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-md border border-zinc-800 bg-zinc-900 px-2.5 py-1 text-xs text-zinc-300 transition hover:border-zinc-700 hover:text-white"
                            >
                                Facebook
                            </a>
                            <a
                                v-if="store.twitter_url"
                                :href="store.twitter_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-md border border-zinc-800 bg-zinc-900 px-2.5 py-1 text-xs text-zinc-300 transition hover:border-zinc-700 hover:text-white"
                            >
                                X / Twitter
                            </a>
                        </div>
                    </div>

                    <!-- Kolom 2: Navigasi Belanja -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold tracking-wide text-white uppercase">Jelajahi Toko</h3>
                        <ul class="space-y-2 text-xs text-zinc-400">
                            <li><Link href="/" class="transition hover:text-white">Beranda</Link></li>
                            <li><Link href="/products" class="transition hover:text-white">Semua Produk</Link></li>
                            <li><Link href="/categories" class="transition hover:text-white">Kategori Produk</Link></li>
                            <li><Link href="/#rekomendasi" class="transition hover:text-white">Produk Rekomendasi</Link></li>
                            <li><Link href="/#terlaris" class="transition hover:text-white">Produk Terlaris</Link></li>
                        </ul>
                    </div>

                    <!-- Kolom 3: Informasi & Kebijakan -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold tracking-wide text-white uppercase">Layanan & Kebijakan</h3>
                        <ul class="space-y-2 text-xs text-zinc-400">
                            <li>
                                <button
                                    type="button"
                                    @click="openPolicyModal('Kebijakan Privasi', store.privacy_policy)"
                                    class="text-left transition hover:text-white"
                                >
                                    Kebijakan Privasi
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    @click="openPolicyModal('Syarat & Ketentuan Toko', store.policy)"
                                    class="text-left transition hover:text-white"
                                >
                                    Syarat & Ketentuan
                                </button>
                            </li>
                            <li>
                                <button
                                    type="button"
                                    @click="openPolicyModal('Kebijakan Pembatalan', store.cancellation_policy)"
                                    class="text-left transition hover:text-white"
                                >
                                    Kebijakan Pembatalan
                                </button>
                            </li>
                            <li>
                                <Link href="/#identitas-toko" class="transition hover:text-white">
                                    Tentang {{ store.name }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Kolom 4: Kontak & Lokasi -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold tracking-wide text-white uppercase">Kontak Toko</h3>
                        <ul class="space-y-2.5 text-xs text-zinc-400">
                            <li v-if="store.address" class="flex items-start gap-2">
                                <MapPin class="mt-0.5 h-3.5 w-3.5 shrink-0 text-zinc-400" />
                                <span>{{ store.address }}</span>
                            </li>
                            <li v-if="store.phone" class="flex items-center gap-2">
                                <Phone class="h-3.5 w-3.5 shrink-0 text-zinc-400" />
                                <span>{{ store.phone }}</span>
                            </li>
                            <li v-if="store.whatsapp" class="flex items-center gap-2">
                                <MessageCircle class="h-3.5 w-3.5 shrink-0 text-emerald-400" />
                                <a
                                    :href="formatWhatsappUrl(store.whatsapp)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="transition hover:text-white"
                                >
                                    Chat Toko ({{ store.whatsapp }})
                                </a>
                            </li>
                            <li v-if="store.email" class="flex items-center gap-2">
                                <Mail class="h-3.5 w-3.5 shrink-0 text-zinc-400" />
                                <a :href="`mailto:${store.email}`" class="transition hover:text-white">
                                    {{ store.email }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-zinc-800 pt-6 text-xs text-zinc-500 sm:flex-row">
                    <p>&copy; {{ new Date().getFullYear() }} {{ store.name }}. Seluruh hak cipta dilindungi.</p>
                    <p v-if="store.website">
                        <a
                            :href="store.website"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 hover:text-zinc-300"
                        >
                            {{ store.website }}
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </p>
                </div>
            </div>
        </footer>

        <!-- MODAL KEBIJAKAN TOKO -->
        <Dialog v-model:open="isPolicyModalOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ policyModalTitle }}</DialogTitle>
                    <DialogDescription>{{ store.name }}</DialogDescription>
                </DialogHeader>
                <div class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                    {{ policyModalContent }}
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
