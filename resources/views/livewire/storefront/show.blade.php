<div class="max-w-[1280px] mx-auto px-4 sm:px-6 py-7 sm:py-10 pb-24 flex flex-col gap-7">

    <script type="application/ld+json">{!! $jsonLd !!}</script>

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-[12.5px] text-muted2 flex-wrap px-1">
        <a href="{{ route('shop.index') }}" wire:navigate class="hover:text-accent">หน้าแรก</a>
        <span class="text-border4">/</span>
        <a href="{{ route('shop.index') }}" wire:navigate class="hover:text-accent">สินค้าทั้งหมด</a>
        @if ($product->category)
            <span class="text-border4">/</span>
            <a href="{{ route('shop.index', ['category' => $product->category_id]) }}" wire:navigate class="hover:text-accent">{{ $product->category->name }}</a>
        @endif
        <span class="text-border4">/</span>
        <span class="text-text2 font-medium truncate max-w-[220px]">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-[1.08fr_.92fr] gap-6 lg:gap-8 items-start">

        {{-- Image — คลิกเพื่อซูมดูเต็มจอ (สินค้าเป็นถุง/กระสอบ ลูกค้ามักอยากดูฉลาก/ส่วนผสมชัดๆ) --}}
        <div x-data="{ zoomed: false }">
            <div @if($product->photo_path) @click="zoomed = true" @endif
                @class(['bg-gradient-to-br from-[#fff1f3] to-[#fffaf1] border border-[#f4dfe2] rounded-[28px] shadow-[0_18px_55px_rgba(9,42,74,.10)] p-7 sm:p-12 flex items-center justify-center aspect-square overflow-hidden', 'cursor-zoom-in' => $product->photo_path])>
                @if ($product->photo_path)
                    <img src="{{ asset('storage/'.$product->photo_path) }}" alt="{{ $product->name }}" class="w-full h-full object-contain drop-shadow-[0_18px_18px_rgba(9,42,74,.18)] transition duration-300 hover:scale-[1.03]">
                @else
                    <span class="text-[72px] font-semibold text-muted3">{{ mb_substr($product->name, 0, 1) }}</span>
                @endif
            </div>

            @if ($product->photo_path)
                <div x-show="zoomed" x-cloak x-transition.opacity @click="zoomed = false" @keydown.escape.window="zoomed = false"
                    class="fixed inset-0 z-[80] bg-black/85 flex items-center justify-center p-4 sm:p-10 cursor-zoom-out">
                    <img src="{{ asset('storage/'.$product->photo_path) }}" alt="{{ $product->name }}" class="max-w-full max-h-full object-contain">
                    <button type="button" @click.stop="zoomed = false" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20" title="ปิด">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                    </button>
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div class="flex flex-col gap-5 bg-white border border-[#f1e2e4] rounded-[28px] p-6 sm:p-8 shadow-[0_18px_55px_rgba(9,42,74,.08)]" x-data="{ sizes: @js($sizes), selectedId: {{ $sizes->first()['id'] }} }">
            <div class="flex flex-col gap-2">
                @if ($product->category)
                    <span class="w-max text-[12px] font-semibold text-[#e95774] bg-[#fff0f3] px-3 py-1.5 rounded-full">{{ $product->category->name }}</span>
                @endif
                <h1 class="text-[28px] sm:text-[36px] text-[#092a4a] font-bold tracking-tight leading-snug">{{ $product->name }}</h1>
                @if (! $hasSizes && $product->size)
                    <span class="text-[13.5px] text-muted2">ขนาด {{ $product->size }}</span>
                @endif
            </div>

            @if ($hasSizes)
                <div class="flex flex-col gap-1.5">
                    <span class="text-[12.5px] font-medium text-text2">ขนาด</span>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="s in sizes" :key="s.id">
                            <button type="button" @click="selectedId = s.id"
                                class="px-4 py-2 rounded-lg text-[13px] font-medium border transition"
                                :class="selectedId === s.id ? 'bg-accent text-white border-accent' : 'bg-surface text-text2 border-border4 hover:border-accent'"
                                x-text="s.label"></button>
                        </template>
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-3 pt-1">
                <span class="text-[34px] sm:text-[40px] font-bold text-[#e95774]"
                    x-text="Number(sizes.find(s => s.id === selectedId).price).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ number_format($sizes->first()['price'], 2) }}</span>
                <span class="text-[14px] text-muted2">บาท</span>
                <span class="inline-flex items-center gap-1.5 text-[11.5px] font-medium px-2.5 py-1 rounded-full whitespace-nowrap"
                    :class="sizes.find(s => s.id === selectedId).in_stock ? 'bg-accent-tint text-accent' : 'bg-danger-tint text-danger'">
                    <span class="w-1.5 h-1.5 rounded-full" :class="sizes.find(s => s.id === selectedId).in_stock ? 'bg-accent' : 'bg-danger'"></span>
                    <span x-text="sizes.find(s => s.id === selectedId).in_stock ? 'มีสินค้า' : 'หมดชั่วคราว'">{{ $sizes->first()['in_stock'] ? 'มีสินค้า' : 'หมดชั่วคราว' }}</span>
                </span>
            </div>

            {{-- Contact CTA --}}
            <div class="flex flex-col gap-3 p-5 rounded-2xl bg-[#fff5f6] border border-[#f6d9df]">
                <span class="text-[13px] font-semibold text-[#092a4a]">สนใจสินค้านี้ เลือกช่องทางสั่งซื้อได้เลย</span>
                <div class="flex flex-col sm:flex-row gap-2.5">
                    @if (config('shop.line_url'))
                        <a href="{{ config('shop.line_url') }}" target="_blank" rel="noopener"
                            class="flex-1 flex items-center justify-center gap-2 py-3.5 rounded-xl bg-[#06C755] text-white text-[14px] font-semibold hover:opacity-90 shadow-sm">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 5.94 2 10.8c0 4.36 3.58 8.02 8.42 8.72.33.07.78.22.89.5.1.26.07.66.03.92l-.14.87c-.04.26-.2 1 .88.55s5.8-3.42 7.92-5.86C21.55 14.05 22 12.47 22 10.8 22 5.94 17.52 2 12 2z"></path></svg>
                            ทักไลน์สั่งซื้อ
                        </a>
                    @endif
                    @if (config('shop.phone'))
                        <a href="tel:{{ config('shop.phone') }}"
                            class="flex-1 flex items-center justify-center gap-2 py-3.5 rounded-xl bg-[#092a4a] border border-[#092a4a] text-white text-[14px] font-semibold hover:bg-[#12456f]">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            โทร {{ config('shop.phone') }}
                        </a>
                    @endif
                </div>
                <div class="flex flex-col sm:flex-row gap-2.5">
                    @if (config('shop.shopee_url'))
                        <a href="{{ config('shop.shopee_url') }}" target="_blank" rel="noopener" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-[#ee4d2d] text-white text-[13px] font-semibold hover:opacity-90">ซื้อผ่าน Shopee</a>
                    @endif
                    @if (config('shop.facebook_url'))
                        <a href="{{ config('shop.facebook_url') }}" target="_blank" rel="noopener" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-[#1877f2] text-white text-[13px] font-semibold hover:opacity-90">สอบถามทาง Facebook</a>
                    @endif
                </div>
            </div>

            {{-- Product details (collapsible) --}}
            <div x-data="{ open: true }" class="border border-[#eee3e4] rounded-2xl p-4">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 text-left">
                    <span class="text-[14px] font-semibold text-text2">รายละเอียดสินค้า</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="text-muted2 transition-transform shrink-0" :class="open ? 'rotate-180' : ''">
                        <path d="M6 9l6 6 6-6"></path>
                    </svg>
                </button>
                <div x-show="open" x-collapse class="mt-3 flex flex-col gap-3">
                @if ($product->description)
                    <p class="text-[13.5px] text-text3 leading-relaxed whitespace-pre-line">{{ $product->description }}</p>
                @endif
                <ul class="flex flex-col gap-2 text-[13.5px] text-text3">
                    @if ($product->category)
                        <li class="flex gap-2"><span class="text-muted2">หมวดหมู่:</span> {{ $product->category->name }}</li>
                    @endif
                    @if ($hasSizes)
                        <li class="flex gap-2"><span class="text-muted2">ขนาด:</span> <span x-text="sizes.find(s => s.id === selectedId).label"></span></li>
                    @elseif ($product->size)
                        <li class="flex gap-2"><span class="text-muted2">ขนาด:</span> {{ $product->size }}</li>
                    @endif
                    <li class="flex gap-2"><span class="text-muted2">สอบถามเพิ่มเติม:</span> ทักไลน์หรือโทรตามช่องทางด้านบนได้ทันที</li>
                </ul>
                </div>
            </div>

            {{-- Share --}}
            <div class="flex items-center gap-3 pt-1">
                <span class="text-[12px] text-muted2">แชร์สินค้านี้</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener"
                    class="w-8 h-8 rounded-full bg-sunken flex items-center justify-center text-muted2 hover:text-accent hover:bg-accent-tint" title="แชร์ไปยัง Facebook">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12z"></path></svg>
                </a>
                <a href="https://social-plugins.line.me/lineit/share?url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener"
                    class="w-8 h-8 rounded-full bg-sunken flex items-center justify-center text-muted2 hover:text-accent hover:bg-accent-tint" title="แชร์ไปยัง LINE">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 5.94 2 10.8c0 4.36 3.58 8.02 8.42 8.72.33.07.78.22.89.5.1.26.07.66.03.92l-.14.87c-.04.26-.2 1 .88.55s5.8-3.42 7.92-5.86C21.55 14.05 22 12.47 22 10.8 22 5.94 17.52 2 12 2z"></path></svg>
                </a>
            </div>
        </div>
    </div>

    <section class="grid grid-cols-2 lg:grid-cols-4 rounded-2xl overflow-hidden border border-[#f0dfe1] bg-white shadow-sm">
        <div class="p-4 flex items-center gap-3 border-b lg:border-b-0 lg:border-r border-[#f0dfe1]"><span class="w-10 h-10 rounded-full bg-[#fff0f3] text-[#e95774] flex items-center justify-center">✓</span><span class="text-[12px] text-text3"><strong class="block text-[13px] text-[#092a4a]">สินค้าคุณภาพ</strong>คัดสรรจากร้าน</span></div>
        <div class="p-4 flex items-center gap-3 border-b lg:border-b-0 lg:border-r border-[#f0dfe1]"><span class="w-10 h-10 rounded-full bg-[#fff0f3] text-[#e95774] flex items-center justify-center">✓</span><span class="text-[12px] text-text3"><strong class="block text-[13px] text-[#092a4a]">มีหน้าร้านจริง</strong>ติดต่อสอบถามได้</span></div>
        <div class="p-4 flex items-center gap-3 border-r border-[#f0dfe1]"><span class="w-10 h-10 rounded-full bg-[#fff0f3] text-[#e95774] flex items-center justify-center">☎</span><span class="text-[12px] text-text3"><strong class="block text-[13px] text-[#092a4a]">บริการด้วยใจ</strong>ช่วยแนะนำสินค้า</span></div>
        <div class="p-4 flex items-center gap-3"><span class="w-10 h-10 rounded-full bg-[#fff0f3] text-[#e95774] flex items-center justify-center">↗</span><span class="text-[12px] text-text3"><strong class="block text-[13px] text-[#092a4a]">จัดส่งทั่วประเทศ</strong>สอบถามค่าจัดส่ง</span></div>
    </section>

    {{-- สินค้าที่คล้ายกัน --}}
    @if ($related->isNotEmpty())
        <div class="flex flex-col gap-3.5 pt-2">
            <div class="flex items-end justify-between"><div><span class="text-[12px] font-semibold text-[#e95774]">เลือกดูเพิ่มเติม</span><h2 class="text-[24px] sm:text-[30px] font-bold text-[#092a4a]">สินค้าที่คล้ายกัน</h2></div><a href="{{ route('shop.index') }}" class="text-[12px] font-semibold text-[#e95774]">ดูทั้งหมด →</a></div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5">
                @foreach ($related as $item)
                    <a href="{{ route('shop.product', $item->id) }}" wire:navigate
                        class="bg-white border border-[#f0e1e3] rounded-[18px] overflow-hidden shadow-sm hover:shadow-lg hover:-translate-y-1 transition flex flex-col">
                        <div class="aspect-square bg-gradient-to-br from-[#fff2f4] to-[#fffaf2] p-4 flex items-center justify-center overflow-hidden">
                            @if ($item->photo_path)
                                <img src="{{ asset('storage/'.$item->photo_path) }}" alt="{{ $item->name }}" class="w-full h-full object-contain">
                            @else
                                <span class="text-[28px] font-semibold text-muted3">{{ mb_substr($item->name, 0, 1) }}</span>
                            @endif
                        </div>
                        <div class="p-3 flex flex-col gap-1">
                            <span class="text-[13px] font-medium leading-snug line-clamp-2">{{ $item->name }}</span>
                            @if ($item->size)
                                <span class="text-[11px] text-muted2">{{ $item->size }}</span>
                            @endif
                            <div class="flex items-center justify-between gap-2 mt-1">
                                <span class="text-[14px] font-bold text-[#e95774]">
                                    {{ number_format((float) $item->price, 2) }} บาท
                                </span>
                                @unless ($item->in_stock)
                                    <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-danger-tint text-danger whitespace-nowrap">หมดชั่วคราว</span>
                                @endunless
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <a href="{{ route('shop.index') }}" wire:navigate class="inline-flex items-center gap-2 text-[13px] font-semibold text-[#092a4a] hover:text-[#e95774] self-start mt-1 px-4 py-2.5 rounded-xl bg-white border border-[#eee0e2]">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
        กลับไปดูสินค้าทั้งหมด
    </a>

    {{-- แถบสั่งซื้อลอยติดล่างจอบนมือถือ — คงอยู่ตลอดแม้เลื่อนดูรายละเอียด/สินค้าที่คล้ายกันยาวแค่ไหน
    ก็ตาม ไม่ต้องเลื่อนกลับขึ้นไปหาปุ่มสั่งซื้อเอง (เดสก์ท็อปมีปุ่มอยู่ในหน้าอยู่แล้ว ไม่ต้องซ้ำ) --}}
    @if (config('shop.line_url') || config('shop.phone'))
    <div class="sm:hidden fixed bottom-0 inset-x-0 z-40 bg-surface border-t border-line shadow-[0_-4px_16px_rgba(0,0,0,.08)] px-4 py-3 flex items-center gap-3">
        <span class="flex-1 min-w-0 flex flex-col leading-snug">
            <span class="text-[11px] text-muted2 truncate">{{ $product->name }}</span>
            <span class="text-[16px] font-semibold text-accent-ink tabular-nums">{{ number_format($sizes->first()['price'], 2) }} บาท</span>
        </span>
        @if (config('shop.line_url'))
            <a href="{{ config('shop.line_url') }}" target="_blank" rel="noopener"
                class="shrink-0 flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-[10px] bg-[#06C755] text-white text-[13px] font-semibold shadow-sm">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 5.94 2 10.8c0 4.36 3.58 8.02 8.42 8.72.33.07.78.22.89.5.1.26.07.66.03.92l-.14.87c-.04.26-.2 1 .88.55s5.8-3.42 7.92-5.86C21.55 14.05 22 12.47 22 10.8 22 5.94 17.52 2 12 2z"></path></svg>
                ทักไลน์
            </a>
        @elseif (config('shop.phone'))
            <a href="tel:{{ config('shop.phone') }}"
                class="shrink-0 flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-[10px] bg-accent text-white text-[13px] font-semibold shadow-sm">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                โทร
            </a>
        @endif
    </div>
    @endif
</div>
