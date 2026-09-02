<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('shop.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="description" content="{{ $description ?? 'ดูรายการสินค้าของร้าน '.config('shop.name').' แล้วทักไลน์หรือโทรสั่งซื้อได้ทันที' }}">
    {{-- ตัวเดียวกันไม่ว่าจะมี query string ตัวกรอง/เรียง/ค้นหาติดมาแบบไหนก็ตาม กัน Google
    มองว่าเป็นหน้าซ้ำกันหลายเวอร์ชัน --}}
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

    {{-- Open Graph — ให้ลิงก์ที่แชร์ไป Facebook/LINE มีรูป ชื่อ และคำอธิบายสินค้าติดไปด้วย --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="{{ config('shop.name') }}">
    <meta property="og:title" content="{{ $title ?? config('shop.name') }}">
    <meta property="og:description" content="{{ $description ?? 'ดูรายการสินค้าของร้าน '.config('shop.name').' แล้วทักไลน์หรือโทรสั่งซื้อได้ทันที' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if (! empty($image))
        <meta property="og:image" content="{{ $image }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-surface2 text-text min-h-screen flex flex-col overflow-x-hidden">

    {{-- Topbar ประกาศสั้นๆ + จุดขายหลัก — ใช้แถบเดียวสื่อสารทุกอย่างตั้งแต่แรกที่เข้าเว็บ --}}
    <div class="bg-login-accent text-white text-[11.5px]">
        <div class="max-w-[1100px] mx-auto px-4 sm:px-6 h-9 flex items-center justify-between gap-4 overflow-x-auto whitespace-nowrap">
            <span>♥ ยินดีต้อนรับสู่ {{ config('shop.name') }} อาหารสัตว์ดี มีคุณภาพ บริการด้วยใจ ♥</span>
            <span class="hidden lg:flex items-center gap-4 shrink-0">
                <span>🚚 จัดส่งไว ทั่วประเทศ</span>
                <span>🛡 ของแท้ 100% เชื่อถือได้</span>
                <span>🐾 บริการด้วยใจ ใส่ใจทุกออเดอร์</span>
            </span>
        </div>
    </div>

    <header class="sticky top-0 z-40 bg-surface/95 backdrop-blur border-b border-line">
        <div class="max-w-[1100px] mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
            <a href="{{ route('shop.index') }}" wire:navigate class="flex items-center gap-2.5 shrink-0">
                <span class="w-9 h-9 rounded-[10px] bg-login-accent text-white flex items-center justify-center font-semibold text-[15px]">
                    {{ mb_substr(config('shop.name'), 0, 1) }}
                </span>
                <span class="flex-col leading-tight hidden sm:flex">
                    <span class="text-[15px] font-semibold tracking-tight">{{ config('shop.name') }}</span>
                    <span class="text-[11px] text-muted2">ใส่ใจทุกคำ เพื่อเขาที่คุณรัก</span>
                </span>
            </a>

            <nav class="hidden md:flex items-center gap-1 mx-auto">
                <a href="{{ route('shop.index') }}" wire:navigate
                    @class(['px-3.5 py-2 rounded-lg text-[13px] font-medium', 'text-login-accent bg-login-accent-tint' => request()->routeIs('shop.index') || request()->routeIs('shop.product'), 'text-text2 hover:bg-sunken' => ! (request()->routeIs('shop.index') || request()->routeIs('shop.product'))])>สินค้า</a>
                <a href="{{ route('shop.about') }}" wire:navigate
                    @class(['px-3.5 py-2 rounded-lg text-[13px] font-medium', 'text-login-accent bg-login-accent-tint' => request()->routeIs('shop.about'), 'text-text2 hover:bg-sunken' => ! request()->routeIs('shop.about')])>เกี่ยวกับเรา</a>
                <a href="{{ route('shop.contact') }}" wire:navigate
                    @class(['px-3.5 py-2 rounded-lg text-[13px] font-medium', 'text-login-accent bg-login-accent-tint' => request()->routeIs('shop.contact'), 'text-text2 hover:bg-sunken' => ! request()->routeIs('shop.contact')])>ติดต่อ/ที่ตั้งร้าน</a>
            </nav>

            <div class="flex items-center gap-2 shrink-0">
                {{-- ไอคอนช่องทางขายอื่นๆ — โชว์เฉพาะช่องที่ตั้งค่า URL ไว้ใน .env (SHOP_SHOPEE_URL /
                SHOP_FACEBOOK_URL) ยังไม่ตั้งก็ไม่ต้องมีไอคอนเปล่าๆ โผล่มาให้กดแล้วไปไหนไม่ได้ --}}
                @if (config('shop.shopee_url'))
                    <a href="{{ config('shop.shopee_url') }}" target="_blank" rel="noopener" title="ช้อปที่ Shopee"
                        class="hidden sm:flex w-9 h-9 rounded-[10px] items-center justify-center bg-[#ee4d2d]/10 text-[#ee4d2d] hover:bg-[#ee4d2d]/20">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M6 2l1.5 3h9L18 2h2l-2 4H6L4 2h2zM4 8h16l-1.4 12.2A2 2 0 0 1 16.6 22H7.4a2 2 0 0 1-2-1.8L4 8zm8 3a3 3 0 0 0-3 3 1 1 0 1 0 2 0 1 1 0 1 1 1 1 1 1 0 0 0 0 2 3 3 0 0 0 0-6z"></path></svg>
                    </a>
                @endif
                @if (config('shop.facebook_url'))
                    <a href="{{ config('shop.facebook_url') }}" target="_blank" rel="noopener" title="เพจ Facebook"
                        class="hidden sm:flex w-9 h-9 rounded-[10px] items-center justify-center bg-[#1877f2]/10 text-[#1877f2] hover:bg-[#1877f2]/20">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12z"></path></svg>
                    </a>
                @endif

                @if (config('shop.phone'))
                    <a href="tel:{{ config('shop.phone') }}" class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-[10px] border border-border4 text-[12.5px] font-medium text-text2 hover:border-login-accent hover:text-login-accent">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        {{ config('shop.phone') }}
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-surface">
        <div class="max-w-[1100px] mx-auto px-4 sm:px-6 py-8 flex flex-col items-center gap-1.5 text-center">
            <span class="text-[13px] font-medium text-text2">{{ config('shop.name') }}</span>
            <span class="text-[11.5px] text-muted2">© {{ now()->year + 543 }} {{ config('shop.name') }} · สงวนลิขสิทธิ์ทุกประการ</span>
        </div>
    </footer>

    {{-- ปุ่มโทรลอย — โชว์เฉพาะจอมือถือ (จอ sm ขึ้นไปมีปุ่มโทรอยู่บน header อยู่แล้ว) ให้กดโทรได้
    ทันทีจากทุกหน้ารวมถึงหน้ารายการสินค้า ตรงกับข้อความ hero ที่สัญญาว่า "โทรสั่งซื้อได้ทันที" --}}
    @if (config('shop.phone'))
        <a href="tel:{{ config('shop.phone') }}"
            class="sm:hidden fixed bottom-24 right-5 z-50 w-14 h-14 rounded-full bg-login-accent text-white shadow-lg flex items-center justify-center hover:opacity-90"
            title="โทรสอบถาม/สั่งซื้อ">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
        </a>
    @endif

    @if (config('shop.line_url'))
        <a href="{{ config('shop.line_url') }}" target="_blank" rel="noopener"
            class="fixed bottom-5 right-5 z-50 w-14 h-14 rounded-full bg-[#06C755] text-white shadow-lg flex items-center justify-center hover:opacity-90"
            title="ทักไลน์สอบถาม/สั่งซื้อ">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 5.94 2 10.8c0 4.36 3.58 8.02 8.42 8.72.33.07.78.22.89.5.1.26.07.66.03.92l-.14.87c-.04.26-.2 1 .88.55s5.8-3.42 7.92-5.86C21.55 14.05 22 12.47 22 10.8 22 5.94 17.52 2 12 2z"></path></svg>
        </a>
    @endif

    @livewireScripts
</body>
</html>
