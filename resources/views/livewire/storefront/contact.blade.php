<div class="max-w-[900px] mx-auto px-4 sm:px-6 py-8 flex flex-col gap-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-[12.5px] text-muted2 flex-wrap">
        <a href="{{ route('shop.index') }}" wire:navigate class="hover:text-accent">หน้าแรก</a>
        <span class="text-border4">/</span>
        <span class="text-text2 font-medium">ติดต่อ/ที่ตั้งร้าน</span>
    </nav>

    <div class="flex flex-col gap-1.5">
        <h1 class="text-[22px] sm:text-[26px] font-semibold tracking-tight">ติดต่อ {{ config('shop.name') }}</h1>
        <p class="text-[13.5px] text-muted2">ทักไลน์ โทร หรือแวะมาที่ร้านได้เลยครับ</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
        @if (config('shop.phone'))
            <a href="tel:{{ config('shop.phone') }}" class="flex items-center gap-3.5 bg-surface border border-border rounded-2xl p-4.5 hover:border-accent-border">
                <span class="w-11 h-11 shrink-0 rounded-xl bg-accent-tint text-accent flex items-center justify-center">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </span>
                <span class="flex flex-col leading-snug">
                    <span class="text-[12px] text-muted2">โทรสั่งซื้อ/สอบถาม</span>
                    <span class="text-[15px] font-semibold">{{ config('shop.phone') }}</span>
                </span>
            </a>
        @endif

        @if (config('shop.line_url'))
            <a href="{{ config('shop.line_url') }}" target="_blank" rel="noopener" class="flex items-center gap-3.5 bg-surface border border-border rounded-2xl p-4.5 hover:border-accent-border">
                <span class="w-11 h-11 shrink-0 rounded-xl bg-[#06C755]/10 text-[#06C755] flex items-center justify-center">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 5.94 2 10.8c0 4.36 3.58 8.02 8.42 8.72.33.07.78.22.89.5.1.26.07.66.03.92l-.14.87c-.04.26-.2 1 .88.55s5.8-3.42 7.92-5.86C21.55 14.05 22 12.47 22 10.8 22 5.94 17.52 2 12 2z"></path></svg>
                </span>
                <span class="flex flex-col leading-snug">
                    <span class="text-[12px] text-muted2">ทักไลน์สั่งซื้อ</span>
                    <span class="text-[15px] font-semibold">{{ config('shop.line_id') ?: 'แชทกับเราเลย' }}</span>
                </span>
            </a>
        @endif

        @if (config('shop.address'))
            <div class="flex items-start gap-3.5 bg-surface border border-border rounded-2xl p-4.5">
                <span class="w-11 h-11 shrink-0 rounded-xl bg-accent-tint text-accent flex items-center justify-center">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </span>
                <span class="flex flex-col leading-snug pt-1">
                    <span class="text-[12px] text-muted2">ที่ตั้งร้าน</span>
                    <span class="text-[14px] font-medium leading-relaxed">{{ config('shop.address') }}</span>
                </span>
            </div>
        @endif

        @if (config('shop.hours'))
            <div class="flex items-start gap-3.5 bg-surface border border-border rounded-2xl p-4.5">
                <span class="w-11 h-11 shrink-0 rounded-xl bg-accent-tint text-accent flex items-center justify-center">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
                </span>
                <span class="flex flex-col leading-snug pt-1">
                    <span class="text-[12px] text-muted2">เวลาทำการ</span>
                    <span class="text-[14px] font-medium leading-relaxed">{{ config('shop.hours') }}</span>
                </span>
            </div>
        @endif
    </div>

    @if (! config('shop.address') && ! config('shop.hours'))
        <div class="flex items-start gap-2.5 bg-warn-tint text-warn rounded-xl px-4 py-3 text-[12.5px] leading-relaxed">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 mt-0.5"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
            <span>ยังไม่ได้ตั้งค่าที่อยู่ร้าน/เวลาทำการ — เพิ่มได้ในไฟล์ .env ด้วย <code class="font-mono">SHOP_ADDRESS</code>, <code class="font-mono">SHOP_HOURS</code></span>
        </div>
    @endif

    @if (config('shop.maps_url'))
        <div class="rounded-2xl overflow-hidden border border-border aspect-video">
            <iframe src="{{ config('shop.maps_url') }}" class="w-full h-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    @endif

    <a href="{{ route('shop.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[13px] text-text3 hover:text-accent self-start">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
        กลับไปดูสินค้าทั้งหมด
    </a>
</div>
