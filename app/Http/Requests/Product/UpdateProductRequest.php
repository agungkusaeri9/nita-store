<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,'.$this->route('product')->id],
            'description' => ['nullable', 'string'],
            'primary_image' => ['nullable', 'image', 'max:2048'],

            // Ecommerce fields
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,'.$this->route('product')->id],
            'weight' => ['required', 'integer', 'min:0'],

            // Flags
            'is_active' => ['boolean'],

            // Relations
            'variant_values' => ['nullable', 'array'],
            'variant_values.*' => ['exists:variant_values,id'],

            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'max:2048'],

            'deleted_images' => ['nullable', 'array'],
            'deleted_images.*' => ['exists:product_images,id'],
        ];
    }
}
