<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Palette, Shield, User } from 'lucide-vue-next';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { BreadcrumbItem, NavItem } from '@/types';

const sidebarNavItems: (NavItem & { icon: any })[] = [
    {
        title: 'Profil Pengguna',
        href: editProfile(),
        icon: User,
    },
    {
        title: 'Keamanan Akun',
        href: editSecurity(),
        icon: Shield,
    },
    {
        title: 'Tampilan & Tema',
        href: editAppearance(),
        icon: Palette,
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();

const currentNav = computed(() =>
    sidebarNavItems.find((item) => isCurrentOrParentUrl(item.href)),
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengaturan', href: '/dashboard/settings/profile' },
    { title: currentNav.value?.title || 'Pengaturan', href: '#' },
]);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Breadcrumbs in Content Body -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                Pengaturan Akun
            </h1>
            <p class="text-sm text-muted-foreground">
                Kelola informasi profil, keamanan kata sandi, dan preferensi tema aplikasi.
            </p>
        </div>

        <!-- Settings Container -->
        <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
            <!-- Sidebar Navigation -->
            <aside class="w-full lg:w-56 shrink-0">
                <nav class="flex flex-row gap-1 overflow-x-auto rounded-xl border bg-card p-1.5 shadow-xs lg:flex-col lg:overflow-visible">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'justify-start gap-2.5 rounded-lg text-xs font-medium transition-colors',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-primary/10 text-primary hover:bg-primary/15 hover:text-primary font-semibold'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </Button>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1">
                <div class="space-y-6">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
