<div class="max-w-[900px] mx-auto px-4 sm:px-6 py-8 flex flex-col gap-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-[12.5px] text-muted2 flex-wrap">
        <a href="{{ route('shop.index') }}" wire:navigate class="hover:text-accent">หน้าแรก</a>
        <span class="text-border4">/</span>
        <span class="text-text2 font-medium">เกี่ยวกับเรา</span>
    </nav>

    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-accent px-5 py-9 sm:px-9 sm:py-12 text-center flex flex-col items-center gap-2.5">
        <div aria-hidden="true" class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/10"></div>
        <div aria-hidden="true" class="absolute -bottom-20 -left-10 w-56 h-56 rounded-full bg-black/10"></div>
        <span class="relative w-16 h-16 rounded-full overflow-hidden bg-white shadow-lg flex items-center justify-center">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('shop.name') }}" class="w-full h-full object-cover">
        </span>
        <h1 class="relative text-[24px] sm:text-[28px] font-semibold text-white tracking-tight">เกี่ยวกับ {{ config('shop.name') }}</h1>
        <p class="relative text-[13.5px] sm:text-[14.5px] text-white/85 max-w-[480px]">เพื่อนแท้ของเกษตรกรและคนรักสัตว์เลี้ยง — อาหารสัตว์และของใช้คู่ฟาร์มครบวงจร ในราคาที่เป็นกันเอง</p>
    </div>

    {{-- What we do --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        @foreach ([
            ['d' => 'M20 6L9 17l-5-5', 'title' => 'สินค้าครบ หลากหลาย', 'desc' => 'อาหารสัตว์ทุกชนิด ทั้งปศุสัตว์ สัตว์ปีก และสัตว์เลี้ยง พร้อมของใช้คู่ฟาร์ม'],
            ['d' => 'M9 12l2 2 4-4M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4z', 'title' => 'ใส่ใจทุกคำ', 'desc' => 'คัดสินค้าคุณภาพ ใส่ใจทุกออเดอร์ เพราะเข้าใจว่าสัตว์เลี้ยงคือคนสำคัญของคุณ'],
            ['d' => 'M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z', 'title' => 'พร้อมให้คำปรึกษา', 'desc' => 'ทักไลน์หรือโทรมาได้เลย ทีมงานยินดีช่วยแนะนำสินค้าที่เหมาะกับสัตว์เลี้ยงของคุณ'],
        ] as $item)
            <div data-aos="fade-up" class="bg-surface border border-border rounded-2xl p-5 flex flex-col gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-accent-tint text-accent flex items-center justify-center">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $item['d'] }}"></path></svg>
                </span>
                <span class="text-[14.5px] font-semibold">{{ $item['title'] }}</span>
                <span class="text-[12.5px] text-text3 leading-relaxed">{{ $item['desc'] }}</span>
            </div>
        @endforeach
    </div>

    {{-- CTA --}}
    <div class="flex flex-col gap-2.5 p-5 rounded-2xl bg-accent-tint/60 border border-accent-border text-center items-center">
        <span class="text-[13.5px] font-medium text-text2">สนใจสินค้า หรืออยากให้ช่วยแนะนำ ทักมาคุยกันได้เลยครับ</span>
        <div class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto">
            @if (config('shop.line_url'))
                <a href="{{ config('shop.line_url') }}" target="_blank" rel="noopener"
                    class="flex-1 flex items-center justify-center gap-2 py-3 px-6 rounded-[10px] bg-[#06C755] text-white text-[14px] font-semibold hover:opacity-90 shadow-sm">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 5.94 2 10.8c0 4.36 3.58 8.02 8.42 8.72.33.07.78.22.89.5.1.26.07.66.03.92l-.14.87c-.04.26-.2 1 .88.55s5.8-3.42 7.92-5.86C21.55 14.05 22 12.47 22 10.8 22 5.94 17.52 2 12 2z"></path></svg>
                    ทักไลน์
                </a>
            @endif
            <a href="{{ route('shop.contact') }}" wire:navigate
                class="flex-1 flex items-center justify-center gap-2 py-3 px-6 rounded-[10px] bg-surface border border-border4 text-text2 text-[14px] font-semibold hover:border-accent hover:text-accent">
                ดูช่องทางติดต่อทั้งหมด
            </a>
        </div>
    </div>

    <a href="{{ route('shop.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[13px] text-text3 hover:text-accent self-start">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
        กลับไปดูสินค้าทั้งหมด
    </a>
</div>
