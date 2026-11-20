<?php

namespace App\Http\Resources;

use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    protected static ?StoreSetting $cachedStoreSetting = null;

    protected static function resolveStoreSetting(): ?StoreSetting
    {
        if (static::$cachedStoreSetting === null) {
            static::$cachedStoreSetting = StoreSetting::first();
        }

        return static::$cachedStoreSetting;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $store = static::resolveStoreSetting();
        $soldCount = (int) OrderDetail::query()
            ->where('product_id', $this->id)
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['processing', 'success', 'completed']))
            ->sum('quantity');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'stock' => (int) $this->stock,
            'sku' => $this->sku,
            'weight' => (int) $this->weight,
            'sold_count' => $soldCount,
            'is_active' => (bool) $this->is_active,
            'primary_image_url' => $this->primary_image_url,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'store' => $store ? [
                'id' => $store->id,
                'name' => $store->name,
                'logo_url' => $store->logo_url,
                'description' => $store->description,
                'address' => $store->address,
                'phone' => $store->phone,
                'whatsapp' => $store->whatsapp,
                'email' => $store->email,
                'website' => $store->website,
            ] : null,
            'gallery_images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'image_url' => $img->image_url,
                'sort_order' => $img->sort_order,
            ])),
            'variants' => $this->whenLoaded('variantValues', fn () => $this->variantValues->map(fn ($val) => [
                'id' => $val->id,
                'type' => $val->variantType?->name,
                'value' => $val->value,
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
