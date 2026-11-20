<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        $products = Product::all();

        // Sample placeholder SVG banners so images load nicely even without external file uploads
        Storage::disk('public')->makeDirectory('banners');

        $banners = [
            [
                'title' => 'Koleksi Eksklusif Batik Tulis Sutra Parang',
                'image' => 'banners/banner-sutra-parang.jpg',
                'target_type' => $categories->isNotEmpty() ? 'category' : 'none',
                'category_id' => $categories->first()?->id,
                'product_id' => null,
                'target_url' => null,
                'sort_order' => 1,
                'is_active' => true,
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(30),
                'description' => 'Promo peluncuran koleksi batik tulis sutra eksklusif warisan nusantara.',
            ],
            [
                'title' => 'Diskon Spesial Hari Batik Nasional 30%',
                'image' => 'banners/banner-promo-batik.jpg',
                'target_type' => $products->isNotEmpty() ? 'product' : 'none',
                'category_id' => null,
                'product_id' => $products->first()?->id,
                'target_url' => null,
                'sort_order' => 2,
                'is_active' => true,
                'start_date' => now()->subDays(2),
                'end_date' => now()->addDays(14),
                'description' => 'Potongan harga hingga 30% untuk produk batik pilihan pria dan wanita.',
            ],
            [
                'title' => 'Seragam Batik Kantor & Komunitas Custom',
                'image' => 'banners/banner-seragam-kantor.jpg',
                'target_type' => 'none',
                'category_id' => null,
                'product_id' => null,
                'target_url' => null,
                'sort_order' => 4,
                'is_active' => true,
                'start_date' => null,
                'end_date' => null,
                'description' => 'Layanan pemesanan batik seragam instansi, kantor, dan acara keluarga besar.',
            ],
            [
                'title' => 'Promo Spesial Weekend Sale (Nonaktif)',
                'image' => 'banners/banner-weekend-sale.jpg',
                'target_type' => $categories->count() > 1 ? 'category' : 'none',
                'category_id' => $categories->last()?->id,
                'product_id' => null,
                'target_url' => null,
                'sort_order' => 5,
                'is_active' => false,
                'start_date' => now()->subDays(20),
                'end_date' => now()->subDays(15),
                'description' => 'Banner arsip promo flash sale akhir pekan lalu.',
            ],
        ];

        foreach ($banners as $bannerData) {
            Banner::updateOrCreate(
                ['title' => $bannerData['title']],
                $bannerData
            );
        }
    }
}
