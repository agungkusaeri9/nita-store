<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Database,
    LayoutGrid,
    Package,
    Users,
    History,
    ShieldCheck,
    CreditCard,
    Truck,
    Settings,
    ShoppingBag,
    Bell,
    Smartphone,
    SlidersHorizontal,
    UserCog,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import type { NavItem } from '@/types';

const { can, hasRole, isSuperAdmin } = usePermissions();

const page = usePage<any>();

const rawNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        permission: 'view dashboard',
    },
    {
        title: 'Pesanan',
        href: '/dashboard/orders',
        icon: ShoppingBag,
        permission: 'view orders',
        badge: page.props.pendingOrdersCount ?? 0,
    },
    {
        title: 'Banner Promosi',
        href: '/dashboard/banners',
        icon: Smartphone,
        permission: 'view banners',
    },
    {
        title: 'Produk',
        href: '#',
        icon: Package,
        items: [
            {
                title: 'Semua Produk',
                href: '/dashboard/products',
                permission: 'view products',
            },
            {
                title: 'Kategori Produk',
                href: '/dashboard/categories',
                permission: 'view categories',
            },
            {
                title: 'Varian Produk',
                href: '/dashboard/variant-types',
                permission: 'view variant types',
            },
        ],
    },
    {
        title: 'Data Master',
        href: '#',
        icon: Database,
        items: [
            {
                title: 'Kelola User',
                href: '/dashboard/users',
                icon: UserCog,
                permission: 'view users',
            },
            {
                title: 'Pelanggan',
                href: '/dashboard/customers',
                icon: Users,
                permission: 'view customers',
            },
            {
                title: 'Metode Pembayaran',
                href: '/dashboard/payment-methods',
                icon: CreditCard,
                permission: 'view payment methods',
            },
            {
                title: 'Kurir (RajaOngkir)',
                href: '/dashboard/couriers',
                icon: Truck,
                permission: 'view couriers',
            },
        ],
    },
    {
        title: 'Peran & Hak Akses',
        href: '#',
        icon: ShieldCheck,
        items: [
            {
                title: 'Peran (Roles)',
                href: '/dashboard/roles',
                permission: 'view roles',
            },
            {
                title: 'Hak Akses (Permissions)',
                href: '/dashboard/permissions',
                permission: 'view permissions',
            },
        ],
    },
    {
        title: 'Notifikasi',
        href: '#',
        icon: Bell,
        items: [
            {
                title: 'Daftar Notifikasi',
                href: '/dashboard/notifications',
                permission: 'view notifications',
            },
            {
                title: 'Template Pengaturan',
                href: '/dashboard/notification-settings',
                permission: 'manage notification settings',
            },
        ],
    },
    {
        title: 'Konfigurasi',
        href: '#',
        icon: SlidersHorizontal,
        items: [
            {
                title: 'Semua Konfigurasi',
                href: '/dashboard/configurations',
                permission: 'view app configs',
            },
            {
                title: 'RajaOngkir',
                href: '/dashboard/configurations/rajaongkir',
                permission: 'view app configs',
            },
            {
                title: 'Geoapify',
                href: '/dashboard/configurations/geoapify',
                permission: 'view app configs',
            },
            {
                title: 'Firebase (FCM)',
                href: '/dashboard/configurations/firebase',
                permission: 'view app configs',
            },
        ],
    },
    {
        title: 'Pengaturan',
        href: '#',
        icon: Settings,
        items: [
            {
                title: 'Pengaturan Toko',
                href: '/dashboard/store-settings',
                permission: 'view store settings',
            },
            {
                title: 'Kebijakan & Privasi',
                href: '/dashboard/privacy-policy-settings',
                permission: 'view privacy policy',
            },
            {
                title: 'FAQ (Tanya Jawab)',
                href: '/dashboard/faqs',
                permission: 'view faqs',
            },
        ],
    },
    {
        title: 'Log Aktivitas',
        href: '/dashboard/activity',
        icon: History,
        permission: 'view activity logs',
    },
]);

const checkItemAccess = (item: NavItem): boolean => {
    if (isSuperAdmin.value) {
return true;
}

    if (item.role && !hasRole(item.role)) {
return false;
}

    if (item.permission && !can(item.permission)) {
return false;
}

    return true;
};

const mainNavItems = computed<NavItem[]>(() => {
    return rawNavItems.value
        .map((item) => {
            if (item.items && item.items.length > 0) {
                const filteredSubItems = item.items.filter((sub) => checkItemAccess(sub));

                if (filteredSubItems.length === 0) {
                    return null;
                }

                return {
                    ...item,
                    items: filteredSubItems,
                };
            }

            return checkItemAccess(item) ? item : null;
        })
        .filter((item): item is NavItem => item !== null);
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="h-auto! justify-center py-2 hover:bg-transparent active:bg-transparent group-data-[collapsible=icon]:h-8!">
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
