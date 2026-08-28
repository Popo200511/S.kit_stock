<div class="flex flex-col gap-3.5">
    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach ($kpis as $k)
            <div class="bg-surface border border-border rounded-[15px] p-4 shadow-sm flex flex-col gap-1.5">
                <span class="text-[12.5px] text-muted font-medium">{{ $k['label'] }}</span>
                <span class="text-[19px] font-semibold tracking-tight tabular-nums">{{ $k['value'] }}</span>
            </div>
        @endforeach
    </div>

    @can('edit_products')
        <div class="flex justify-end">
            <button wire:click="openCreate" class="flex items-center gap-1.5 px-4 py-2.5 rounded-[10px] bg-accent text-white text-[12.5px] font-medium hover:bg-accent-hover">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>เพิ่มประเภท
            </button>
        </div>
    @endcan

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ($categories as $row)
            <div class="bg-surface border border-border rounded-[15px] p-4.5 shadow-sm hover:border-accent-border2 flex flex-col gap-3.5">
                <div class="flex items-center gap-2.5">
                    <span class="w-[33px] h-[33px] shrink-0 rounded-[9px] bg-accent-tint text-accent flex items-center justify-center">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8"></path></svg>
                    </span>
                    <div class="flex-1 min-w-0 flex flex-col leading-snug">
                        <span class="text-[13.5px] font-semibold truncate">{{ $row['category']->name }}</span>
                        <span class="text-[11.5px] text-muted2 tabular-nums">{{ $row['count'] }} รายการ · กำไรเฉลี่ย {{ number_format($row['avgMargin'], 0) }}%</span>
                    </div>
                    @can('edit_products')
                        <button wire:click="openEdit({{ $row['category']->id }})" title="แก้ไขชื่อประเภท" class="w-[27px] h-[27px] shrink-0 rounded-lg flex items-center justify-center text-muted2 hover:bg-hairline hover:text-accent">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                        </button>
                        <button wire:click="askDelete({{ $row['category']->id }})" title="ลบประเภทนี้" class="w-[27px] h-[27px] shrink-0 rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                        </button>
                    @endcan
                </div>
                <div class="flex justify-between gap-2.5 border-t border-hairline pt-3.5">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[11px] text-muted2">มูลค่าสต็อก</span>
                        <span class="text-[15px] font-semibold tabular-nums">{{ number_format($row['stockValue'], 0) }} บาท</span>
                    </div>
                    <div class="flex flex-col gap-0.5 text-right">
                        <span class="text-[11px] text-muted2">ใกล้หมด</span>
                        <span class="text-[15px] font-semibold tabular-nums {{ $row['lowCount'] > 0 ? 'text-warn' : 'text-text' }}">{{ $row['lowCount'] }}</span>
                    </div>
                </div>
                <a href="{{ route('products.index', ['category' => $row['category']->id]) }}" wire:navigate
                    class="flex items-center justify-center gap-1 text-[12px] font-medium text-accent hover:underline">
                    ดูสินค้าในประเภทนี้
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"></path></svg>
                </a>
            </div>
        @endforeach
    </div>

    @if ($categories->isEmpty())
        <div class="bg-surface border border-border rounded-[15px] p-10 text-center text-sm text-muted2">ยังไม่มีประเภทสินค้า</div>
    @endif

    {{-- Add/Rename modal --}}
    @if ($showForm)
        <div wire:click="closeForm" class="fixed inset-0 bg-black/40 z-[88] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[396px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <span class="text-[17px] font-semibold tracking-tight">{{ $editingId ? 'แก้ไขชื่อประเภท' : 'เพิ่มประเภทใหม่' }}</span>
                    <button wire:click="closeForm" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">ชื่อประเภท</label>
                    <input type="text" wire:model="name" wire:keydown.enter="save" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                </div>
                @if ($formError)
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3 py-2.5">{{ $formError }}</span>
                @endif
                <div class="flex gap-2.5">
                    <button wire:click="save" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">{{ $editingId ? 'บันทึกชื่อใหม่' : 'เพิ่มประเภท' }}</button>
                    <button wire:click="closeForm" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirm --}}
    @if ($deleteCategory)
        <div wire:click="cancelDelete" class="fixed inset-0 bg-black/45 z-[92] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ลบ &ldquo;{{ $deleteCategory->name }}&rdquo;?</span>
                        @if ($deleteCategory->products_count > 0)
                            <span class="text-[12.5px] text-warn">ลบไม่ได้ เพราะยังมีสินค้า {{ $deleteCategory->products_count }} รายการอยู่ในประเภทนี้</span>
                        @else
                            <span class="text-[12.5px] text-muted">ประเภทนี้ไม่มีสินค้าอยู่ ลบได้ทันที</span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="cancelDelete" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ยกเลิก</button>
                    @if ($deleteCategory->products_count === 0)
                        <button wire:click="delete" class="flex-1 py-2.5 rounded-[10px] bg-danger text-white text-[13px] font-medium hover:bg-danger-ink2">ลบประเภท</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
