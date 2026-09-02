<div class="flex flex-col gap-3.5">

    <div class="flex flex-wrap items-start gap-3 bg-warn-bg border border-warn-border rounded-2xl px-4.5 py-4">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-warn shrink-0 mt-0.5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 16H3zM12 9v5M12 17h.01"></path></svg>
        <div class="flex flex-col gap-0.5 leading-relaxed">
            <span class="text-[13.5px] font-semibold text-warn-ink">สินค้า {{ $lowCount }} รายการถึงหรือต่ำกว่าจุดสั่งซื้อ · มูลค่าที่ควรสั่ง {{ number_format($suggestValue) }} บาท</span>
            <span class="text-[12.5px] text-warn-ink2">ระบบแนะนำจำนวนสั่งซื้อจากจุดสั่งซื้อขั้นต่ำ × 2 ลบยอดคงเหลือ</span>
        </div>

        @if ($lowCount > 0)
            <div class="flex flex-wrap items-center gap-2 ml-auto">
                @can('stock_movements')
                    <button wire:click="openReceiptModal" wire:loading.attr="disabled" class="flex items-center gap-1.5 px-3.5 py-2 rounded-[10px] bg-warn text-white text-[12.5px] font-medium hover:opacity-90 disabled:opacity-50 whitespace-nowrap">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"></path></svg>
                        สร้างใบรับเข้ารวมทุกรายการ
                    </button>
                @endcan
                @canany(['view_reports', 'edit_products'])
                    <button wire:click="exportExcel" class="flex items-center gap-1.5 px-3 py-2 rounded-[10px] border border-warn-border bg-surface text-[12.5px] font-medium text-warn-ink hover:bg-surface4 whitespace-nowrap">
                        <span class="w-5 h-5 rounded-md bg-accent-tint text-accent flex items-center justify-center text-[9px] font-semibold">XLS</span>ส่งออก
                    </button>
                    <button wire:click="exportPdf" class="flex items-center gap-1.5 px-3 py-2 rounded-[10px] border border-warn-border bg-surface text-[12.5px] font-medium text-warn-ink hover:bg-surface4 whitespace-nowrap">
                        <span class="w-5 h-5 rounded-md bg-danger-tint text-danger flex items-center justify-center text-[9px] font-semibold">PDF</span>ส่งออก
                    </button>
                @endcanany
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @forelse ($products as $product)
            @php
                $status = $frozenStatus[$product->id];
                $autoSuggestQty = max(0, $product->reorder_point * 2 - $product->stock);
                $isOverridden = $product->suggested_reorder_qty !== null;
                $suggestQty = $product->suggested_reorder_qty ?? $autoSuggestQty;
                $isHighlighted = $highlightProductId && $product->id === $highlightProductId;
            @endphp
            <div @if ($isHighlighted) x-data x-init="setTimeout(() => $el.scrollIntoView({ behavior: 'instant', block: 'center' }), 300)" @endif
                @class([
                    'bg-surface rounded-[14px] p-4.5 shadow-sm flex flex-col gap-3.5 transition-colors',
                    'border border-border border-l-4' => ! $isHighlighted,
                    'border-2 border-accent ring-2 ring-accent bg-accent-tint' => $isHighlighted,
                    'border-l-warn' => $status['tone'] === 'warn' && ! $isHighlighted,
                    'border-l-danger' => $status['tone'] === 'danger' && ! $isHighlighted,
                ])>
                <div class="flex items-start justify-between gap-2.5">
                    <div class="flex flex-col leading-snug min-w-0">
                        <span class="text-[13.5px] font-medium truncate">{{ $product->name }}</span>
                        <span class="text-[11.5px] text-muted2 tabular-nums">{{ $product->sku }} · {{ $product->category?->name }}</span>
                    </div>
                    <span @class(['shrink-0 text-[11px] font-medium px-2.5 py-0.5 rounded-full whitespace-nowrap', 'bg-warn-tint text-warn' => $status['tone'] === 'warn', 'bg-danger-tint text-danger' => $status['tone'] === 'danger'])>{{ $status['label'] }}</span>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex items-end justify-between gap-3">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[11px] text-muted2 whitespace-nowrap">คงเหลือ</span>
                            <span @class(['text-[21px] font-semibold tabular-nums tracking-tight', 'text-warn' => $status['tone'] === 'warn', 'text-danger' => $status['tone'] === 'danger'])>{{ $product->stock_display }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5 items-end">
                            <span class="text-[11px] text-muted2 whitespace-nowrap">จุดสั่งซื้อขั้นต่ำ</span>
                            <div class="flex items-center gap-2">
                                @can('edit_products')
                                    <button wire:click="decrementReorder({{ $product->id }})" class="w-[26px] h-[26px] shrink-0 border border-border4 rounded-lg flex items-center justify-center text-text4 hover:border-accent hover:text-accent">−</button>
                                    <input type="number" min="0" inputmode="numeric"
                                        wire:model.blur="reorderPointInput.{{ $product->id }}"
                                        wire:blur="saveReorderPoint({{ $product->id }})"
                                        class="w-12 shrink-0 text-sm font-semibold tabular-nums text-center border border-border3 rounded-lg px-1 py-1 focus:border-accent focus:ring-0 focus:outline-none">
                                    <button wire:click="incrementReorder({{ $product->id }})" class="w-[26px] h-[26px] shrink-0 border border-border4 rounded-lg flex items-center justify-center text-text4 hover:border-accent hover:text-accent">+</button>
                                @else
                                    <span class="text-sm font-semibold tabular-nums min-w-[24px] text-center">{{ $product->reorder_point }}</span>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2.5 pt-3 border-t border-hairline">
                        <span class="text-[11px] text-muted2 whitespace-nowrap shrink-0">แนะนำสั่ง</span>
                        @can('edit_products')
                            <div class="flex items-center gap-2 min-w-0">
                                @if ($isOverridden)
                                    <button wire:click="resetSuggestQty({{ $product->id }})" class="text-[10px] text-accent hover:underline whitespace-nowrap shrink-0">อัตโนมัติ {{ $autoSuggestQty }}</button>
                                @endif
                                <input type="number" min="0" inputmode="numeric"
                                    wire:model.blur="suggestQtyInput.{{ $product->id }}"
                                    wire:blur="saveSuggestQty({{ $product->id }})"
                                    class="w-14 shrink-0 text-right text-sm font-semibold tabular-nums border border-border3 rounded-lg px-1.5 py-1 focus:border-accent focus:ring-0 focus:outline-none">
                                <span class="text-[11px] text-muted2 whitespace-nowrap shrink-0">{{ $product->unit?->name }}</span>
                            </div>
                        @else
                            <span class="text-sm font-semibold tabular-nums whitespace-nowrap">{{ $suggestQty }} {{ $product->unit?->name }}</span>
                        @endcan
                    </div>
                </div>

                @can('stock_movements')
                    <button wire:click="openReceiptModal({{ $product->id }})"
                        class="flex items-center justify-center text-center py-2.5 rounded-[9px] border border-accent text-accent text-[12.5px] font-medium hover:bg-accent-tint">สร้างใบรับเข้าตามจำนวนแนะนำ</button>
                @endcan
            </div>
        @empty
            <div class="col-span-full bg-surface border border-border rounded-[15px] p-10 text-center text-sm text-muted2">ยังไม่มีสินค้าที่ต่ำกว่าจุดสั่งซื้อ</div>
        @endforelse
    </div>

    {{-- Quick receipt modal --}}
    @if ($showReceiptModal)
        <div wire:click="closeReceiptModal" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[560px] max-h-[92vh] overflow-y-auto bg-surface rounded-2xl shadow-2xl flex flex-col">
                <div class="sticky top-0 z-10 bg-surface rounded-t-2xl flex items-start justify-between gap-3 px-5 pt-5 pb-3 border-b border-hairline2">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[17px] font-semibold tracking-tight">สร้างใบรับเข้า</span>
                        <span class="text-[12px] text-muted2">ตรวจสอบและแก้ไขจำนวนก่อนบันทึกได้ · ราคาต้นทุนดึงจากระบบ</span>
                    </div>
                    <button wire:click="closeReceiptModal" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>

                <div class="flex flex-col gap-4 px-5 pb-5">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[12.5px] font-medium text-text2">วันที่</label>
                            <input type="date" lang="en-GB" wire:model="receiptForm.date" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[12.5px] font-medium text-text2">ผู้จำหน่าย (ถ้ามี)</label>
                            <input type="text" wire:model="receiptForm.party" placeholder="ชื่อผู้จำหน่าย" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                        </div>
                    </div>

                    @if ($receiptError)
                        <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3.5 py-2.5">{{ $receiptError }}</span>
                    @endif

                    @if (! empty($receiptLines))
                        <div class="border border-line rounded-[13px] overflow-hidden">
                            @foreach ($receiptLines as $i => $l)
                                <div wire:key="receipt-line-{{ $i }}" class="flex items-center gap-2.5 px-3.5 py-2.5 {{ ! $loop->last ? 'border-b border-hairline2' : '' }}">
                                    <div class="flex-1 min-w-0 flex flex-col leading-snug">
                                        <span class="text-[13px] font-medium truncate">{{ $l['name'] }}</span>
                                        <span class="text-[11px] text-muted2">{{ $l['category_name'] }}</span>
                                    </div>
                                    <input type="number" min="1" wire:model.live="receiptLines.{{ $i }}.qty"
                                        class="w-16 shrink-0 text-right text-[13px] font-medium tabular-nums border border-border3 rounded-lg px-2 py-1.5 focus:border-accent focus:ring-0 focus:outline-none">
                                    <span class="text-[11px] text-muted2 shrink-0 w-10">{{ $l['unit'] }}</span>
                                    <span title="ราคาต้นทุนต่อหน่วยจากระบบ แก้ไขไม่ได้จากหน้านี้" class="w-20 shrink-0 text-right text-[13px] tabular-nums text-text2 px-2 py-1.5">{{ number_format($l['unit_price'], 2) }}</span>
                                    <button type="button" wire:click="removeReceiptLine({{ $i }})" class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-muted hover:bg-danger-tint hover:text-danger">✕</button>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-between px-1 text-[13px]">
                            <span class="text-muted2">มูลค่ารวม</span>
                            <span class="font-semibold tabular-nums">{{ number_format($this->receiptTotal(), 2) }} บาท</span>
                        </div>
                    @endif

                    <div class="flex gap-2.5">
                        <button wire:click="saveReceipt" wire:loading.attr="disabled" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover disabled:opacity-50">บันทึกใบรับเข้า</button>
                        <button wire:click="closeReceiptModal" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
