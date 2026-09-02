<div class="max-w-[1100px] mx-auto px-4 sm:px-6 py-6 flex flex-col gap-5">

    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl" style="background: linear-gradient(135deg, var(--login-accent-tint) 0%, #fbe4ea 100%);">
        {{-- decorative soft blobs, purely visual — hidden from screen readers --}}
        <div aria-hidden="true" class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/40"></div>
        <div aria-hidden="true" class="absolute -bottom-20 -left-10 w-56 h-56 rounded-full bg-login-accent/10"></div>

        <div class="relative flex flex-col lg:flex-row items-center gap-6 px-5 py-8 sm:px-9 sm:py-11">
            <div data-aos="fade-right" class="flex-1 min-w-0 flex flex-col gap-3 text-center lg:text-left items-center lg:items-start">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-login-accent-ink text-[11.5px] font-medium shadow-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 7.2H22l-6 4.6 2.3 7.2-6.3-4.6-6.3 4.6 2.3-7.2-6-4.6h7.6z"></path></svg>
                    อาหารสัตว์ดี มีคุณภาพ
                </span>
                <h1 class="text-[26px] sm:text-[36px] font-semibold text-login-accent-ink tracking-tight leading-tight">
                    ครบคุณค่า<br>ถูกใจน้องที่บ้าน
                </h1>
                <p class="text-[13.5px] sm:text-[14.5px] text-text3 max-w-[420px]">
                    คัดสรรอาหารสัตว์และของใช้คู่ฟาร์มคุณภาพดี เพื่อสุขภาพที่แข็งแรงและมีความสุขของสัตว์เลี้ยงและปศุสัตว์ของคุณ
                </p>

                {{-- Benefit chips --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 w-full max-w-[440px] mt-1">
                    @foreach ([
                        ['icon' => '🌿', 'title' => 'วัตถุดิบคุณภาพ', 'sub' => 'ปลอดภัย'],
                        ['icon' => '🐾', 'title' => 'ย่อยง่าย', 'sub' => 'ขับถ่ายเป็นก้อน'],
                        ['icon' => '🛡️', 'title' => 'เสริมภูมิคุ้มกัน', 'sub' => 'แข็งแรง'],
                        ['icon' => '❤️', 'title' => 'สัตว์เลี้ยงชอบ', 'sub' => 'กินอร่อย'],
                    ] as $benefit)
                        <div class="flex flex-col items-center lg:items-start gap-1 text-center lg:text-left">
                            <span class="text-[17px] leading-none">{{ $benefit['icon'] }}</span>
                            <span class="text-[11px] font-semibold text-text2 leading-tight">{{ $benefit['title'] }}</span>
                            <span class="text-[10px] text-muted2 leading-tight">{{ $benefit['sub'] }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2.5 mt-2">
                    @if (config('shop.line_url'))
                        <a href="{{ config('shop.line_url') }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[10px] bg-[#06C755] text-white text-[13px] font-semibold shadow-sm hover:opacity-90">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 5.94 2 10.8c0 4.36 3.58 8.02 8.42 8.72.33.07.78.22.89.5.1.26.07.66.03.92l-.14.87c-.04.26-.2 1 .88.55s5.8-3.42 7.92-5.86C21.55 14.05 22 12.47 22 10.8 22 5.94 17.52 2 12 2z"></path></svg>
                            ทักไลน์สั่งซื้อ
                        </a>
                    @endif
                    @if (config('shop.phone'))
                        <a href="tel:{{ config('shop.phone') }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[10px] bg-login-accent text-white text-[13px] font-semibold shadow-sm hover:bg-login-accent-hover">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            โทรเลย {{ config('shop.phone') }}
                        </a>
                    @endif
                </div>
            </div>

            <div data-aos="fade-left" data-aos-delay="100" class="relative shrink-0 w-[170px] h-[170px] sm:w-[220px] sm:h-[220px]">
                <span aria-hidden="true" class="absolute -top-1 -left-3 text-login-accent text-[26px]">❤</span>
                <span aria-hidden="true" class="absolute -bottom-1 -right-3 text-login-accent/40 text-[20px]">♡</span>
                <div class="w-full h-full rounded-full bg-white/70 p-2.5 shadow-inner">
                    <img src="{{ asset('images/login-hero-dog.png') }}" alt="" aria-hidden="true" class="w-full h-full rounded-full object-cover border-4 border-white shadow-md">
                </div>
            </div>
        </div>
    </div>

    {{-- Category quick-shop --}}
    @if ($categories->count() > 0)
        <div data-aos="fade-up" class="flex gap-3 overflow-x-auto pb-1 -mx-4 sm:-mx-6 px-4 sm:px-6">
            <button wire:click="selectCategory(null)" wire:key="quickcat-all"
                class="shrink-0 w-[84px] flex flex-col items-center gap-1.5 group">
                <span @class(['w-14 h-14 rounded-2xl flex items-center justify-center transition ring-2 ring-transparent group-hover:ring-login-accent/40 bg-chip text-text3', 'ring-login-accent' => $categoryId === null])>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>
                </span>
                <span @class(['text-[11px] text-center leading-tight', 'font-semibold text-login-accent' => $categoryId === null, 'text-text3' => $categoryId !== null])>ทั้งหมด</span>
            </button>
            @foreach ($categories as $i => $cat)
                @php
                    $catColors = ['bg-login-accent-tint text-login-accent', 'bg-warn-tint text-warn', 'bg-danger-tint text-danger'];
                    // ตัดคำนำหน้าที่ซ้ำกันบ่อย (เช่น "อาหาร...") ออกก่อน จะได้ตัวอักษรที่ต่างกันจริง
                    // ไม่ใช่ "อ" ซ้ำๆ กันเกือบทุกวง
                    $iconLabel = mb_substr(preg_replace('/^(อาหาร)/u', '', $cat->name) ?: $cat->name, 0, 1);
                @endphp
                <button wire:click="selectCategory({{ $cat->id }})" wire:key="quickcat-{{ $cat->id }}"
                    class="shrink-0 w-[84px] flex flex-col items-center gap-1.5 group">
                    <span @class(['w-14 h-14 rounded-2xl flex items-center justify-center text-[17px] font-semibold transition ring-2 ring-transparent group-hover:ring-login-accent/40', $catColors[$i % count($catColors)], 'ring-login-accent' => $categoryId === $cat->id])>
                        {{ $iconLabel }}
                    </span>
                    <span @class(['text-[11px] text-center leading-tight line-clamp-2', 'font-semibold text-login-accent' => $categoryId === $cat->id, 'text-text3' => $categoryId !== $cat->id])>{{ $cat->name }}</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Service bar — สร้างความมั่นใจให้ลูกค้าใหม่ที่ยังไม่เคยซื้อกับร้าน --}}
    <div data-aos="fade-up" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
        @foreach ([
            ['d' => 'M3 3h13v10H3zM16 8h4l3 3v5h-7zM5.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM18.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z', 'title' => 'จัดส่งไว', 'sub' => 'ทั่วประเทศ'],
            ['d' => 'M9 12l2 2 4-4M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4z', 'title' => 'ของแท้ 100%', 'sub' => 'เชื่อถือได้'],
            ['d' => 'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z', 'title' => 'บริการด้วยใจ', 'sub' => 'ใส่ใจทุกออเดอร์'],
            ['d' => 'M19 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2zM7 11V7a5 5 0 0 1 10 0v4', 'title' => 'ชำระเงินปลอดภัย', 'sub' => 'หลายช่องทาง'],
            ['d' => 'M17 1l4 4-4 4M3 11V9a4 4 0 0 1 4-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 0 1-4 4H3', 'title' => 'คืน/เปลี่ยนสินค้า', 'sub' => 'ภายใน 7 วัน'],
        ] as $badge)
            <div class="flex items-center gap-2.5 bg-surface border border-border rounded-xl px-3.5 py-3">
                <span class="shrink-0 w-8 h-8 rounded-lg bg-login-accent-tint text-login-accent flex items-center justify-center">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $badge['d'] }}"></path></svg>
                </span>
                <span class="flex flex-col leading-tight">
                    <span class="text-[12px] font-semibold">{{ $badge['title'] }}</span>
                    <span class="text-[10.5px] text-muted2">{{ $badge['sub'] }}</span>
                </span>
            </div>
        @endforeach
    </div>

    {{-- หมวดหมู่สินค้า (การ์ดใหญ่ พร้อมปุ่ม) --}}
    @if ($categories->count() > 0)
        <div data-aos="fade-up" class="flex flex-col gap-3">
            <h2 class="flex items-center gap-2 text-[16px] font-semibold tracking-tight">
                <span>🐾</span> หมวดหมู่สินค้า
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach ($categories->take(8) as $i => $cat)
                    @php $catColors = ['bg-login-accent-tint text-login-accent', 'bg-warn-tint text-warn', 'bg-danger-tint text-danger', 'bg-accent-tint text-accent']; @endphp
                    <button wire:click="selectCategory({{ $cat->id }})" wire:key="catgrid-{{ $cat->id }}"
                        class="flex flex-col items-center gap-2.5 bg-surface border border-border rounded-2xl p-4 hover:border-login-accent/40 hover:shadow-sm transition">
                        <span @class(['w-14 h-14 rounded-full flex items-center justify-center text-[19px] font-semibold', $catColors[$i % count($catColors)]])>
                            {{ mb_substr(preg_replace('/^(อาหาร)/u', '', $cat->name) ?: $cat->name, 0, 1) }}
                        </span>
                        <span class="text-[13px] font-medium text-center leading-snug">{{ $cat->name }}</span>
                        <span class="text-[11.5px] font-semibold text-login-accent">ช้อปเลย ›</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

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

        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-[12.5px] text-text3 cursor-pointer select-none w-fit">
                <input type="checkbox" wire:model.live="inStockOnly" class="w-[16px] h-[16px] rounded-[5px] border-border4 text-accent focus:ring-accent focus:outline-none">
                แสดงเฉพาะที่มีของ
            </label>

            {{-- ตอนเลื่อนลงมาแล้วแถบหมวดหมู่ด้านบนเลื่อนพ้นจอไปแล้ว ยังต้องเห็นอยู่ว่ากำลังกรองหมวดไหนอยู่ --}}
            @if ($categoryId)
                @php $activeCategory = $categories->firstWhere('id', $categoryId); @endphp
                @if ($activeCategory)
                    <button wire:click="selectCategory(null)" class="flex items-center gap-1.5 pl-3 pr-2 py-1 rounded-full bg-accent-tint text-accent text-[12px] font-medium">
                        กำลังดู: {{ $activeCategory->name }}
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- Product grid --}}
    @if ($products->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5">
            @foreach ($products as $i => $product)
                <a href="{{ route('shop.product', $product->id) }}" wire:navigate
                    data-aos="fade-up" data-aos-delay="{{ ($i % 8) * 50 }}"
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
                                {{ number_format((float) $product->price, 2) }} บาท
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
            ">
                {{-- Skeleton การ์ดสินค้า — แทนที่ข้อความ "กำลังโหลด..." เฉยๆ ให้ดูลื่นไหลกว่า
                ระหว่างรอสินค้าชุดถัดไปโหลดเข้ามา (infinite scroll) --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5 animate-pulse">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="bg-surface border border-border rounded-[14px] overflow-hidden">
                            <div class="aspect-square bg-sunken"></div>
                            <div class="p-3 flex flex-col gap-2">
                                <div class="h-3 rounded bg-sunken w-4/5"></div>
                                <div class="h-3 rounded bg-sunken w-2/5"></div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        @endif
    @else
        {{-- Empty state — ให้ทางออกแทนที่จะจบตัน --}}
        <div class="py-16 flex flex-col items-center gap-4 text-center">
            <span class="w-16 h-16 rounded-full bg-sunken text-muted3 flex items-center justify-center">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.3-4.3"></path></svg>
            </span>
            <div class="flex flex-col gap-1">
                <span class="text-[14px] font-medium">ไม่พบสินค้าที่ค้นหา</span>
                <span class="text-[12.5px] text-muted2">ลองค้นหาด้วยคำอื่น หรือดูหมวดหมู่ยอดนิยมด้านล่าง</span>
            </div>
            <div class="flex flex-wrap justify-center gap-2">
                <button wire:click="clearFilters" class="px-4 py-2 rounded-lg border border-border4 text-[12.5px] font-medium text-text2 hover:border-accent hover:text-accent">ล้างตัวกรองทั้งหมด</button>
                @foreach ($categories->take(3) as $cat)
                    <button wire:click="selectCategory({{ $cat->id }})" class="px-4 py-2 rounded-lg border border-border4 text-[12.5px] font-medium text-text2 hover:border-accent hover:text-accent">{{ $cat->name }}</button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Back to top --}}
    <div x-data="{ show: false }" x-init="window.addEventListener('scroll', () => { show = window.scrollY > 500 }, { passive: true })">
        {{-- ชิดซ้ายแทนขวา — ฝั่งขวามีปุ่มลอย LINE/โทร ของ layout อยู่แล้ว วางซ้อนกันจะทับกัน --}}
        <button type="button" @click="window.scrollTo({ top: 0, behavior: 'smooth' })" x-show="show" x-cloak x-transition.opacity
            class="fixed bottom-5 left-5 z-40 w-11 h-11 rounded-full bg-surface border border-border2 shadow-lg flex items-center justify-center text-text2 hover:text-accent hover:border-accent"
            title="กลับขึ้นบนสุด">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"></path></svg>
        </button>
    </div>
</div>
