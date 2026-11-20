<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\VariantValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryDefinitions = [
            'sepatu' => [
                'name' => 'Sepatu',
                'description' => 'Koleksi sepatu sneakers, running, loafers, boots, dan sandal kasual berkualitas.',
            ],
            'baju' => [
                'name' => 'Baju',
                'description' => 'Pilihan kemeja, blouse, tunik, dan pakaian atasan kasual maupun formal.',
            ],
            'celana' => [
                'name' => 'Celana',
                'description' => 'Beragam celana chino, jeans, cargo, jogger, dan kulot nyaman dipakai.',
            ],
            'kaos' => [
                'name' => 'Kaos',
                'description' => 'Kaos polos cotton combed, oversized tee, polo shirt, dan kaos harian.',
            ],
            'topi' => [
                'name' => 'Topi',
                'description' => 'Topi baseball cap, bucket hat, snapback, beanie, dan topi outdoor.',
            ],
            'lainnya' => [
                'name' => 'Lainnya',
                'description' => 'Jaket, hoodie, tas ransel, dompet, ikat pinggang, dan aksesoris pelengkap.',
            ],
        ];

        $categories = [];
        foreach ($categoryDefinitions as $slug => $data) {
            $categories[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }

        $catalogByCategory = [
            'sepatu' => [
                ['Sepatu Sneakers Kanvas Low White Classic', 289000, 35, 'SPT-001', 750],
                ['Sepatu Running Ultralight Mesh Abu-Abu', 425000, 24, 'SPT-002', 680],
                ['Sepatu Loafers Kulit Sintetis Coklat Tua', 375000, 18, 'SPT-003', 820],
                ['Sepatu Slip-On Kanvas Hitam Kasual', 235000, 40, 'SPT-004', 650],
                ['Sepatu Boots Kulit Chelsea Hitam', 690000, 12, 'SPT-005', 1100],
                ['Sepatu Sneakers Chunky Retro Krem', 460000, 22, 'SPT-006', 850],
                ['Sepatu Trail Running Outdoor Waterproof', 780000, 10, 'SPT-007', 900],
                ['Sepatu Badminton & Indoor Court Pro', 520000, 16, 'SPT-008', 720],
                ['Sepatu Pantofel Formal Pria Kulit Mengkilap', 495000, 15, 'SPT-009', 880],
                ['Sepatu Flat Shoes Wanita Pita Minimalis', 189000, 32, 'SPT-010', 450],
                ['Sepatu Sneakers High Top Kanvas Navy', 330000, 20, 'SPT-011', 800],
                ['Sepatu Basket Mid Cut Cushion Sport', 845000, 8, 'SPT-012', 950],
                ['Sepatu Wedges Kasual Wanita Sol Empuk', 265000, 25, 'SPT-013', 620],
                ['Sandal Gunung Outdoor Strap Nylon Hitam', 175000, 45, 'SPT-014', 520],
                ['Sandal Selop Slide EVA Empuk Minimalis', 115000, 60, 'SPT-015', 380],
                ['Sepatu Training Gym Breathable Knit', 399000, 0, 'SPT-016', 690],
                ['Sepatu Leather Sneakers Premium Putih Polos', 1150000, 7, 'SPT-017', 860],
            ],
            'baju' => [
                ['Kemeja Oxford Lengan Panjang Putih Bersih', 245000, 30, 'BJU-001', 320],
                ['Kemeja Flanel Kotak-Kotak Merah Hitam', 229000, 28, 'BJU-002', 360],
                ['Kemeja Linen Lengan Pendek Beige Santai', 215000, 35, 'BJU-003', 260],
                ['Kemeja Denim Chambray Biru Muda', 279000, 20, 'BJU-004', 410],
                ['Blouse Wanita Kerah V-Neck Sifon Dusty Pink', 195000, 25, 'BJU-005', 220],
                ['Tunik Rayon Viscose Motif Floral Lembut', 239000, 22, 'BJU-006', 290],
                ['Kemeja Kerja Slim Fit Navy Elegan', 259000, 27, 'BJU-007', 310],
                ['Kemeja Bowling Retro Kerah Camp Hitam', 185000, 34, 'BJU-008', 240],
                ['Blouse Batik Modern Lengan Balon Wanita', 265000, 18, 'BJU-009', 270],
                ['Kemeja Safari Kantong Dua Khaki', 275000, 16, 'BJU-010', 350],
                ['Kemeja Poplin Stretch Abu-Abu Muda', 239000, 24, 'BJU-011', 290],
                ['Kemeja Tunik Polos Kancing Depan Sage', 249000, 19, 'BJU-012', 310],
                ['Baju Koko Lengan Panjang Katun Toyobo', 225000, 30, 'BJU-013', 330],
                ['Kemeja Oversize Wanita Katun Poplin Biru', 219000, 26, 'BJU-014', 280],
                ['Kemeja Corduroy Tebal Coklat Karamel', 315000, 14, 'BJU-015', 450],
                ['Blouse Knit Lengan Panjang Cream Elegan', 209000, 0, 'BJU-016', 320],
                ['Kemeja Batik Sutra Eksklusif Lengan Panjang', 650000, 9, 'BJU-017', 360],
            ],
            'celana' => [
                ['Celana Chino Slim Fit Khaki Stretch', 249000, 40, 'CLN-001', 420],
                ['Celana Jeans Denim Biru Tua Selvedge', 389000, 25, 'CLN-002', 580],
                ['Celana Cargo Panjang Banyak Saku Olive', 285000, 30, 'CLN-003', 490],
                ['Celana Jogger Fleece Sporty Abu Tua', 189000, 45, 'CLN-004', 410],
                ['Celana Kulot Highwaist Wanita Linen Hitam', 215000, 32, 'CLN-005', 340],
                ['Celana Pendek Chino Pria Navy Santai', 165000, 50, 'CLN-006', 280],
                ['Celana Bahan Formal Ankle Pants Charcoal', 275000, 22, 'CLN-007', 380],
                ['Celana Jeans Ripped Wash Biru Muda', 329000, 18, 'CLN-008', 540],
                ['Celana Training Parasut Ringan Lari', 179000, 35, 'CLN-009', 260],
                ['Celana Wide Leg Denim Wanita Retro', 310000, 20, 'CLN-010', 520],
                ['Celana Pendek Cargo Outdoor Quick Dry', 195000, 28, 'CLN-011', 270],
                ['Celana Legging Sport High Compression', 155000, 38, 'CLN-012', 220],
                ['Celana Chino Regular Fit Hitam Pekat', 245000, 34, 'CLN-013', 430],
                ['Celana Baggy Carpenter Kanvas Coklat', 339000, 15, 'CLN-014', 560],
                ['Celana Pleated Trousers Wanita Cream', 259000, 21, 'CLN-015', 360],
                ['Celana Jeans Straight Fit Raw Indigo', 415000, 0, 'CLN-016', 600],
                ['Celana Pendek Pantai Boardshort Motif Tropis', 135000, 42, 'CLN-017', 210],
            ],
            'kaos' => [
                ['Kaos Polos Cotton Combed 30s Hitam', 79000, 80, 'KOS-001', 180],
                ['Kaos Polos Cotton Combed 30s Putih', 79000, 75, 'KOS-002', 180],
                ['Kaos Oversized Heavyweight 24s Sage Green', 139000, 45, 'KOS-003', 260],
                ['Kaos Polo Kerah Pique Lacoste Navy', 169000, 36, 'KOS-004', 240],
                ['Kaos Lengan Panjang Ribbed Crewneck Abu', 119000, 40, 'KOS-005', 230],
                ['Kaos Henley Kancing Tiga Katun Charcoal', 129000, 30, 'KOS-006', 220],
                ['Kaos Graphic Streetwear Sablon Plastisol', 159000, 28, 'KOS-007', 250],
                ['Kaos Raglan Dua Warna Putih Lengan Maroon', 105000, 35, 'KOS-008', 210],
                ['Kaos Crop Top Wanita Cotton Ribbed Pink', 95000, 42, 'KOS-009', 150],
                ['Kaos Sport Dry-Fit Running Biru Elektrik', 125000, 50, 'KOS-010', 160],
                ['Kaos Pocket Tee Minimalis Coklat Kopi', 115000, 32, 'KOS-011', 210],
                ['Kaos Stripped Garis-Garis Breton Navy Putih', 135000, 26, 'KOS-012', 220],
                ['Kaos Waffle Knit Tekstur Lembut Cream', 149000, 24, 'KOS-013', 270],
                ['Kaos Tie Dye Pastel Handmade Unik', 145000, 18, 'KOS-014', 220],
                ['Kaos V-Neck Basic Bamboo Cotton Hitam', 99000, 48, 'KOS-015', 175],
                ['Kaos Boxy Fit Heavy Cotton Washed Grey', 165000, 0, 'KOS-016', 280],
                ['Kaos Polo Striped Sporty Merah Putih', 179000, 22, 'KOS-017', 250],
            ],
            'topi' => [
                ['Topi Baseball Cap Polos Twill Hitam', 65000, 60, 'TPI-001', 120],
                ['Topi Bucket Hat Bolak-Balik Kanvas Cream', 85000, 45, 'TPI-002', 130],
                ['Topi Snapback Flat Visor Streetwear Navy', 115000, 30, 'TPI-003', 150],
                ['Topi Trucker Jaring Mesh Outdoor Hijau Army', 75000, 50, 'TPI-004', 125],
                ['Topi Kupluk Beanie Rajut Hangat Abu Tua', 69000, 40, 'TPI-005', 110],
                ['Topi Dad Hat Corduroy Vintage Coklat', 99000, 28, 'TPI-006', 130],
                ['Topi Rimba Gunung Anti UV Tali Dagu', 95000, 35, 'TPI-007', 140],
                ['Topi Visor Olahraga Lari & Tenis Putih', 79000, 32, 'TPI-008', 95],
                ['Topi Newsboy Flat Cap Wol Klasik', 145000, 18, 'TPI-009', 160],
                ['Topi Baseball Bordir Minimalis Dusty Rose', 89000, 36, 'TPI-010', 120],
                ['Topi Fedora Pantai Anyaman Jerami Lebar', 135000, 20, 'TPI-011', 180],
                ['Topi Bucket Hat Denim Washed Biru Muda', 99000, 25, 'TPI-012', 140],
                ['Topi Cycling Cap Tipis Bersepeda Hitam', 85000, 29, 'TPI-013', 80],
                ['Topi Beret Wanita Bahan Wol Merah Marun', 110000, 22, 'TPI-014', 120],
                ['Topi Waterproof Nylon Camp Cap Olive', 129000, 16, 'TPI-015', 115],
                ['Topi Baseball Kulit Sintetis Premium Hitam', 159000, 0, 'TPI-016', 150],
            ],
            'lainnya' => [
                ['Jaket Hoodie Zipper Fleece Tebal Hitam', 279000, 30, 'AKS-001', 620],
                ['Jaket Denim Trucker Klasik Biru Indigo', 399000, 18, 'AKS-002', 780],
                ['Jaket Windbreaker Parasut Anti Angin Navy', 289000, 25, 'AKS-003', 390],
                ['Cardigan Rajut Wanita Oversized Pastel Pink', 229000, 24, 'AKS-004', 440],
                ['Tas Ransel Laptop 15 Inch Water Resistant', 349000, 28, 'AKS-005', 650],
                ['Tas Selempang Sling Bag Kanvas Kasual', 149000, 45, 'AKS-006', 320],
                ['Tote Bag Kanvas Tebal Resleting Natural', 89000, 55, 'AKS-007', 260],
                ['Dompet Kulit Asli Pria Bifold Minimalis', 265000, 30, 'AKS-008', 180],
                ['Ikat Pinggang Kulit Gesper Otomatis Hitam', 145000, 40, 'AKS-009', 240],
                ['Kacamata Hitam Polarized UV400 Klasik', 169000, 35, 'AKS-010', 110],
                ['Jam Tangan Analog Strap Kulit Minimalis', 485000, 15, 'AKS-011', 210],
                ['Paket 3 Pasang Kaos Kaki Katun Panjang', 59000, 70, 'AKS-012', 140],
                ['Waist Bag Sporty Ringan Banyak Kompartemen', 129000, 38, 'AKS-013', 230],
                ['Syal Pashmina Ceruty Babydoll Premium', 75000, 50, 'AKS-014', 160],
                ['Payung Lipat Otomatis Anti UV Ringkas', 115000, 32, 'AKS-015', 340],
                ['Tas Travel Duffel Bag Kabin Waterproof', 540000, 0, 'AKS-016', 890],
            ],
        ];

        $variantValues = VariantValue::pluck('id');

        foreach ($catalogByCategory as $categorySlug => $items) {
            $category = $categories[$categorySlug];

            foreach ($items as [$name, $price, $stock, $sku, $weight]) {
                $slug = Str::slug($name);

                $product = Product::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'description' => "{$name} hadir dengan material pilihan berkualitas tinggi, jahitan rapi, dan desain modern yang nyaman dipakai untuk aktivitas sehari-hari maupun acara khusus.",
                        'price' => $price,
                        'stock' => $stock,
                        'sku' => $sku,
                        'weight' => $weight,
                        'is_active' => true,
                    ]
                );

                if ($variantValues->isNotEmpty() && $product->variantValues()->count() === 0) {
                    $randomIds = $variantValues
                        ->random(min(3, $variantValues->count()))
                        ->all();
                    $product->variantValues()->sync($randomIds);
                }
            }
        }
    }
}
