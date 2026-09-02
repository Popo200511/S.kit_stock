<div class="flex flex-col gap-3.5 md:h-full md:overflow-y-auto" x-data="{ toolsOpen: false }">

    {{-- KPIs --}}
    @php
        $mvBrowseSeed = $selectedMonth ?? now()->format('Y-m');
        $mvBrowseInitYear = (int) substr($mvBrowseSeed, 0, 4);
        $mvBrowseInitMonth = (int) substr($mvBrowseSeed, 5, 2);
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
        <div class="bg-surface border border-border rounded-[12px] p-3 shadow-sm flex flex-col gap-1" x-data="{
                monthOpen: false,
                browseYear: {{ $mvBrowseInitYear }},
                browseMonth: {{ $mvBrowseInitMonth }},
                monthNames: ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'],
                openPicker() {
                    this.monthOpen = !this.monthOpen;
                    const sm = this.$wire.selectedMonth;
                    const now = new Date();
                    if (sm) {
                        const [y, m] = sm.split('-');
                        this.browseYear = parseInt(y);
                        this.browseMonth = parseInt(m);
                    } else {
                        this.browseYear = now.getFullYear();
                        this.browseMonth = now.getMonth() + 1;
                    }
                },
            }">
            <span class="text-[11px] text-muted font-medium">{{ $kpis[0]['label'] }}</span>
            <span class="text-[15.5px] font-semibold tracking-tight tabular-nums">{{ $kpis[0]['value'] }}</span>
            <div class="flex items-center justify-between gap-2">
                @if ($kpis[0]['delta'] ?? null)
                    <span @class(['text-[10.5px] font-medium whitespace-nowrap', 'text-accent' => $kpis[0]['delta']['tone'] === 'accent', 'text-danger' => $kpis[0]['delta']['tone'] === 'danger'])>{{ $kpis[0]['delta']['text'] }}</span>
                @else
                    <span></span>
                @endif
                <div class="relative">
                    <button @click="openPicker()" title="เลือกเดือน" class="w-6 h-6 rounded-lg flex items-center justify-center text-muted2 hover:bg-sunken hover:text-accent">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
                    </button>
                    <div x-show="monthOpen" @click.outside="monthOpen = false" x-cloak
                        class="absolute top-full right-0 mt-2 z-30 bg-surface border border-border2 rounded-xl shadow-lg p-3 flex flex-col gap-2 w-[218px]">
                        <button wire:click="setAllTime" @click="monthOpen = false"
                            class="w-full py-1.5 rounded-lg text-[12px] font-medium {{ $selectedMonth === null ? 'bg-accent text-white' : 'border border-border4 text-text3 hover:bg-sunken' }}">ทั้งหมด (ทุกช่วงเวลา)</button>
                        <div class="flex items-center justify-between">
                            <button @click="browseYear--" title="ปีก่อนหน้า" class="w-7 h-7 rounded-lg flex items-center justify-center text-text3 hover:bg-sunken hover:text-accent">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"></path></svg>
                            </button>
                            <span class="text-[13px] font-semibold tabular-nums" x-text="browseYear + 543"></span>
                            <button @click="browseYear++" :disabled="browseYear >= {{ now()->year }}" title="ปีถัดไป"
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-text3 hover:bg-sunken hover:text-accent disabled:opacity-30 disabled:pointer-events-none">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"></path></svg>
                            </button>
                        </div>
                        <div class="grid grid-cols-4 gap-1.5">
                            @foreach (['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'] as $i => $mLabel)
                                @php $mNum = $i + 1; @endphp
                                <button
                                    @click="$wire.selectMonth(browseYear + '-' + String({{ $mNum }}).padStart(2, '0'))"
                                    :disabled="browseYear > {{ now()->year }} || (browseYear === {{ now()->year }} && {{ $mNum }} > {{ now()->month }})"
                                    :class="(browseYear + '-' + String({{ $mNum }}).padStart(2, '0')) === '{{ $selectedMonth }}' ? 'bg-accent text-white' : 'text-text3 hover:bg-sunken'"
                                    class="py-1.5 rounded-lg text-[12px] font-medium transition-colors disabled:opacity-25 disabled:pointer-events-none"
                                >{{ $mLabel }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @foreach ([$kpis[1], $kpis[2]] as $k)
            <div class="bg-surface border border-border rounded-[12px] p-3 shadow-sm flex flex-col gap-1">
                <span class="text-[11px] text-muted font-medium">{{ $k['label'] }}</span>
                <span class="text-[15.5px] font-semibold tracking-tight tabular-nums">{{ $k['value'] }}</span>
                @if ($k['delta'] ?? null)
                    <span @class(['text-[10.5px] font-medium whitespace-nowrap', 'text-accent' => $k['delta']['tone'] === 'accent', 'text-danger' => $k['delta']['tone'] === 'danger'])>{{ $k['delta']['text'] }}</span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap gap-2.5 items-center">
        <div class="flex gap-1 bg-chip p-[3px] rounded-[9px]">
            <button wire:click="setTypeTab('all')" class="px-4 py-1.5 rounded-[7px] text-[12.5px] font-medium {{ $typeTab === 'all' ? 'bg-surface shadow-sm' : 'text-muted2' }}">ทั้งหมด</button>
            <button wire:click="setTypeTab('in')" class="px-4 py-1.5 rounded-[7px] text-[12.5px] font-medium {{ $typeTab === 'in' ? 'bg-surface shadow-sm' : 'text-muted2' }}">รับเข้า</button>
            <button wire:click="setTypeTab('out')" class="px-4 py-1.5 rounded-[7px] text-[12.5px] font-medium {{ $typeTab === 'out' ? 'bg-surface shadow-sm' : 'text-muted2' }}">เบิกออก</button>
        </div>

        <div class="flex-1 min-w-[160px] flex items-center gap-2 bg-surface border border-border2 rounded-[10px] px-3 py-2 shadow-sm focus-within:border-accent">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-muted3" stroke-width="1.9" stroke-linecap="round"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3"></path></svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="ค้นหาเลขที่, สินค้า หรือคู่ค้า" class="flex-1 min-w-0 text-[13px] border-0 p-0 focus:ring-0 focus:outline-none bg-transparent">
        </div>

        <div class="flex gap-1 bg-chip p-[3px] rounded-[9px]">
            <button wire:click="setView('table')" class="px-3.5 py-1.5 rounded-[7px] text-[12.5px] font-medium {{ $view === 'table' ? 'bg-surface shadow-sm' : 'text-muted2' }}">ตาราง</button>
            <button wire:click="setView('card')" class="px-3.5 py-1.5 rounded-[7px] text-[12.5px] font-medium {{ $view === 'card' ? 'bg-surface shadow-sm' : 'text-muted2' }}">การ์ด</button>
        </div>

        @can('stock_movements')
            <button wire:click="openCreate" class="flex items-center gap-1.5 px-4 py-2.5 rounded-[10px] bg-accent text-white text-[12.5px] font-medium hover:bg-accent-hover">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>เพิ่มรายการ
            </button>
        @endcan

        @if ($this->hasChipFilters())
            <button wire:click="clearFilters" class="flex items-center gap-1.5 px-3 py-2 rounded-[10px] border border-danger-border text-xs text-danger hover:bg-danger-tint2">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"></path></svg>ล้างตัวกรอง
            </button>
        @endif

        <div class="relative">
            <button @click="toolsOpen = !toolsOpen" class="flex items-center gap-1.5 px-3.5 py-2.5 rounded-[10px] border border-border4 text-[12.5px] text-text2 hover:border-accent hover:text-accent">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v14M8 3L4 7M8 3l4 4M16 21V7M16 21l4-4M16 21l-4-4"></path></svg>นำเข้า/ส่งออก
            </button>
            <div x-show="toolsOpen" @click.outside="toolsOpen = false" x-cloak class="absolute top-11 right-0 w-[200px] bg-surface border border-border2 rounded-xl shadow-lg z-30 overflow-hidden">
                @if (auth()->user()->isOwner())
                    <button wire:click="openImportModal" class="w-full flex items-center gap-2 px-3.5 py-2.5 text-[12.5px] text-text2 border-b border-hairline hover:bg-surface4">
                        <span class="w-6 h-6 rounded-[7px] bg-chip text-text2 flex items-center justify-center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9M12 9l-4 4M12 9l4 4M4 3h16"></path></svg>
                        </span>นำเข้าจาก Excel
                    </button>
                @endif
                <button wire:click="exportExcel" class="w-full flex items-center gap-2 px-3.5 py-2.5 text-[12.5px] text-text2 border-b border-hairline hover:bg-surface4">
                    <span class="w-6 h-6 rounded-[7px] bg-accent-tint text-accent flex items-center justify-center text-[10px] font-semibold">XLS</span>ส่งออก Excel (.xlsx)
                </button>
                <button wire:click="exportPdf" class="w-full flex items-center gap-2 px-3.5 py-2.5 text-[12.5px] text-text2 hover:bg-surface4">
                    <span class="w-6 h-6 rounded-[7px] bg-danger-tint text-danger flex items-center justify-center text-[10px] font-semibold">PDF</span>ส่งออก PDF / พิมพ์
                </button>
            </div>
        </div>
    </div>

    {{-- Table view --}}
    @if ($view === 'table')
        <div class="table-scroll-container bg-surface border border-border rounded-[15px] shadow-sm overflow-auto max-h-[65vh] md:max-h-none md:flex-1 md:min-h-0">
            <table class="w-full text-[12.5px]">
                <thead>
                    <tr class="sticky top-0 z-20 bg-surface2 border-b border-line text-[11px] font-semibold tracking-wide text-muted2">
                        <th class="text-left px-4 py-2.5 relative whitespace-nowrap">
                            <span>เลขที่</span>
                            <x-excel-filter field="docNo" :options="$columnOptionsMap['docNo']" :selected="$columnFilters['docNo'] ?? null" align="left" wire:key="filter-docNo-{{ $typeTab }}-{{ $selectedMonth }}" />
                        </th>
                        <th class="text-left px-4 py-2.5 relative whitespace-nowrap">
                            <span>วันที่</span>
                            <x-excel-filter field="date" :options="$columnOptionsMap['date']" :selected="$columnFilters['date'] ?? null" align="left" wire:key="filter-date-{{ $typeTab }}-{{ $selectedMonth }}" />
                        </th>
                        <th class="text-left px-4 py-2.5 whitespace-nowrap">
                            <span>เวลา</span>
                        </th>
                        <th class="text-left px-4 py-2.5 relative whitespace-nowrap">
                            <span>รายการสินค้า</span>
                            <x-excel-filter field="productName" :options="$columnOptionsMap['productName']" :selected="$columnFilters['productName'] ?? null" align="left" wire:key="filter-productName-{{ $typeTab }}-{{ $selectedMonth }}" />
                        </th>
                        <th class="text-left px-4 py-2.5 relative whitespace-nowrap">
                            <span>หมวด</span>
                            <x-excel-filter field="category" :options="$columnOptionsMap['category']" :selected="$columnFilters['category'] ?? null" align="left" wire:key="filter-category-{{ $typeTab }}-{{ $selectedMonth }}" />
                        </th>
                        <th class="text-right px-4 py-2.5 relative whitespace-nowrap">
                            <span>จำนวน</span>
                            <x-excel-filter field="qty" :options="$columnOptionsMap['qty']" :selected="$columnFilters['qty'] ?? null" align="right" wire:key="filter-qty-{{ $typeTab }}-{{ $selectedMonth }}" />
                        </th>
                        <th class="text-right px-4 py-2.5 relative whitespace-nowrap">
                            <span>ราคาหน่วย</span>
                            <x-excel-filter field="unitPrice" :options="$columnOptionsMap['unitPrice']" :selected="$columnFilters['unitPrice'] ?? null" align="right" wire:key="filter-unitPrice-{{ $typeTab }}-{{ $selectedMonth }}" />
                        </th>
                        <th class="text-right px-4 py-2.5 relative whitespace-nowrap">
                            <span>รวม</span>
                            <x-excel-filter field="amount" :options="$columnOptionsMap['amount']" :selected="$columnFilters['amount'] ?? null" align="right" wire:key="filter-amount-{{ $typeTab }}-{{ $selectedMonth }}" />
                        </th>
                        <th class="text-left px-4 py-2.5 relative whitespace-nowrap">
                            <span>ผู้ทำรายการ</span>
                            <x-excel-filter field="user" :options="$columnOptionsMap['user']" :selected="$columnFilters['user'] ?? null" align="left" wire:key="filter-user-{{ $typeTab }}-{{ $selectedMonth }}" />
                        </th>
                        <th class="text-left px-4 py-2.5 relative whitespace-nowrap">
                            <span>บันทึก</span>
                            <x-excel-filter field="note" :options="$columnOptionsMap['note']" :selected="$columnFilters['note'] ?? null" align="left" wire:key="filter-note-{{ $typeTab }}-{{ $selectedMonth }}" />
                        </th>
                        <th class="px-4 py-2.5 whitespace-nowrap"></th>
                    </tr>
                </thead>
                <tbody>
                    @php $scrolledToHighlight = false; @endphp
                    @forelse ($lines as $line)
                        @php
                            $isIn = $line->stockMovement->type === 'in';
                            $isHighlighted = $highlightDocId && $line->stock_movement_id === $highlightDocId;
                            $shouldScroll = $isHighlighted && ! $scrolledToHighlight;
                            if ($shouldScroll) { $scrolledToHighlight = true; }
                        @endphp
                        <tr @if($shouldScroll) x-data x-init="setTimeout(() => $el.scrollIntoView({ behavior: 'instant', block: 'center' }), 300)" @endif
                            @class(['border-b border-hairline2 hover:bg-surface2 transition-colors', 'bg-accent-tint' => $isHighlighted])>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <button wire:click="openDocument({{ $line->stock_movement_id }})" class="flex items-center gap-2 font-medium tabular-nums hover:text-accent">
                                    <span @class(['w-1.5 h-1.5 rounded-full', 'bg-accent' => $isIn, 'bg-bar-neutral' => ! $isIn])></span>
                                    {{ $line->stockMovement->doc_no }}
                                </button>
                            </td>
                            <td class="px-4 py-2.5 text-text4 tabular-nums whitespace-nowrap">{{ $line->stockMovement->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 text-text4 tabular-nums whitespace-nowrap">{{ $line->stockMovement->created_at->format('H:i') }}</td>
                            <td class="px-4 py-2.5">{{ $line->product_name }}</td>
                            <td class="px-4 py-2.5 text-muted whitespace-nowrap">{{ $line->category_name }}</td>
                            <td @class(['px-4 py-2.5 text-right font-semibold tabular-nums whitespace-nowrap', 'text-accent' => $isIn, 'text-text4' => ! $isIn])>{{ $isIn ? '+' : '-' }}{{ $line->qty }} {{ $line->unit }}</td>
                            <td class="px-4 py-2.5 text-right text-text4 tabular-nums whitespace-nowrap">{{ number_format($line->unit_price, 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold tabular-nums whitespace-nowrap">{{ number_format($line->line_total, 2) }}</td>
                            <td class="px-4 py-2.5 text-muted whitespace-nowrap">{{ $line->stockMovement->user?->name }}</td>
                            <td class="px-4 py-2.5 text-muted max-w-[160px]">
                                @if ($line->stockMovement->note)
                                    <div x-data="{ noteTip: false, tipStyle: '' }"
                                        @mouseenter="
                                            const r = $el.getBoundingClientRect();
                                            const nearRight = r.left > window.innerWidth * 0.55;
                                            tipStyle = nearRight
                                                ? `top:${r.bottom + 6}px; right:${window.innerWidth - r.right}px;`
                                                : `top:${r.bottom + 6}px; left:${r.left}px;`;
                                            noteTip = true;
                                        "
                                        @mouseleave="noteTip = false">
                                        <span class="block truncate">{{ $line->stockMovement->note }}</span>
                                        <div x-show="noteTip" x-cloak :style="tipStyle"
                                            class="fixed z-40 max-w-[280px] w-max bg-surface2 border border-border2 rounded-lg shadow-lg px-3 py-2 text-[12px] text-text2 leading-relaxed whitespace-normal">
                                            {{ $line->stockMovement->note }}
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @can('stock_movements')
                                    <div class="flex items-center gap-1">
                                        <button wire:click="openEditLine({{ $line->id }})" title="แก้ไขรายการนี้" class="w-6 h-6 rounded-lg flex items-center justify-center text-muted3 hover:bg-accent-tint hover:text-accent">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                                        </button>
                                        <button wire:click="askDeleteLine({{ $line->id }})" title="ลบรายการนี้" class="w-6 h-6 rounded-lg flex items-center justify-center text-muted3 hover:bg-danger-tint hover:text-danger">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                                        </button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center py-9 text-muted2">ไม่พบรายการที่ตรงกับตัวกรอง</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($hasMoreRows)
                <div wire:key="load-more-{{ $tablePerPage }}" x-data x-init="
                    const io = new IntersectionObserver((entries) => {
                        if (entries[0].isIntersecting) { $wire.loadMore(); io.disconnect(); }
                    }, { root: $el.closest('.table-scroll-container'), rootMargin: '200px' });
                    io.observe($el);
                " class="py-3.5 text-center text-[12px] text-muted2">กำลังโหลดเพิ่มเติม...</div>
            @endif
        </div>
    @endif

    {{-- Card view --}}
    @if ($view === 'card')
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
            @php $scrolledToHighlightCard = false; @endphp
            @forelse ($lines as $line)
                @php
                    $isIn = $line->stockMovement->type === 'in';
                    $isHighlighted = $highlightDocId && $line->stock_movement_id === $highlightDocId;
                    $shouldScroll = $isHighlighted && ! $scrolledToHighlightCard;
                    if ($shouldScroll) { $scrolledToHighlightCard = true; }
                @endphp
                <div @if($shouldScroll) x-data x-init="setTimeout(() => $el.scrollIntoView({ behavior: 'instant', block: 'center' }), 300)" @endif
                    @class(['bg-surface border rounded-[14px] p-3.5 shadow-sm flex flex-col gap-2.5 transition-colors', 'border-accent bg-accent-tint' => $isHighlighted, 'border-border' => ! $isHighlighted])>
                    <div class="flex items-start gap-2.5">
                        <div class="flex-1 min-w-0 flex flex-col leading-snug">
                            <span class="text-[13.5px] font-medium truncate">{{ $line->product_name }}</span>
                            <span class="text-[11.5px] text-muted2 tabular-nums">{{ $line->stockMovement->doc_no }} · {{ $line->stockMovement->date->format('d/m/Y') }} {{ $line->stockMovement->created_at->format('H:i') }} · {{ $line->category_name }}</span>
                        </div>
                        @can('stock_movements')
                            <div class="flex items-center gap-1 shrink-0">
                                <button wire:click="openEditLine({{ $line->id }})" class="w-7 h-7 rounded-lg flex items-center justify-center text-muted3 hover:bg-accent-tint hover:text-accent">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                                </button>
                                <button wire:click="askDeleteLine({{ $line->id }})" class="w-7 h-7 rounded-lg flex items-center justify-center text-muted3 hover:bg-danger-tint hover:text-danger">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                                </button>
                            </div>
                        @endcan
                    </div>
                    <div class="grid grid-cols-3 gap-2 border-t border-hairline pt-2.5">
                        <div class="flex flex-col gap-0.5"><span class="text-[11px] text-muted2">จำนวน</span><span @class(['text-[13px] font-semibold tabular-nums', 'text-accent' => $isIn, 'text-text4' => ! $isIn])>{{ $isIn ? '+' : '-' }}{{ $line->qty }}</span></div>
                        <div class="flex flex-col gap-0.5"><span class="text-[11px] text-muted2">ราคาหน่วย</span><span class="text-[13px] tabular-nums">{{ number_format($line->unit_price, 2) }}</span></div>
                        <div class="flex flex-col gap-0.5"><span class="text-[11px] text-muted2">รวม</span><span class="text-[13px] font-semibold tabular-nums">{{ number_format($line->line_total, 2) }}</span></div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11.5px] text-muted truncate">{{ $line->stockMovement->party }}</span>
                        <button wire:click="openDocument({{ $line->stock_movement_id }})" class="text-xs text-accent font-medium border border-accent-border px-2.5 py-1 rounded-lg hover:bg-accent-tint whitespace-nowrap">ดูเอกสาร</button>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-surface border border-border rounded-[15px] p-10 text-center text-sm text-muted2">ไม่พบรายการที่ตรงกับตัวกรอง</div>
            @endforelse
        </div>
        @if ($hasMoreRows)
            <div wire:key="load-more-card-{{ $cardPerPage }}" x-data x-init="
                const io = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting) { $wire.loadMore(); io.disconnect(); }
                }, { rootMargin: '300px' });
                io.observe($el);
            " class="py-3.5 text-center text-[12px] text-muted2">กำลังโหลดเพิ่มเติม...</div>
        @endif
    @endif

    {{-- Create form modal --}}
    @if ($showForm)
        <div wire:click="closeForm" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[560px] max-h-[93vh] overflow-y-auto bg-surface rounded-2xl shadow-2xl flex flex-col">
                <div class="sticky top-0 z-10 bg-surface rounded-t-2xl flex items-start justify-between gap-3 px-5 pt-5 pb-3 border-b border-hairline2">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[17px] font-semibold tracking-tight">บันทึกรับเข้า / เบิกออก</span>
                        <span class="text-[12.5px] text-muted2">เพิ่มได้หลายรายการในเอกสารเดียว</span>
                    </div>
                    <button wire:click="closeForm" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>

                <div class="flex flex-col gap-4 px-5 pb-5">

                <div class="flex gap-1.5 bg-chip p-[3px] rounded-[9px]">
                    <button wire:click="setFormType('in')" class="flex-1 text-center py-2 rounded-[7px] text-[13px] font-medium {{ $form['type'] === 'in' ? 'bg-surface shadow-sm' : 'text-muted2' }}">รับเข้า</button>
                    <button wire:click="setFormType('out')" class="flex-1 text-center py-2 rounded-[7px] text-[13px] font-medium {{ $form['type'] === 'out' ? 'bg-surface shadow-sm' : 'text-muted2' }}">เบิกออก</button>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">วันที่</label>
                        <input type="date" lang="en-GB" wire:model="form.date" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">{{ $form['type'] === 'in' ? 'ผู้จำหน่าย' : 'ผู้รับสินค้า' }}</label>
                        <input type="text" wire:model="form.party" placeholder="ชื่อคู่ค้า / ลูกค้า" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                </div>

                <div class="border border-line rounded-[13px] p-3.5 bg-surface2 flex flex-col gap-2.5">
                    <span class="text-xs font-semibold tracking-wide text-muted2">รายการสินค้า</span>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <label class="text-[11.5px] text-muted">สินค้า</label>
                                @if ($lineProduct)
                                    <span @class(['text-[11px] font-medium tabular-nums', 'text-danger' => (float) $lineProduct->stock <= 0, 'text-muted2' => (float) $lineProduct->stock > 0])>
                                        คงเหลือ {{ $lineProduct->stock_display }} {{ $lineProduct->unit?->name }}
                                    </span>
                                @endif
                            </div>
                            <x-combobox field="lineProductId" :options="$productOptions" placeholder="เลือกสินค้า" :live="true" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11.5px] text-muted">ประเภท</label>
                            <x-combobox field="lineCategoryId" :options="$lineCategoryOptions" placeholder="เลือกประเภท" />
                        </div>
                    </div>

                    @if (! empty($lineVariantOptions))
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11.5px] text-muted">ขนาด</label>
                            <x-combobox field="lineVariantId" :options="$lineVariantOptions" placeholder="เลือกขนาด" :live="true" />
                        </div>
                    @endif

                    <div class="grid grid-cols-3 gap-2">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11.5px] text-muted">จำนวน</label>
                            <input type="number" min="1" wire:model.live="lineQty" placeholder="0" class="border border-border3 rounded-lg px-2.5 py-2 text-[13px] bg-surface text-right tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11.5px] text-muted">หน่วย</label>
                            <x-combobox field="lineUnitId" :options="$lineUnitOptions" placeholder="เลือกหน่วย" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11.5px] text-muted">ราคาต่อหน่วย</label>
                            <input type="number" step="0.01" wire:model.live="lineUnitPrice" class="border border-border3 rounded-lg px-2.5 py-2 text-[13px] bg-surface text-right tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                        </div>
                    </div>

                    @if ($lineCostMissing)
                        <span class="flex items-center gap-1.5 text-[11.5px] text-warn bg-warn-tint rounded-lg px-3 py-2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
                            สินค้านี้ยังไม่มีต้นทุนในระบบ (0.00 บาท) กรุณากรอกราคาต้นทุนจริงในช่อง "ราคาต่อหน่วย"
                        </span>
                    @endif

                    <div class="flex items-center justify-between gap-3 bg-surface border border-line rounded-lg px-3 py-2">
                        <span class="text-xs text-muted2">จำนวน × ราคา</span>
                        <span class="text-[14.5px] font-semibold tabular-nums">{{ number_format($this->lineTotal(), 2) }}</span>
                    </div>

                    <div class="flex gap-2">
                        @if ($editingLineIndex !== null)
                            <button type="button" wire:click="addLine" class="flex-1 flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-[10px] bg-accent text-white text-[12.5px] font-medium hover:bg-accent-hover">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4z"></path></svg>บันทึกการแก้ไขรายการ
                            </button>
                            <button type="button" wire:click="cancelEditLine" class="px-3.5 py-2 rounded-[10px] border border-border4 text-text2 text-[12.5px] font-medium hover:bg-hairline">ยกเลิก</button>
                        @else
                            <button type="button" wire:click="addLine" class="self-start flex items-center gap-1.5 px-3.5 py-2 rounded-[10px] border border-dashed border-accent-border3 text-accent text-[12.5px] font-medium hover:bg-accent-tint">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>เพิ่มรายการนี้ในเอกสาร
                            </button>
                        @endif
                    </div>
                </div>

                @if (! empty($formLines))
                    <div class="border border-border rounded-[13px] overflow-hidden">
                        @foreach ($formLines as $i => $l)
                            <div @class(['flex flex-col gap-1.5 px-3.5 py-2.5 border-b border-hairline2 last:border-0 text-[12.5px]', 'bg-accent-tint' => $editingLineIndex === $i])>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex-1 min-w-0 flex flex-col leading-snug">
                                        <span class="truncate">{{ $l['name'] }}</span>
                                        <span class="text-[11px] text-muted2 tabular-nums">{{ $l['qty'] }} {{ $l['unit'] }} × {{ number_format($l['unit_price'], 2) }}</span>
                                    </div>
                                    <span class="font-semibold tabular-nums">{{ number_format($l['line_total'], 2) }}</span>
                                    <button type="button" wire:click="editLine({{ $i }})" title="แก้ไขรายการนี้" class="w-6 h-6 rounded-lg flex items-center justify-center text-muted2 hover:bg-accent-tint hover:text-accent">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                                    </button>
                                    <button type="button" wire:click="removeLine({{ $i }})" class="w-6 h-6 rounded-lg flex items-center justify-center text-muted2 hover:bg-danger-tint hover:text-danger">✕</button>
                                </div>
                                @if (($l['implied_cost'] ?? null) !== null)
                                    @if ($l['implied_cost'] <= 0 && $l['current_cost'] <= 0)
                                        <span class="flex items-center gap-1.5 pl-0.5 text-[11px] text-warn">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
                                            ยังไม่มีต้นทุนสินค้านี้ — ราคาต่อหน่วยที่กรอกไว้เป็น 0 บาท
                                        </span>
                                    @else
                                        <label class="flex items-center gap-1.5 pl-0.5 text-[11px] text-muted2 cursor-pointer">
                                            <input type="checkbox" wire:model="formLines.{{ $i }}.update_cost" class="rounded border-border3 text-accent focus:ring-0">
                                            อัปเดตต้นทุนสินค้า {{ number_format($l['current_cost'], 2) }} → {{ number_format($l['implied_cost'], 2) }} บาท
                                        </label>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                        <div class="flex justify-between gap-3 px-3.5 py-2.5 bg-surface2 text-[13.5px]">
                            <span class="font-medium">มูลค่ารวม</span>
                            <span class="font-semibold tabular-nums">{{ number_format($this->formTotal(), 2) }}</span>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">บันทึก / หมายเหตุ</label>
                    <input type="text" wire:model="form.note" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                </div>

                @if ($formError)
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3 py-2.5">{{ $formError }}</span>
                @endif

                <div class="flex gap-2.5">
                    <button wire:click="askSaveConfirm" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">บันทึก</button>
                    <button wire:click="closeForm" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Save confirm --}}
    @if ($showSaveConfirm)
        <div wire:click="cancelSaveConfirm" class="fixed inset-0 bg-black/45 z-[92] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-accent-tint text-accent flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7l8-4z"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ยืนยันบันทึกเอกสาร{{ $form['type'] === 'in' ? 'รับเข้า' : 'เบิกออก' }}?</span>
                        <span class="text-[12.5px] text-muted">{{ count($formLines) }} รายการ · มูลค่ารวม {{ number_format($this->formTotal()) }} บาท</span>
                        <span class="text-[12.5px] text-warn">ระบบจะปรับจำนวนสต็อกสินค้าทันทีหลังบันทึก</span>
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="cancelSaveConfirm" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ยกเลิก</button>
                    <button wire:click="save" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13px] font-medium hover:bg-accent-hover">ยืนยันบันทึก</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit line modal --}}
    @if ($editingLine)
        <div wire:click="closeEditLine" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[420px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[17px] font-semibold tracking-tight">แก้ไขรายการ</span>
                        <span class="text-[12.5px] text-muted2">{{ $editingLine->stockMovement->doc_no }} · {{ $editingLine->product_name }}</span>
                    </div>
                    <button wire:click="closeEditLine" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">จำนวน ({{ $editingLine->unit }})</label>
                        <input type="number" min="1" wire:model="editForm.qty" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] text-right tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">ราคาต่อหน่วย</label>
                        <input type="number" step="0.01" wire:model="editForm.unit_price" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] text-right tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">วันที่</label>
                        <input type="date" lang="en-GB" wire:model="editForm.date" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">{{ $editingLine->stockMovement->type === 'in' ? 'ผู้จำหน่าย' : 'ผู้รับสินค้า' }}</label>
                        <input type="text" wire:model="editForm.party" placeholder="ชื่อคู่ค้า / ลูกค้า" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">บันทึก / หมายเหตุ</label>
                    <input type="text" wire:model="editForm.note" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                </div>

                <span class="text-[11.5px] text-muted2 leading-relaxed">แก้ไขได้เฉพาะจำนวน ราคา วันที่ และข้อมูลคู่ค้าของรายการนี้ — ถ้าต้องการเปลี่ยนสินค้า ให้ลบรายการนี้แล้วเพิ่มใหม่แทน</span>

                @if ($editFormError)
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3 py-2.5">{{ $editFormError }}</span>
                @endif

                <div class="flex gap-2.5">
                    <button wire:click="saveEditLine" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">บันทึกการแก้ไข</button>
                    <button wire:click="closeEditLine" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Document / receipt view --}}
    @if ($docMovement)
        <div wire:click="closeDocument" id="print-overlay" class="fixed inset-0 bg-black/40 z-[90] flex items-center justify-center p-3.5 overflow-y-auto">
            <div wire:click.stop id="doc-sheet" class="w-full max-w-[492px] bg-white rounded-[15px] shadow-2xl p-7 flex flex-col gap-5">
                <div class="flex items-start justify-between gap-3.5 border-b border-line pb-4">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-base font-semibold tracking-tight">ส.กิจการค้า</span>
                        <span class="text-[11.5px] text-muted2 leading-relaxed">ตำบลศรีสุทโธ อำเภอบ้านดุง จังหวัดอุดรธานี 41190 ประเทศไทย<br>โทร 095-669-9178 · ใบอนุญาตขายอาหารสัตว์ 62410000300252</span>
                    </div>
                    @php $isIn = $docMovement->type === 'in'; @endphp
                    <span @class(['text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap', 'bg-accent-tint text-accent-ink' => $isIn, 'bg-chip text-text4' => ! $isIn])>{{ $isIn ? 'ใบรับสินค้าเข้า' : 'ใบเบิกสินค้าออก' }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-[12.5px]">
                    <div class="flex flex-col gap-0.5"><span class="text-muted2">เลขที่เอกสาร</span><span class="font-semibold tabular-nums">{{ $docMovement->doc_no }}</span></div>
                    <div class="flex flex-col gap-0.5"><span class="text-muted2">วันที่</span><span class="font-medium tabular-nums">{{ $docMovement->date->format('d/m/Y') }}</span></div>
                    <div class="flex flex-col gap-0.5"><span class="text-muted2">{{ $isIn ? 'ผู้จำหน่าย' : 'ผู้รับสินค้า' }}</span><span class="font-medium">{{ $docMovement->party ?: '-' }}</span></div>
                    <div class="flex flex-col gap-0.5"><span class="text-muted2">ผู้ทำรายการ</span><span class="font-medium">{{ $docMovement->user?->name }}</span></div>
                </div>

                <div class="flex flex-col">
                    <div class="grid grid-cols-[2.2fr_.7fr_1fr_1fr] gap-2 py-2 border-b border-text text-[11px] font-semibold tracking-wide text-text2">
                        <span>รายการ</span><span class="text-right">จำนวน</span><span class="text-right">ราคา/หน่วย</span><span class="text-right">รวม</span>
                    </div>
                    @foreach ($docMovement->lines as $l)
                        <div class="grid grid-cols-[2.2fr_.7fr_1fr_1fr] gap-2 py-2.5 border-b border-hairline text-[12.5px] items-baseline">
                            <div class="flex flex-col leading-snug">
                                <span>{{ $l->product_name }}</span>
                                <span class="text-[11px] text-muted2 tabular-nums">{{ $l->product?->sku }}{{ $l->variant ? ' · '.$l->variant->label : '' }}</span>
                            </div>
                            <span class="text-right tabular-nums">{{ $l->qty }} {{ $l->unit }}</span>
                            <span class="text-right tabular-nums">{{ number_format($l->unit_price, 2) }}</span>
                            <span class="text-right tabular-nums font-medium">{{ number_format($l->line_total, 2) }}</span>
                        </div>
                    @endforeach
                </div>

                @php $vat = $docMovement->total * 0.07; @endphp
                <div class="flex flex-col gap-1.5 items-end">
                    <div class="flex gap-6 text-[12.5px]"><span class="text-muted">รวมเป็นเงิน</span><span class="tabular-nums min-w-[94px] text-right">{{ number_format($docMovement->total, 2) }}</span></div>
                    <div class="flex gap-6 text-[12.5px]"><span class="text-muted">ภาษีมูลค่าเพิ่ม 7%</span><span class="tabular-nums min-w-[94px] text-right">{{ number_format($vat, 2) }}</span></div>
                    <div class="flex gap-6 text-[15px] font-semibold border-t border-text pt-2"><span>ยอดสุทธิ</span><span class="tabular-nums min-w-[94px] text-right">{{ number_format($docMovement->total + $vat, 2) }}</span></div>
                </div>

                <div class="flex justify-between gap-7 mt-2.5 pt-6 text-[11.5px] text-muted2">
                    <span class="flex-1 border-t border-dotted border-faint pt-1.5 text-center">ผู้ส่งมอบ</span>
                    <span class="flex-1 border-t border-dotted border-faint pt-1.5 text-center">ผู้รับสินค้า</span>
                </div>

                <div class="flex gap-2.5 border-t border-line pt-4" data-no-print>
                    <button wire:click="closeDocument" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ปิด</button>
                    @can('stock_movements')
                        <button wire:click="duplicateDocument({{ $docMovement->id }})" title="ทำซ้ำเอกสารนี้เป็นฉบับร่างใหม่" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:border-accent hover:text-accent">ทำซ้ำ</button>
                        <button wire:click="askDeleteMovement({{ $docMovement->id }})" class="px-4.5 py-2.5 rounded-[10px] border border-danger-border text-danger text-[13px] font-medium hover:bg-danger-tint">ลบเอกสาร</button>
                    @endcan
                    <button onclick="printDocSheet()" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-[10px] bg-accent text-white text-[13px] font-medium hover:bg-accent-hover">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4v-6h16v6h-2M8 14h8v7H8z"></path></svg>พิมพ์เอกสาร
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirm --}}
    @if ($deleteLine)
        <div wire:click="cancelDeleteLine" class="fixed inset-0 bg-black/45 z-[95] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ลบ &ldquo;{{ $deleteLine->product_name }}&rdquo; จาก {{ $deleteLine->stockMovement->doc_no }}?</span>
                        <span class="text-[12.5px] text-muted">ยอดคงเหลือของสินค้าจะถูกปรับกลับให้ตรงกับก่อนบันทึกรายการนี้</span>
                        <span class="text-[12.5px] text-warn">ถ้าเป็นรายการสุดท้ายในเอกสาร เอกสารทั้งใบจะถูกลบไปด้วย</span>
                    </div>
                </div>
                @if ($deleteError)
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3 py-2.5">{{ $deleteError }}</span>
                @endif
                <div class="flex gap-2.5">
                    <button wire:click="cancelDeleteLine" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ยกเลิก</button>
                    <button wire:click="deleteLine" class="flex-1 py-2.5 rounded-[10px] bg-danger text-white text-[13px] font-medium hover:bg-danger-ink2">ลบรายการ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete whole document confirm --}}
    @if ($deleteMovement)
        <div wire:click="cancelDeleteMovement" class="fixed inset-0 bg-black/45 z-[95] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ลบเอกสาร &ldquo;{{ $deleteMovement->doc_no }}&rdquo;?</span>
                        <span class="text-[12.5px] text-muted">ทุกรายการในเอกสารนี้จะถูกลบ และยอดคงเหลือของสินค้าจะถูกปรับกลับทั้งหมด</span>
                    </div>
                </div>
                @if ($deleteError)
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3 py-2.5">{{ $deleteError }}</span>
                @endif
                <div class="flex gap-2.5">
                    <button wire:click="cancelDeleteMovement" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ยกเลิก</button>
                    <button wire:click="deleteMovement" class="flex-1 py-2.5 rounded-[10px] bg-danger text-white text-[13px] font-medium hover:bg-danger-ink2">ลบเอกสาร</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Import from Excel --}}
    @if ($showImportModal)
        <div wire:click="closeImportModal" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[760px] max-h-[88vh] bg-surface rounded-2xl shadow-2xl flex flex-col overflow-hidden">
                <div class="p-5 border-b border-line flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-0.5 leading-relaxed">
                        <span class="text-[16px] font-semibold">นำเข้ารายการจาก Excel</span>
                        <span class="text-[12.5px] text-muted2">อัปโหลดไฟล์ .xlsx ตามรูปแบบ Template เพื่อสร้างเอกสารรับเข้า/เบิกออกหลายรายการพร้อมกัน</span>
                    </div>
                    <button wire:click="closeImportModal" class="text-danger hover:opacity-70 text-[19px] leading-none shrink-0">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto p-5 flex flex-col gap-4">

                    <div>
                        <input type="file" wire:model="importFile" accept=".xlsx,.xls"
                            class="w-full text-[12.5px] text-text3 border border-border3 rounded-lg bg-surface pl-1 pr-3 py-1 cursor-pointer focus:outline-none focus:border-accent
                            file:mr-3 file:rounded-lg file:border-0 file:bg-chip file:text-text2 file:px-3.5 file:py-2 file:text-[12.5px] file:font-medium file:cursor-pointer hover:file:bg-chip2">
                        <div wire:loading wire:target="importFile" class="text-[12px] text-muted2 mt-1.5">กำลังอ่านไฟล์...</div>
                    </div>

                    @if ($importFileError)
                        <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3.5 py-2.5">{{ $importFileError }}</span>
                    @endif

                    @if ($importGroups !== null)
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center gap-2 bg-accent-tint text-accent-ink rounded-lg px-3.5 py-2.5 text-[12.5px] font-medium">
                                พบ {{ count($importGroups) }} เอกสาร จาก {{ collect($importGroups)->sum(fn ($g) => count($g['lines'])) }} รายการที่ถูกต้อง
                            </div>

                            @if (! empty($importErrors))
                                <div class="bg-danger-tint rounded-lg px-3.5 py-2.5 flex flex-col gap-1">
                                    <span class="text-[12.5px] font-medium text-danger">ข้าม {{ count($importErrors) }} แถว:</span>
                                    <ul class="text-[11.5px] text-danger-ink2 list-disc list-inside flex flex-col gap-0.5 max-h-[100px] overflow-y-auto">
                                        @foreach ($importErrors as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (count($importGroups) > 0)
                                <div class="border border-border rounded-[10px] overflow-auto max-h-[320px]">
                                    <table class="w-full text-[12px] whitespace-nowrap">
                                        <thead>
                                            <tr class="sticky top-0 z-10 bg-surface2 border-b border-line text-[10.5px] font-semibold tracking-wide text-muted2">
                                                <th class="text-left px-3 py-2">เลขที่</th>
                                                <th class="text-left px-3 py-2">ประเภท</th>
                                                <th class="text-left px-3 py-2">วันที่</th>
                                                <th class="text-left px-3 py-2">คู่ค้า</th>
                                                <th class="text-left px-3 py-2">สินค้า</th>
                                                <th class="text-right px-3 py-2">จำนวน</th>
                                                <th class="text-right px-3 py-2">ราคาต่อหน่วย</th>
                                                <th class="text-right px-3 py-2">รวม</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-hairline2">
                                            @foreach ($importGroups as $group)
                                                @foreach ($group['lines'] as $line)
                                                    <tr class="hover:bg-surface2">
                                                        <td class="px-3 py-2 font-medium">{{ $group['label'] }}</td>
                                                        <td class="px-3 py-2">
                                                            <span @class([
                                                                'text-[10.5px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap',
                                                                'bg-accent-tint text-accent' => $group['type'] === 'in',
                                                                'bg-warn-tint text-warn' => $group['type'] === 'out',
                                                            ])>{{ $group['type'] === 'in' ? 'รับเข้า' : 'เบิกออก' }}</span>
                                                        </td>
                                                        <td class="px-3 py-2 text-muted2 tabular-nums">{{ \Illuminate\Support\Carbon::parse($group['date'])->format('d/m/Y') }}</td>
                                                        <td class="px-3 py-2 text-muted2">{{ $group['party'] ?? '—' }}</td>
                                                        <td class="px-3 py-2 max-w-[180px] truncate">{{ $line['product_name'] }}</td>
                                                        <td class="px-3 py-2 text-right tabular-nums">{{ $line['qty'] }}</td>
                                                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format($line['unit_price'], 2) }}</td>
                                                        <td class="px-3 py-2 text-right font-medium tabular-nums">{{ number_format($line['line_total'], 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex gap-2.5 p-5 pt-0">
                    @if ($importGroups !== null && count($importGroups) > 0)
                        <button wire:click="confirmImport" wire:loading.attr="disabled" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover disabled:opacity-50">ยืนยันนำเข้า {{ count($importGroups) }} เอกสาร</button>
                    @endif
                    <button wire:click="closeImportModal" class="px-5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif
</div>
