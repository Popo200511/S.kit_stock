<div class="flex flex-col gap-3.5">

    {{-- Start new count --}}
    <div class="bg-surface border border-border rounded-[15px] p-4.5 shadow-sm flex flex-wrap gap-3.5 items-center">
        <div class="flex-1 min-w-[200px] flex flex-col gap-0.5 leading-relaxed">
            <span class="text-[14.5px] font-semibold">นับสต็อกจริงเทียบระบบ</span>
            <span class="text-[12.5px] text-muted2">เลือกประเภทที่จะนับ กรอกจำนวนจริง ระบบคำนวณผลต่างและปรับยอดให้ทันที</span>
        </div>
        @can('stock_count')
            <div class="w-[190px]">
                <x-combobox field="newCategoryId" :options="$categoryOptions" placeholder="เลือกประเภท" />
            </div>
            <button wire:click="startCount" @if($draft) disabled @endif
                class="px-4.5 py-2.5 rounded-[10px] bg-accent text-white text-[13px] font-medium hover:bg-accent-hover disabled:opacity-40 disabled:cursor-not-allowed">เริ่มนับรอบใหม่</button>
        @endcan
    </div>

    @if ($startError)
        <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3.5 py-2.5">{{ $startError }}</span>
    @endif

    {{-- Active draft: count modal --}}
    @if ($draft)
        @php
            $mismatchCount = 0;
            $varianceValue = 0;
            $countedDone = 0;
            foreach ($draft->lines as $l) {
                $real = $realQty[$l->id] ?? null;
                if ($real !== null && $real !== '') {
                    $countedDone++;
                    $diff = (int) $real - $l->system_qty;
                    if ($diff !== 0) {
                        $mismatchCount++;
                        $varianceValue += $diff * ($l->product?->cost ?? 0);
                    }
                }
            }
            $countedTotal = $draft->lines->count();
            $visibleLines = $countSearch === ''
                ? $draft->lines
                : $draft->lines->filter(fn ($l) => str_contains(mb_strtolower($l->product_name), mb_strtolower($countSearch)));
        @endphp
        <div class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div class="w-full max-w-[560px] max-h-[88vh] bg-surface rounded-2xl shadow-2xl flex flex-col overflow-hidden">
                <div class="p-5 border-b border-line flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-0.5 leading-relaxed">
                        <span class="text-[17px] font-semibold">นับสต็อก · {{ $draft->scope_label }}</span>
                        <span class="text-[12.5px] text-muted2">กรอกจำนวนที่นับได้จริง ช่องที่ไม่กรอกถือว่าตรงกับระบบ</span>
                    </div>
                    @can('stock_count')
                        <button wire:click="askCancelDraft({{ $draft->id }})" class="text-danger hover:opacity-70 text-[19px] leading-none shrink-0">✕</button>
                    @endcan
                </div>

                <div class="px-5 pt-3.5 flex flex-col gap-2.5">
                    <div class="flex items-center gap-2 bg-surface2 border border-border2 rounded-[10px] px-3 py-2">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-muted3 shrink-0" stroke-width="1.9" stroke-linecap="round"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3"></path></svg>
                        <input type="text" wire:model.live.debounce.300ms="countSearch" placeholder="ค้นหาสินค้าในรอบนับนี้"
                            class="flex-1 min-w-0 text-[13px] border-0 p-0 focus:ring-0 focus:outline-none bg-transparent">
                        @if ($countSearch !== '')
                            <button wire:click="$set('countSearch', '')" class="text-muted3 hover:text-text shrink-0">✕</button>
                        @endif
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex-1 h-1.5 rounded-full bg-hairline overflow-hidden">
                            <div class="h-full rounded-full bg-accent" style="width:{{ $countedTotal > 0 ? round($countedDone / $countedTotal * 100) : 0 }}%"></div>
                        </div>
                        <span class="text-[11.5px] text-muted2 tabular-nums whitespace-nowrap">นับแล้ว {{ $countedDone }}/{{ $countedTotal }}</span>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-hairline2 mt-1">
                    @forelse ($visibleLines as $line)
                        @php
                            $real = $realQty[$line->id] ?? null;
                            $diff = ($real === null || $real === '') ? null : ((int) $real - $line->system_qty);
                        @endphp
                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="flex-1 min-w-0 leading-snug">
                                <div class="font-medium text-[14.5px] truncate">{{ $line->product_name }}</div>
                                <div class="text-[12px] text-muted2">ระบบ {{ $line->system_qty }} {{ $line->product?->unit?->name }}</div>
                            </div>
                            <input type="number" wire:model.live.debounce.500ms="realQty.{{ $line->id }}" placeholder="{{ $line->system_qty }}"
                                @cannot('stock_count') disabled @endcannot
                                class="w-[120px] border border-border3 rounded-[10px] px-3 py-2.5 text-right text-[14px] tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                            <span @class([
                                'w-12 shrink-0 text-right font-semibold text-[13.5px] tabular-nums',
                                'text-muted3' => $diff === null,
                                'text-accent' => $diff !== null && $diff >= 0,
                                'text-danger' => $diff !== null && $diff < 0,
                            ])>{{ $diff === null ? '—' : ($diff > 0 ? '+'.$diff : $diff) }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-9 text-center text-[13px] text-muted2">ไม่พบสินค้าที่ตรงกับคำค้นหา</div>
                    @endforelse
                </div>

                <div class="flex items-center justify-between px-5 py-3.5 border-t border-line text-[13.5px]">
                    <span class="text-muted2">รายการที่ไม่ตรง {{ $mismatchCount }} รายการ</span>
                    <span @class([
                        'font-semibold tabular-nums',
                        'text-accent' => $varianceValue >= 0,
                        'text-danger' => $varianceValue < 0,
                    ])>มูลค่าส่วนต่าง {{ $varianceValue > 0 ? '+' : '' }}{{ number_format($varianceValue, 0) }} บาท</span>
                </div>

                @can('stock_count')
                    <div class="flex gap-2.5 p-5 pt-0">
                        <button wire:click="completeCount({{ $draft->id }})" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">ปิดรอบและปรับยอดตามจริง</button>
                        <button wire:click="askCancelDraft({{ $draft->id }})" class="px-5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                    </div>
                @endcan
            </div>
        </div>
    @endif

    {{-- Completed history --}}
    @foreach ($completed as $count)
        @php
            $totalDiff = $count->lines->sum(fn ($l) => $l->real_qty === null ? 0 : $l->real_qty - $l->system_qty);
            $isHighlighted = $highlightCountId && $count->id === $highlightCountId;
        @endphp
        <div @if ($isHighlighted) x-data x-init="setTimeout(() => $el.scrollIntoView({ behavior: 'instant', block: 'center' }), 300)" @endif
            @class(['bg-surface rounded-[15px] shadow-sm transition-colors', 'border border-border' => ! $isHighlighted, 'border-2 border-accent ring-2 ring-accent bg-accent-tint' => $isHighlighted])>
            <div class="p-4 border-b border-line flex flex-wrap gap-3 items-center">
                <div class="flex-1 min-w-[170px] flex flex-col leading-snug">
                    <span class="text-[13.5px] font-semibold tabular-nums">{{ $count->count_no }}</span>
                    <span class="text-[11.5px] text-muted2">{{ $count->date->format('d/m/Y') }} · {{ $count->scope_label }} · โดย {{ $count->user?->name }}</span>
                </div>
                <span class="text-[11.5px] font-medium px-2.5 py-1 rounded-full bg-sunken3 text-text4 whitespace-nowrap">ปิดรอบแล้ว</span>
                <span @class(['text-[12.5px] font-semibold tabular-nums whitespace-nowrap', 'text-accent' => $totalDiff >= 0, 'text-danger' => $totalDiff < 0])>ผลต่างรวม {{ $totalDiff > 0 ? '+'.$totalDiff : $totalDiff }}</span>
            </div>
            <div class="divide-y divide-hairline2">
                @foreach ($count->lines as $line)
                    @php $diff = $line->real_qty === null ? null : $line->real_qty - $line->system_qty; @endphp
                    <div class="grid grid-cols-[1fr_.8fr_.8fr_.7fr] gap-3 px-4.5 py-2.5 items-center text-[12.5px]">
                        <span class="min-w-0 truncate">{{ $line->product_name }}</span>
                        <span class="text-right text-muted2 tabular-nums whitespace-nowrap">ระบบ {{ $line->system_qty }}</span>
                        <span class="text-right tabular-nums whitespace-nowrap">{{ $diff === null ? 'ไม่ได้นับ' : 'นับจริง '.$line->real_qty }}</span>
                        <span @class([
                            'text-right font-semibold tabular-nums whitespace-nowrap',
                            'text-muted3' => $diff === null,
                            'text-accent' => $diff !== null && $diff >= 0,
                            'text-danger' => $diff !== null && $diff < 0,
                        ])>{{ $diff === null ? '—' : ($diff > 0 ? '+'.$diff : $diff) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    @if (! $draft && $completed->isEmpty())
        <div class="bg-surface border border-border rounded-[15px] p-10 text-center text-sm text-muted2">ยังไม่มีประวัติการนับสต็อก</div>
    @endif

    {{-- Cancel confirm --}}
    @if ($confirmCancel)
        <div wire:click="cancelCancelDraft" class="fixed inset-0 bg-black/45 z-[95] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ยกเลิกรอบนับ &ldquo;{{ $confirmCancel->count_no }}&rdquo;?</span>
                        <span class="text-[12.5px] text-muted">จำนวนที่กรอกไว้จะหายไปทั้งหมด ยอดคงเหลือในระบบจะไม่ถูกเปลี่ยนแปลง</span>
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="cancelCancelDraft" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ปิดหน้าต่าง</button>
                    <button wire:click="cancelDraft" class="flex-1 py-2.5 rounded-[10px] bg-danger text-white text-[13px] font-medium hover:bg-danger-ink2">ยกเลิกรอบนับ</button>
                </div>
            </div>
        </div>
    @endif
</div>
