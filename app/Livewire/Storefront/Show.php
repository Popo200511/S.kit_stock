<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    // Deliberately just the id, not `public Product $product` — a public
    // Eloquent-model property gets serialized whole into the page's
    // wire:snapshot payload (visible in the response), which would leak
    // cost/wholesale_price/reorder_point/sku even if the Blade never echoes
    // them. Keeping only the id public and re-querying safe columns in
    // render() means those fields are never fetched for this page at all.
    public int $productId;

    public function mount(int $product): void
    {
        $this->productId = $product;
    }

    public function render()
    {
        $product = Product::query()
            ->where('active', true)
            ->select(['id', 'name', 'size', 'description', 'category_id', 'photo_path', 'price'])
            ->selectRaw('(stock > 0) as in_stock')
            ->with('category:id,name')
            ->with(['variants' => fn ($q) => $q->where('active', true)->orderBy('sort_order')
                ->select(['id', 'product_id', 'label', 'unit_qty', 'price'])])
            ->findOrFail($this->productId);

        // Exact stock is only ever used here, server-side, to derive a per-size boolean —
        // it never reaches the view/props (which only ever gets true/false per size),
        // same discipline as the plain (stock > 0) boolean above for products with no sizes.
        $baseStock = (float) Product::where('id', $this->productId)->value('stock');

        $sizes = $product->variants->isNotEmpty()
            ? $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->label,
                'price' => (float) $v->price,
                'in_stock' => $baseStock >= (float) $v->unit_qty,
            ])->values()
            : collect([[
                'id' => 0,
                'label' => null,
                'price' => (float) $product->price,
                'in_stock' => (bool) $product->in_stock,
            ]]);

        $minPrice = $sizes->min('price');
        $priceLabel = $sizes->count() > 1
            ? 'เริ่มต้น '.number_format($minPrice, 2).' บาท'
            : number_format($minPrice, 2).' บาท';

        // สินค้าที่คล้ายกัน — สุ่มหยิบจากหมวดหมู่เดียวกัน ให้ลูกค้ามีอะไรดูต่อ
        // แทนที่จะจบทางที่หน้าสินค้าชิ้นเดียว (เว้นว่างถ้าสินค้านี้ไม่มีหมวดหมู่)
        $related = $product->category_id
            ? Product::query()
                ->where('active', true)
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->select(['id', 'name', 'size', 'category_id', 'photo_path', 'price'])
                ->selectRaw('(stock > 0) as in_stock')
                ->withCount(['variants' => fn ($q) => $q->where('active', true)])
                ->addSelect(['min_variant_price' => ProductVariant::query()
                    ->selectRaw('MIN(price)')
                    ->whereColumn('product_id', 'products.id')
                    ->where('active', true),
                ])
                ->inRandomOrder()
                ->limit(8)
                ->get()
            : collect();

        $ogImage = $product->photo_path ? asset('storage/'.$product->photo_path) : null;

        // ใช้รายละเอียดสินค้าจริงถ้าพนักงานกรอกไว้ (สั้นลงให้พอดี meta description) ไม่งั้น
        // ใช้ข้อความสรุปอัตโนมัติจากชื่อ+หมวดหมู่แทนเหมือนเดิม
        $autoDescription = $product->name.' '.$priceLabel
            .($product->category ? ' · หมวดหมู่ '.$product->category->name : '')
            .' — ทักไลน์หรือโทรสั่งซื้อได้ทันที';
        $metaDescription = $product->description
            ? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $product->description)), 160)
            : $autoDescription;

        // JSON-LD (schema.org Product) — ให้ Google โชว์ราคา/สถานะสินค้าตรงในผลค้นหาได้เลย,
        // ต่อยอดจาก Open Graph ด้านบน (คนละมาตรฐาน คนละผู้บริโภค: OG สำหรับพรีวิวตอนแชร์,
        // JSON-LD สำหรับเครื่องมือค้นหา)
        $jsonLd = json_encode([
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $metaDescription,
            'image' => $ogImage ? [$ogImage] : [],
            'offers' => [
                '@type' => 'Offer',
                'url' => url()->current(),
                'priceCurrency' => 'THB',
                'price' => number_format($minPrice, 2, '.', ''),
                'availability' => $sizes->contains('in_stock', true)
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('livewire.storefront.show', [
            'product' => $product,
            'sizes' => $sizes,
            'hasSizes' => $sizes->count() > 1,
            'related' => $related,
            'jsonLd' => $jsonLd,
        ])->layout('components.layouts.storefront', [
            'title' => $product->name.' · '.config('shop.name'),
            'description' => $metaDescription,
            'image' => $ogImage,
            'canonical' => route('shop.product', $product->id),
        ]);
    }
}
