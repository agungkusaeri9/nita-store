<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Comprehensive list of permissions grouped by module
        $permissions = [
            // Dashboard
            'view dashboard',

            // Manajemen Pengguna (Admin Users)
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Pelanggan (Customers)
            'view customers',
            'edit customers',
            'delete customers',

            // Peran & Hak Akses (Roles & Permissions)
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',

            // Produk (Products)
            'view products',
            'create products',
            'edit products',
            'delete products',
            'import products',

            // Kategori Produk (Categories)
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            // Tipe & Nilai Varian (Variant Types)
            'view variant types',
            'create variant types',
            'edit variant types',
            'delete variant types',

            // Pesanan (Orders)
            'view orders',
            'edit orders',
            'update order status',
            'update order payment',
            'update order shipping',

            // Banner Promosi (Banners)
            'view banners',
            'create banners',
            'edit banners',
            'delete banners',
            'toggle banners',

            // Metode Pembayaran (Payment Methods)
            'view payment methods',
            'create payment methods',
            'edit payment methods',
            'delete payment methods',

            // Kurir (Couriers)
            'view couriers',
            'create couriers',
            'edit couriers',
            'delete couriers',
            'toggle couriers',

            // Lokasi Wilayah (Locations)
            'view locations',

            // Notifikasi (Notifications)
            'view notifications',
            'create notifications',
            'delete notifications',
            'manage notification settings',

            // Ulasan Produk (Product Reviews)
            'view product reviews',
            'toggle product reviews',
            'delete product reviews',

            // Pengaturan Toko (Store Settings)
            'view store settings',
            'edit store settings',

            // Kebijakan & Privasi (Privacy Policy)
            'view privacy policy',
            'edit privacy policy',

            // FAQ (Tanya Jawab)
            'view faqs',
            'create faqs',
            'edit faqs',
            'delete faqs',

            // Manajemen Aplikasi (App Versions)
            'view app versions',
            'create app versions',
            'edit app versions',
            'delete app versions',

            // Konfigurasi Aplikasi (App Configurations)
            'view app configs',
            'create app configs',
            'edit app configs',
            'delete app configs',

            // Log Aktivitas (Activity Logs)
            'view activity logs',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create Super Admin role and sync all permissions
        $superAdminRole = Role::findOrCreate('super admin', 'web');
        $superAdminRole->syncPermissions(Permission::all());

        // Create Admin role with all operational permissions except user management, roles/permissions, and app configurations
        $adminRole = Role::findOrCreate('admin', 'web');
        $excludedAdminPermissions = [
            // Manajemen Pengguna (User Management)
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Peran & Hak Akses (Role & Permission)
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',

            // Konfigurasi Aplikasi (Configurations)
            'view app configs',
            'create app configs',
            'edit app configs',
            'delete app configs',
        ];
        $adminPermissions = array_values(array_diff($permissions, $excludedAdminPermissions));
        $adminRole->syncPermissions($adminPermissions);

        // Create Customer role
        Role::findOrCreate('customer', 'web');
        Role::findOrCreate('customer', 'api');

        // Assign super admin role to the superadmin user or first user
        $superAdminUser = User::where('username', 'superadmin')->first() ?? User::first();
        if ($superAdminUser && ! $superAdminUser->hasRole('super admin')) {
            $superAdminUser->assignRole($superAdminRole);
        }
    }
}
