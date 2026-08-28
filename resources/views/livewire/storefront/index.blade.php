<div class="max-w-[1100px] mx-auto px-4 sm:px-6 py-6 flex flex-col gap-5">

    {{-- Hero --}}
    <div class="bg-accent-tint rounded-2xl px-5 py-7 sm:px-8 sm:py-9 flex flex-col gap-1.5 text-center">
        <h1 class="text-[22px] sm:text-[26px] font-semibold text-accent-ink tracking-tight">สินค้าของร้าน {{ config('shop.name') }}</h1>
        <p class="text-[13.5px] text-text3">ดูรายการสินค้า แล้วทักไลน์หรือโทรสั่งซื้อได้ทันที</p>
    </div>

    {{-- Search + category chips: sticky right under the header, like an extended navbar --}}
    <div class="sticky top-16 z-30 -mx-4 sm:-mx-6 px-4 sm:px-6 py-3 bg-surface2/95 backdrop-blur border-b border-line flex flex-col gap-3">
        <div class="flex gap-2">
            <div class="flex-1 min-w-0 flex items-center gap-2 bg-surface border border-border2 rounded-[10px] px-3.5 py-2.5 shadow-sm focus-within:border-accent">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-muted3 shrink-0" stroke-width="1.9" stroke-linecap="round"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3"></path></svg>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="ค้นหาชื่อสินค้า"
                    class="flex-1 min-w-0 text-[14px] border-0 p-0 focus:ring-0 focus:outline-none bg-transparent">
            </div>
            <select wire:model.live="sort"
                class="shrink-0 bg-surface border border-border2 rounded-[10px] pl-3 pr-8 py-2.5 text-[13px] text-text2 shadow-sm focus:border-accent focus:ring-0 focus:outline-none">
                <option value="name">เรียงตามชื่อ</option>
                <option value="price_asc">ราคา: น้อย→มาก</option>
                <option value="price_desc">ราคา: มาก→น้อย</option>
            </select>
        </div>

        <label class="flex items-center gap-2 text-[12.5px] text-text3 cursor-pointer select-none w-fit">
            <input type="checkbox" wire:model.live="inStockOnly" class="w-[16px] h-[16px] rounded-[5px] border-border4 text-accent focus:ring-accent focus:outline-none">
            แสดงเฉพาะที่มีของ
        </label>

        <div class="min-w-0 flex gap-2 overflow-x-auto pb-1">
            <button wire:click="selectCategory(null)"
                @class(['shrink-0 px-4 py-2 rounded-full text-[12.5px] font-medium whitespace-nowrap border', 'bg-accent text-white border-accent' => $categoryId === null, 'bg-surface text-text3 border-border4' => $categoryId !== null])>
                ทั้งหมด
            </button>
            @foreach ($categories as $cat)
                <button wire:click="selectCategory({{ $cat->id }})"
                    @class(['shrink-0 px-4 py-2 rounded-full text-[12.5px] font-medium whitespace-nowrap border', 'bg-accent text-white border-accent' => $categoryId === $cat->id, 'bg-surface text-text3 border-border4' => $categoryId !== $cat->id])>
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Product grid --}}
    @if ($products->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5">
            @foreach ($products as $product)
                <a href="{{ route('shop.product', $product->id) }}" wire:navigate
                    class="bg-surface border border-border rounded-[14px] overflow-hidden shadow-sm hover:shadow-md hover:border-accent-border transition flex flex-col">
                    <div class="aspect-square bg-sunken flex items-center justify-center overflow-hidden">
                        @if ($product->photo_path)
                            <img src="{{ asset('storage/'.$product->photo_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-[28px] font-semibold text-muted3">{{ mb_substr($product->name, 0, 1) }}</span>
                        @endif
                    </div>
                    <div class="p-3 flex flex-col gap-1">
                        <span class="text-[13px] font-medium leading-snug line-clamp-2">{{ $product->name }}</span>
                        @if ($product->size)
                            <span class="text-[11px] text-muted2">{{ $product->size }}</span>
                        @endif
                        <div class="flex items-center justify-between gap-2 mt-1">
                            <span class="text-[14px] font-semibold text-accent-ink">
                                @if ($product->variants_count > 1)
                                    เริ่มต้น {{ number_format((float) $product->min_variant_price, 2) }}
                                @else
                                    {{ number_format((float) $product->price, 2) }}
                                @endif
                                บาท
                            </span>
                            @unless ($product->in_stock)
                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-danger-tint text-danger whitespace-nowrap">หมดชั่วคราว</span>
                            @endunless
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($products->hasMorePages())
            <div wire:key="load-more-{{ $perPage }}" x-data x-init="
                const io = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting) { $wire.loadMore(); io.disconnect(); }
                }, { rootMargin: '300px' });
                io.observe($el);
            " class="py-3.5 text-center text-[12px] text-muted2">กำลังโหลดเพิ่มเติม...</div>
        @endif
    @else
        <div class="py-16 text-center text-[13.5px] text-muted2">ไม่พบสินค้าที่ค้นหา</div>
    @endif
</div>
