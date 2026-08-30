<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }} · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script>
        // Applied before paint so there's no flash of the wrong theme.
        (function () {
            var saved = localStorage.getItem('fs-theme');
            var theme = saved === 'dark' || saved === 'light'
                ? saved
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body class="font-sans antialiased text-text"
      x-data="{
          railCollapsed: localStorage.getItem('fs-rail') === '1',
          theme: (function () {
              var saved = localStorage.getItem('fs-theme');
              return (saved === 'dark' || saved === 'light') ? saved : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
          })(),
          logoutOpen: false,
          moreOpen: false,
          toggleRail() { this.railCollapsed = !this.railCollapsed; localStorage.setItem('fs-rail', this.railCollapsed ? '1' : '0'); },
          setTheme(t) { this.theme = t; document.documentElement.setAttribute('data-theme', t); localStorage.setItem('fs-theme', t); },
      }"
      x-init="document.documentElement.setAttribute('data-theme', theme)">

    @php
        // เฉพาะหน้ารับเข้า–เบิกออกเท่านั้นที่ให้ <main> เป็นกรอบ scroll ของตัวเอง (ตารางในหน้านั้น
        // scroll ในตัวเองอยู่แล้ว ไม่ต้องการ scroll bar ซ้อนของทั้งหน้าด้วย) — จำกัดเฉพาะหน้านี้
        // เพราะหน้าอื่นที่มีแถบตัวกรองแบบ sticky (เช่น สินค้า/ราคา) จะเสีย ถ้า <main> กลายเป็น
        // กรอบ scroll ซ้อนอีกชั้น (sticky คำนวณตำแหน่งผิดเมื่ออยู่ในกรอบ scroll ที่ซ้อนกัน)
        $fillViewport = request()->routeIs('movements.index');
    @endphp
    <div class="flex min-h-screen {{ $fillViewport ? 'md:h-screen' : '' }} bg-bg">
        {{-- Sidebar --}}
        <aside
            class="hidden md:flex flex-col gap-[22px] sticky top-0 z-50 h-screen shrink-0 bg-surface border-r border-border py-5 px-3.5 relative transition-[width] duration-300 ease-in-out"
            :class="railCollapsed ? 'w-[72px]' : 'w-[246px]'"
        >
            <button @click="toggleRail" title="ย่อ/ขยายเมนู"
                class="absolute top-11 -right-[13px] z-50 w-[26px] h-[26px] rounded-full bg-surface border border-border shadow-sm flex items-center justify-center text-muted2 hover:border-accent hover:text-accent">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path :d="railCollapsed ? 'M9 5l7 7-7 7' : 'M15 5l-7 7 7 7'"></path>
                </svg>
            </button>

            <div class="flex items-center gap-[11px] px-1" :class="railCollapsed ? 'justify-center' : ''">
                <div class="w-8 h-8 shrink-0 rounded-[9px] bg-accent text-white flex items-center justify-center text-[13.5px] font-semibold tracking-tight">ส.กิจ</div>
                <div class="flex-1 min-w-0 flex flex-col leading-tight overflow-hidden" x-show="!railCollapsed" x-cloak>
                    <span class="text-sm font-semibold tracking-tight whitespace-nowrap">ระบบสต็อคสินค้า</span>
                    <span class="text-[11px] text-muted2 whitespace-nowrap overflow-hidden text-ellipsis">ส.กิจการค้า</span>
                </div>
            </div>

            <nav class="flex flex-col gap-[18px] overflow-y-auto">
                @foreach (\App\Support\Nav::groups() as $group)
                    @php
                        $items = collect(\App\Support\Nav::pages())
                            ->where('group', $group)
                            ->filter(fn ($p) => $p['permission'] === null || auth()->user()->can($p['permission']->value));
                    @endphp
                    @if ($items->isNotEmpty())
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[10px] font-semibold tracking-[.11em] text-muted3 px-[9px] pb-[7px] block whitespace-nowrap"
                                  x-show="!railCollapsed" x-cloak>{{ $group }}</span>
                            @foreach ($items as $item)
                                @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                                <a href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                                   title="{{ $item['label'] }}"
                                   wire:navigate
                                   class="flex items-center gap-[11px] px-[10px] py-[9px] rounded-[9px] text-[13.5px] hover:bg-sunken2 {{ $active ? 'bg-accent-tint text-accent-ink font-semibold' : 'text-text4 font-normal' }}"
                                   :class="railCollapsed ? 'justify-center' : ''">
                                    <span class="shrink-0 flex">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="opacity-90"><path d="{{ $item['d'] }}"></path></svg>
                                    </span>
                                    <span class="flex-1 whitespace-nowrap overflow-hidden text-ellipsis" x-show="!railCollapsed" x-cloak>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="mt-auto border-t border-line pt-[13px] flex items-center gap-[11px]" :class="railCollapsed ? 'justify-center' : ''">
                <span class="w-[31px] h-[31px] shrink-0 rounded-full bg-accent-tint text-accent flex items-center justify-center text-xs font-semibold">
                    {{ \Illuminate\Support\Str::of(auth()->user()?->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('') }}
                </span>
                <div class="flex-1 min-w-0 flex flex-col leading-tight overflow-hidden" x-show="!railCollapsed" x-cloak>
                    <span class="text-[12.5px] font-medium whitespace-nowrap overflow-hidden text-ellipsis">{{ auth()->user()?->name }}</span>
                    <span class="text-[11px] text-muted2 whitespace-nowrap">{{ auth()->user()?->role->label() }}</span>
                </div>
                <button @click="logoutOpen = true" title="ออกจากระบบ"
                    class="w-[29px] h-[29px] shrink-0 rounded-lg flex items-center justify-center text-muted2 hover:bg-danger-tint hover:text-danger">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"></path></svg>
                </button>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 min-w-0 flex flex-col pb-16 md:pb-0 {{ $fillViewport ? 'md:h-screen' : '' }}">
            <header class="bg-surface border-b border-border px-4 md:px-6 py-3 flex items-center gap-3 sticky top-0 z-40">
                <div class="flex-1 min-w-0 flex flex-col leading-snug">
                    <span class="text-[16.5px] font-semibold tracking-tight whitespace-nowrap overflow-hidden text-ellipsis">{{ $title ?? '' }}</span>
                    <span class="text-xs text-muted2 whitespace-nowrap overflow-hidden text-ellipsis">{{ $subtitle ?? '' }}</span>
                </div>

                <livewire:notifications.bell />

                <div class="flex items-center gap-[3px] shrink-0 bg-chip rounded-[9px] p-[3px]">
                    <button @click="setTheme('light')" title="โหมดสว่าง"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-[7px] text-xs font-medium"
                        :class="theme === 'light' ? 'bg-surface shadow-sm text-text' : 'text-muted2'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
                    </button>
                    <button @click="setTheme('dark')" title="โหมดมืด"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-[7px] text-xs font-medium"
                        :class="theme === 'dark' ? 'bg-surface shadow-sm text-text' : 'text-muted2'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"></path></svg>
                    </button>
                </div>
            </header>

            <main class="flex-1 w-full max-w-[1420px] mx-auto p-4 md:p-6 {{ $fillViewport ? 'md:overflow-y-auto md:min-h-0' : '' }}">
                @if (session('success'))
                    <div class="mb-4 px-4 py-2.5 rounded-[10px] bg-accent-tint text-accent-ink text-[13px]">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 px-4 py-2.5 rounded-[10px] bg-danger-tint text-danger text-[13px]">{{ session('error') }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Mobile bottom tab bar --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-surface/95 backdrop-blur border-t border-border flex px-1.5 pt-1.5 pb-2">
        @php
            $tabKeys = ['dashboard', 'products', 'movements', 'alerts'];
            $tabs = collect(\App\Support\Nav::pages())
                ->whereIn('key', $tabKeys)
                ->filter(fn ($p) => $p['permission'] === null || auth()->user()->can($p['permission']->value));
        @endphp
        @foreach ($tabs as $item)
            <a href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
               wire:navigate
               class="flex-1 flex flex-col items-center gap-1 py-1.5 text-[11px] {{ request()->routeIs($item['route']) ? 'text-accent font-semibold' : 'text-muted2 font-medium' }}">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $item['d'] }}"></path></svg>
                {{ explode(' ', $item['label'])[0] }}
            </a>
        @endforeach
        <button @click="moreOpen = true" class="flex-1 flex flex-col items-center gap-1 py-1.5 text-[11px] text-muted2 font-medium">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
            เพิ่มเติม
        </button>
    </nav>

    {{-- Mobile "more" sheet --}}
    <div x-show="moreOpen" x-cloak @click="moreOpen = false" class="md:hidden fixed inset-0 bg-black/40 z-[85]"></div>
    <div x-show="moreOpen" x-cloak @click.stop
         class="md:hidden fixed bottom-0 left-0 right-0 z-[90] bg-surface rounded-t-2xl p-4 pb-6 max-h-[70vh] overflow-y-auto">
        <div class="flex flex-col gap-1">
            @foreach (\App\Support\Nav::pages() as $item)
                @continue($item['permission'] !== null && ! auth()->user()->can($item['permission']->value))
                <a href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                   wire:navigate
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs($item['route']) ? 'bg-accent-tint text-accent-ink font-semibold' : 'text-text2' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $item['d'] }}"></path></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Logout confirm modal --}}
    <div x-show="logoutOpen" x-cloak @click="logoutOpen = false" class="fixed inset-0 bg-black/40 z-[95] flex items-center justify-center p-4">
        <div @click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-xl p-5 flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <span class="text-base font-semibold">ออกจากระบบ</span>
                <span class="text-sm text-muted">คุณกำลังเข้าใช้งานเป็น {{ auth()->user()?->name }} หากออกจากระบบต้องเข้าสู่ระบบใหม่อีกครั้ง</span>
            </div>
            <div class="flex gap-2.5">
                <button @click="logoutOpen = false" type="button"
                    class="flex-1 px-4 py-2.5 rounded-[10px] border border-border4 text-text2 text-sm font-medium hover:bg-sunken">ยกเลิก</button>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 rounded-[10px] bg-danger text-white text-sm font-medium hover:opacity-90">ออกจากระบบ</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Page navigation loader --}}
    <div id="page-loader-overlay" style="display:none" class="fixed inset-0 z-[999] flex items-center justify-center bg-bg/60 backdrop-blur-[1px]">
        <div class="page-loader"></div>
    </div>

    @livewireScripts
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.navigate.disableProgressBar();
        });
        document.addEventListener('livewire:navigating', () => {
            document.getElementById('page-loader-overlay').style.display = 'flex';
        });
        document.addEventListener('livewire:navigated', () => {
            document.getElementById('page-loader-overlay').style.display = 'none';
        });
    </script>
</body>
</html>
