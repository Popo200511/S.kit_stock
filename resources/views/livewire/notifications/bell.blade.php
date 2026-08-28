<div class="relative" wire:poll.30s x-data="{ open: false, menuOpen: false }" @click.outside="open = false; menuOpen = false">
    <button @click="open = !open" title="การแจ้งเตือน"
        class="relative w-9 h-9 rounded-[9px] flex items-center justify-center text-muted2 hover:bg-sunken2 hover:text-text">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 min-w-[16px] h-[16px] px-1 rounded-full bg-danger text-white text-[10px] font-semibold flex items-center justify-center leading-none">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right
        class="absolute top-11 right-0 w-[360px] max-w-[92vw] bg-surface border border-border2 rounded-2xl shadow-lg z-50 overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-4 py-3.5 border-b border-line">
            <span class="text-[15px] font-semibold tracking-tight">การแจ้งเตือน</span>
            <div class="relative">
                <button @click="menuOpen = !menuOpen" class="w-7 h-7 rounded-lg flex items-center justify-center text-muted2 hover:bg-sunken2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
                </button>
                <div x-show="menuOpen" x-cloak @click.outside="menuOpen = false"
                    class="absolute top-8 right-0 w-[230px] bg-surface border border-border2 rounded-xl shadow-lg z-10 overflow-hidden">
                    <button wire:click="markAllAsRead" @click="menuOpen = false" class="w-full text-left px-3.5 py-2.5 text-[12.5px] text-text2 hover:bg-chip2 transition-colors border-b border-hairline">ทำเครื่องหมายว่าอ่านแล้วทั้งหมด</button>
                    @if ($hasRead)
                        <button wire:click="deleteAllRead" @click="menuOpen = false" class="w-full text-left px-3.5 py-2.5 text-[12.5px] text-text2 hover:bg-chip2 transition-colors border-b border-hairline">ลบที่อ่านแล้วทั้งหมด</button>
                    @endif
                    <button wire:click="openSettings" @click="menuOpen = false" class="w-full text-left px-3.5 py-2.5 text-[12.5px] text-text2 hover:bg-chip2 transition-colors">ตั้งค่าการแจ้งเตือน</button>
                </div>
            </div>
        </div>

        <div class="flex gap-1 p-2 border-b border-line">
            <button wire:click="setTab('all')" class="flex-1 py-1.5 rounded-lg text-[12.5px] font-medium {{ $tab === 'all' ? 'bg-accent-tint text-accent' : 'text-muted2 hover:bg-sunken2' }}">ทั้งหมด</button>
            <button wire:click="setTab('unread')" class="flex-1 py-1.5 rounded-lg text-[12.5px] font-medium {{ $tab === 'unread' ? 'bg-accent-tint text-accent' : 'text-muted2 hover:bg-sunken2' }}">ยังไม่ได้อ่าน</button>
        </div>

        <div class="max-h-[420px] overflow-y-auto">
            @forelse ($grouped as $label => $items)
                <div class="px-4 pt-3 pb-1.5 text-[11.5px] font-semibold text-muted2">{{ $label }}</div>
                @foreach ($items as $n)
                    @php
                        $icon = $n->data['icon'] ?? 'check';
                        [$iconBg, $iconFg, $iconPath] = match ($icon) {
                            'in' => ['bg-accent-tint', 'text-accent', 'M12 5v14M5 12l7 7 7-7'],
                            'out' => ['bg-chip', 'text-text4', 'M12 19V5M5 12l7-7 7 7'],
                            'warning' => ['bg-warn-tint', 'text-warn', 'M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A2 2 0 0 0 4 21h16a2 2 0 0 0 1.89-2.96L13.71 3.86a2 2 0 0 0-3.42 0z'],
                            'cart' => ['bg-danger-tint', 'text-danger', 'M6 2h12l2 5H4zM5 7v13h14V7M9 11a3 3 0 0 0 6 0'],
                            default => ['bg-accent-tint', 'text-accent', 'M20 6L9 17l-5-5'],
                        };
                        $unread = is_null($n->read_at);
                    @endphp
                    <div class="w-full flex items-start gap-2 pl-4 pr-2.5 py-2.5 hover:bg-chip2 transition-colors group">
                        <button wire:click="markAsRead('{{ $n->id }}')" @click="open = false"
                            class="flex-1 min-w-0 flex items-start gap-3 text-left">
                            <span class="w-9 h-9 shrink-0 rounded-full {{ $iconBg }} {{ $iconFg }} flex items-center justify-center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $iconPath }}"></path></svg>
                            </span>
                            <div class="flex-1 min-w-0 flex flex-col gap-0.5">
                                <span class="text-[13px] leading-snug {{ $unread ? 'font-semibold text-text' : 'text-text3' }}">{{ $n->data['title'] ?? '' }}</span>
                                @if (! empty($n->data['body']))
                                    <span class="text-[12px] text-muted2 truncate">{{ $n->data['body'] }}</span>
                                @endif
                                <span class="text-[11px] text-accent">{{ \App\Support\ThaiTime::diffForHumans($n->created_at) }}</span>
                            </div>
                            @if ($unread)
                                <span class="w-2 h-2 rounded-full bg-accent shrink-0 mt-1.5"></span>
                            @endif
                        </button>
                        <button wire:click="delete('{{ $n->id }}')" title="ลบการแจ้งเตือนนี้"
                            class="shrink-0 w-6 h-6 rounded-lg flex items-center justify-center text-muted3 opacity-0 group-hover:opacity-100 hover:bg-danger-tint hover:text-danger">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                        </button>
                    </div>
                @endforeach
            @empty
                <div class="px-4 py-10 text-center text-[12.5px] text-muted2">ไม่มีการแจ้งเตือน</div>
            @endforelse
        </div>
    </div>

    {{-- Notification settings --}}
    @if ($showSettings)
        <div wire:click="closeSettings" class="fixed inset-0 bg-black/40 z-[95] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[400px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[17px] font-semibold tracking-tight">ตั้งค่าการแจ้งเตือน</span>
                        <span class="text-[12.5px] text-muted2">เลือกเรื่องที่อยากได้รับการแจ้งเตือน</span>
                    </div>
                    <button wire:click="closeSettings" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>
                <div class="flex flex-col gap-1">
                    @foreach ($notificationTypes as $type)
                        <label class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] hover:bg-chip2 transition-colors cursor-pointer">
                            <input type="checkbox" wire:model="enabledTypes" value="{{ $type->value }}"
                                class="w-[17px] h-[17px] rounded-[5px] border-border4 text-accent focus:ring-accent focus:outline-none">
                            <span class="text-[13px] text-text2">{{ $type->label() }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="saveSettings" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">บันทึก</button>
                    <button wire:click="closeSettings" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif
</div>
