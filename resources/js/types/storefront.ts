export interface StoreIdentity {
    name: string;
    logo_url: string | null;
    description: string | null;
    address: string | null;
    phone: string | null;
    whatsapp: string | null;
    email: string | null;
    website: string | null;
    facebook_url: string | null;
    twitter_url: string | null;
    instagram_url: string | null;
    privacy_policy: string | null;
    policy: string | null;
    cancellation_policy: string | null;
}

export interface StoreBanner {
    id: number;
    title: string;
    description: string | null;
    image_url: string | null;
    target_type: 'product' | 'category' | 'url' | 'none' | string;
    target_url: string | null;
    product_id: number | null;
    product_name: string | null;
    product_slug: string | null;
    category_id: number | null;
    category_name: string | null;
    category_slug: string | null;
}

export interface StoreCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image_url: string | null;
    products_count: number;
}

export interface StoreProductVariant {
    type: string;
    values: string[];
}

export interface StoreProductImage {
    id: number;
    image_url: string | null;
    sort_order: number;
}

export interface StoreProduct {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    stock: number;
    sku: string | null;
    weight: number;
    is_active: boolean;
    primary_image_url: string | null;
    category: {
        id: number;
        name: string;
        slug: string;
    } | null;
    gallery_images: StoreProductImage[];
    variants: StoreProductVariant[];
    average_rating: number;
    reviews_count: number;
    sold_count: number;
}

export interface StoreVariantValue {
    id: number;
    value: string;
}

export interface StoreVariantType {
    id: number;
    name: string;
    slug: string;
    values: StoreVariantValue[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedProducts {
    data: StoreProduct[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export interface StoreReview {
    id: number;
    rating: number;
    comment: string | null;
    customer_name: string;
    customer_avatar: string | null;
    is_verified_purchase: boolean;
    created_at: string | null;
}

export interface StoreRatingBreakdownItem {
    count: number;
    percentage: number;
}

export interface StoreRatingStats {
    average_rating: number;
    total_count: number;
    breakdown: Record<number, StoreRatingBreakdownItem>;
}

export interface PaginatedReviews {
    data: StoreReview[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}
