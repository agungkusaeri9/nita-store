<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\VariantType;
use App\Models\VariantValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    private $importedCount = 0;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                if (! isset($row['name']) || empty($row['name']) || ! isset($row['price'])) {
                    continue; // Skip invalid rows
                }

                // 1. Resolve Category
                $categoryId = null;
                if (! empty($row['category'])) {
                    $category = Category::firstOrCreate([
                        'slug' => Str::slug($row['category']),
                    ], [
                        'name' => $row['category'],
                        'is_active' => true,
                    ]);
                    $categoryId = $category->id;
                }

                // 2. Prepare Product Data
                $slug = Str::slug($row['name']);

                // Ensure slug uniqueness
                $originalSlug = $slug;
                $counter = 1;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $originalSlug.'-'.$counter;
                    $counter++;
                }

                $product = Product::create([
                    'category_id' => $categoryId,
                    'name' => $row['name'],
                    'slug' => $slug,
                    'description' => $row['description'] ?? null,
                    'price' => floatval($row['price']),
                    'stock' => intval($row['stock'] ?? 0),
                    'sku' => $row['sku'] ?? null,
                    'weight' => intval($row['weight'] ?? 0),
                    'is_active' => isset($row['is_active']) ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
                ]);

                // 3. Resolve Variants
                if (! empty($row['variants'])) {
                    $variantGroups = explode(';', $row['variants']);
                    $variantValueIds = [];

                    foreach ($variantGroups as $group) {
                        $parts = explode(':', $group);
                        if (count($parts) === 2) {
                            $typeName = trim($parts[0]);
                            $values = explode(',', $parts[1]);

                            $variantType = VariantType::firstOrCreate([
                                'name' => $typeName,
                            ], [
                                'is_active' => true,
                            ]);

                            foreach ($values as $valueName) {
                                $valueName = trim($valueName);
                                if (! empty($valueName)) {
                                    $variantValue = VariantValue::firstOrCreate([
                                        'variant_type_id' => $variantType->id,
                                        'value' => $valueName,
                                    ]);
                                    $variantValueIds[] = $variantValue->id;
                                }
                            }
                        }
                    }

                    if (! empty($variantValueIds)) {
                        $product->variantValues()->sync($variantValueIds);
                    }
                }

                $this->importedCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
