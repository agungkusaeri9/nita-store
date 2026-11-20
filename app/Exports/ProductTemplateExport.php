<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'name',
            'category',
            'price',
            'stock',
            'sku',
            'weight',
            'description',
            'is_active',
            'variants',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Kemeja Batik Mega Mendung', // name
                'Batik Pria', // category
                150000, // price
                50, // stock
                'B001', // sku
                200, // weight
                'Kemeja halus premium', // description
                1, // is_active
                'Warna:Merah,Biru;Ukuran:M,L,XL', // variants
            ],
            [
                'Kain Batik Sogan',
                'Kain',
                200000,
                30,
                'K002',
                500,
                'Kain premium untuk seragam',
                1,
                'Tipe:Katun,Sutra',
            ],
        ];
    }
}
