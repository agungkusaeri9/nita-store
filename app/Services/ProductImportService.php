<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\VariantType;
use App\Models\VariantValue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImportService
{
    public function importFromCsv(UploadedFile $file)
    {
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle); // First row as header

        $importedCount = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Combine header with row values
                // Handle cases where row might have fewer columns than header
                $data = [];
                foreach ($header as $index => $columnName) {
                    $columnName = strtolower(trim($columnName));
                    $data[$columnName] = isset($row[$index]) ? trim($row[$index]) : null;
                }

                // Required fields check
                if (empty($data['name']) || empty($data['price'])) {
                    continue; // Skip invalid rows
                }

                // 1. Resolve Category
                $categoryId = null;
                if (! empty($data['category'])) {
                    $category = Category::firstOrCreate([
                        'slug' => Str::slug($data['category']),
                    ], [
                        'name' => $data['category'],
                        'is_active' => true,
                    ]);
                    $categoryId = $category->id;
                }

                // 2. Prepare Product Data
                $slug = Str::slug($data['name']);

                // Ensure slug uniqueness
                $originalSlug = $slug;
                $counter = 1;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $originalSlug.'-'.$counter;
                    $counter++;
                }

                $product = Product::create([
                    'category_id' => $categoryId,
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                    'price' => floatval($data['price']),
                    'stock' => intval($data['stock'] ?? 0),
                    'sku' => $data['sku'] ?? null,
                    'weight' => intval($data['weight'] ?? 0),
                    'is_active' => isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
                ]);

                // 3. Resolve Variants (Format: "Warna:Merah,Biru;Ukuran:M,L")
                if (! empty($data['variants'])) {
                    $variantGroups = explode(';', $data['variants']);
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

                $importedCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            fclose($handle);
        }

        return $importedCount;
    }
}
