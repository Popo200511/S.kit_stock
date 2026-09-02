<?php

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    /** 'name' | 'price_asc' | 'price_desc' */
    #[Url(as: 'sort')]
    public string $sort = 'name';

    public bool $inStockOnly = false;

    public int $perPage = 24;

    public function updatingSearch(): void
    {
        $this->perPage = 24;
    }

    public function updatingSort(): void
    {
        $this->perPage = 24;
    }

    public function updatingInStockOnly(): void
    {
        $this->perPage = 24;
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->perPage = 24;
    }

    public function loadMore(): void
    {
        $this->perPage += 24;
    }

    /**
     * Selects only customer-safe columns at the query level — cost,
     * wholesale_price, reorder_point, suggested_reorder_qty, and sku never
     * leave the database for this page. Exact stock count is likewise never
     * fetched; only a boolean "is anything left" flag is.
     */
    protected function baseQuery()
    {
        return Product::query()
            ->where('active', true)
            ->select(['id', 'name', 'size', 'category_id', 'photo_path', 'price'])
            ->selectRaw('(stock > 0) as in_stock')
            ->with('category:id,name')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->inStockOnly, fn ($q) => $q->where('stock', '>', 0));
    }

    public function render()
    {
        $query = $this->baseQuery();
        match ($this->sort) {
            // ตามที่ตกลงกับลูกค้า — หน้าร้านไม่โชว์ราคาตามขนาดที่แบ่งขาย (variants) แล้ว
            // เรียงตามราคาสินค้าหลัก (ราคากระสอบ) ตรงๆ แทน
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            default => $query->orderBy('name'),
        };
        $products = $query->paginate($this->perPage, ['*'], 'page', 1);

        $categories = Category::query()
            ->whereHas('products', fn ($q) => $q->where('active', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.storefront.index', [
            'products' => $products,
            'categories' => $categories,
        ])->layout('components.layouts.storefront', [
            'title' => 'สินค้าของร้าน '.config('shop.name'),
            'description' => 'ดูรายการสินค้าของร้าน '.config('shop.name').' แล้วทักไลน์หรือโทรสั่งซื้อได้ทันที',
            'canonical' => route('shop.index'),
        ]);
    }
}
