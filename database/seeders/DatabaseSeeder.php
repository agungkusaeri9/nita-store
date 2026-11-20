<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles & Permissions first
        $this->call(RolePermissionSeeder::class);

        // 2. Ensure Super Admin User exists and has super admin role
        $sa = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@batik.com',
                'password' => Hash::make('password'),
            ]
        );

        $superAdminRole = Role::where('name', 'super admin')->first();
        if ($superAdminRole && ! $sa->hasRole('super admin')) {
            $sa->assignRole($superAdminRole);
        }

        // 2b. Ensure default Admin User exists and has admin role
        $adminUser = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'email' => 'admin@batik.com',
                'password' => Hash::make('password'),
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && ! $adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
        }

        // 3. Seed locations (provinces, cities, subdistricts from RajaOngkir) and other core data
        $this->call([
            LocationSeeder::class,
            CourierSeeder::class,
            PaymentMethodSeeder::class,
            CategorySeeder::class,
            VariantTypeSeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
            // NotificationSeeder::class,
            ProductReviewSeeder::class,
            BannerSeeder::class,
            StoreSettingSeeder::class,
            FaqSeeder::class,
            // AppVersionSeeder::class,
            AppConfigSeeder::class,
            UserCartSeeder::class,
        ]);
    }
}
