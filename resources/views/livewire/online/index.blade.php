<div class="flex flex-col gap-3.5" x-data="{ toolsOpen: false }">

    @if ($importSuccessMessage)
        <div x-data x-init="setTimeout(() => $wire.dismissImportSuccess(), 5000)"
            class="flex items-center gap-2.5 bg-accent-tint text-accent-ink rounded-[10px] px-4 py-2.5 text-[13px] font-medium">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M20 6L9 17l-5-5"></path></svg>
            <span class="flex-1">{{ $importSuccessMessage }}</span>
            <button wire:click="dismissImportSuccess" class="shrink-0 hover:opacity-70">✕</button>
        </div>
    @endif


    {{-- KPIs --}}
    @php [$firstKpi, $restKpis] = [$kpis[0], array_slice($kpis, 1)]; @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @php
            $browseInitYear = $period === 'range' ? (int) substr($rangeStart, 0, 4) : (int) substr($selectedMonth, 0, 4);
            $browseInitMonth = $period === 'range' ? (int) substr($rangeStart, 5, 2) : (int) substr($selectedMonth, 5, 2);
        @endphp
        <div class="bg-surface border border-border rounded-[15px] p-4 shadow-sm flex flex-col gap-1.5" x-data="{
                monthOpen: false,
                browseYear: {{ $browseInitYear }},
                browseMonth: {{ $browseInitMonth }},
                pendingStart: @js($rangeStart),
                pendingEnd: @js($rangeEnd),
                monthNames: ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'],
                daysInMonthFor(y, m) { return new Date(y, m, 0).getDate(); },
                leadingBlanksFor(y, m) { return new Date(y, m - 1, 1).getDay(); },
                get rightMonth() { return this.browseMonth === 12 ? 1 : this.browseMonth + 1; },
                get rightYear() { return this.browseMonth === 12 ? this.browseYear + 1 : this.browseYear; },
                get canGoNextBrowseMonth() { return this.browseYear < {{ now()->year }} || (this.browseYear === {{ now()->year }} && this.browseMonth < {{ now()->month }}); },
                prevBrowseMonth() { this.browseMonth--; if (this.browseMonth < 1) { this.browseMonth = 12; this.browseYear--; } },
                nextBrowseMonth() { if (this.canGoNextBrowseMonth) { this.browseMonth++; if (this.browseMonth > 12) { this.browseMonth = 1; this.browseYear++; } } },
                selectRangeDay(dateStr) {
                    if (! this.pendingStart || (this.pendingStart && this.pendingEnd)) {
                        this.pendingStart = dateStr;
                        this.pendingEnd = null;
                    } else if (dateStr < this.pendingStart) {
                        this.pendingEnd = this.pendingStart;
                        this.pendingStart = dateStr;
                        this.$wire.selectRange(this.pendingStart, this.pendingEnd);
                    } else {
                        this.pendingEnd = dateStr;
                        this.$wire.selectRange(this.pendingStart, this.pendingEnd);
                    }
                },
                openPicker() {
                    // Read straight from $wire (not Blade-embedded literals) — this method's
                    // body is part of x-data, which Alpine/Livewire's morph deliberately does
                    // NOT re-initialize on every re-render (that's what lets Alpine state
                    // survive Livewire updates), so any value baked in here at first paint
                    // would otherwise go stale after the very first selection.
                    this.monthOpen = !this.monthOpen;
                    if (this.$wire.period === 'range') {
                        this.pendingStart = this.$wire.rangeStart;
                        this.pendingEnd = this.$wire.rangeEnd;
                        const [y, m] = this.$wire.rangeStart.split('-');
                        this.browseYear = parseInt(y);
                        this.browseMonth = parseInt(m);
                    } else {
                        this.pendingStart = null;
                        this.pendingEnd = null;
                        const [y, m] = this.$wire.selectedMonth.split('-');
                        this.browseYear = parseInt(y);
                        this.browseMonth = parseInt(m);
                    }
                },
            }">
            <span class="text-[12.5px] text-muted font-medium">{{ $firstKpi['label'] }}</span>
            <span class="text-[19px] font-semibold tracking-tight tabular-nums">{{ $firstKpi['value'] }}</span>
            <div class="flex items-center justify-between gap-2">
                @if ($firstKpi['delta'] ?? null)
                    <span @class(['text-[11px] font-medium whitespace-nowrap', 'text-accent' => $firstKpi['delta']['tone'] === 'accent', 'text-danger' => $firstKpi['delta']['tone'] === 'danger'])>{{ $firstKpi['delta']['text'] }}</span>
                @else
                    <span></span>
                @endif
                <div class="relative">
                <button @click="openPicker()" title="เลือกวันที่" class="w-7 h-7 rounded-lg flex items-center justify-center text-muted2 hover:bg-sunken hover:text-accent">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
                </button>
                <div x-show="monthOpen" @click.outside="monthOpen = false" x-cloak
                    class="absolute top-full left-0 mt-2 z-30 bg-surface border border-border2 rounded-xl shadow-lg p-3 flex flex-col gap-2 w-max">
                    <div class="flex gap-1 bg-chip p-[3px] rounded-[9px]">
                        <button wire:click="setPeriod('range')" class="flex-1 px-3 py-1.5 rounded-[7px] text-[12px] font-medium {{ $period === 'range' ? 'bg-surface shadow-sm' : 'text-muted2' }}">ช่วงวันที่</button>
                        <button wire:click="setPeriod('month')" class="flex-1 px-3 py-1.5 rounded-[7px] text-[12px] font-medium {{ $period === 'month' ? 'bg-surface shadow-sm' : 'text-muted2' }}">รายเดือน</button>
                        <button wire:click="setPeriod('year')" class="flex-1 px-3 py-1.5 rounded-[7px] text-[12px] font-medium {{ $period === 'year' ? 'bg-surface shadow-sm' : 'text-muted2' }}">รายปี</button>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($period === 'range')
                            @php $dayBtnClasses = 'w-7 h-7 text-[12px] font-medium transition-colors disabled:opacity-25 disabled:pointer-events-none'; @endphp
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between px-1">
                                    <button @click="prevBrowseMonth()" title="เดือนก่อนหน้า" class="w-7 h-7 rounded-lg flex items-center justify-center text-text3 hover:bg-sunken hover:text-accent">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"></path></svg>
                                    </button>
                                    <span class="text-[11.5px] text-muted3">ลากเลือกวันเริ่ม-สิ้นสุด</span>
                                    <button @click="nextBrowseMonth()" :disabled="! canGoNextBrowseMonth" title="เดือนถัดไป"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-text3 hover:bg-sunken hover:text-accent disabled:opacity-30 disabled:pointer-events-none">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"></path></svg>
                                    </button>
                                </div>
                                <div class="flex items-start gap-3">
                                    {{-- Left calendar (browseYear/browseMonth) --}}
                                    <div class="flex flex-col gap-2 w-[218px]">
                                        <span class="text-[13px] font-semibold tabular-nums text-center" x-text="monthNames[browseMonth - 1] + ' ' + (browseYear + 543)"></span>
                                        <div class="grid grid-cols-7 gap-1 text-center">
                                            @foreach (['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'] as $wd)
                                                <span class="text-[10.5px] text-muted3 font-medium py-1">{{ $wd }}</span>
                                            @endforeach
                                            <template x-for="n in leadingBlanksFor(browseYear, browseMonth)" :key="'lb-' + n"><span></span></template>
                                            <template x-for="d in daysInMonthFor(browseYear, browseMonth)" :key="'l-' + d">
                                                <button
                                                    @click="selectRangeDay(browseYear + '-' + String(browseMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0'))"
                                                    :disabled="(browseYear + '-' + String(browseMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) > '{{ now()->toDateString() }}'"
                                                    :class="{
                                                        'bg-accent text-white rounded-full': ['pendingStart','pendingEnd'].some(k => (browseYear + '-' + String(browseMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) === (k === 'pendingStart' ? pendingStart : pendingEnd)),
                                                        'bg-accent-tint text-accent-ink': pendingStart && pendingEnd && (browseYear + '-' + String(browseMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) > pendingStart && (browseYear + '-' + String(browseMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) < pendingEnd,
                                                        'text-text3 hover:bg-sunken': ! (pendingStart === (browseYear + '-' + String(browseMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) || pendingEnd === (browseYear + '-' + String(browseMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) || (pendingStart && pendingEnd && (browseYear + '-' + String(browseMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) > pendingStart && (browseYear + '-' + String(browseMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) < pendingEnd)),
                                                    }"
                                                    class="{{ $dayBtnClasses }}"
                                                    x-text="d"
                                                ></button>
                                            </template>
                                        </div>
                                    </div>
                                    {{-- Right calendar (rightYear/rightMonth) --}}
                                    <div class="flex flex-col gap-2 w-[218px]">
                                        <span class="text-[13px] font-semibold tabular-nums text-center" x-text="monthNames[rightMonth - 1] + ' ' + (rightYear + 543)"></span>
                                        <div class="grid grid-cols-7 gap-1 text-center">
                                            @foreach (['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'] as $wd)
                                                <span class="text-[10.5px] text-muted3 font-medium py-1">{{ $wd }}</span>
                                            @endforeach
                                            <template x-for="n in leadingBlanksFor(rightYear, rightMonth)" :key="'rb-' + n"><span></span></template>
                                            <template x-for="d in daysInMonthFor(rightYear, rightMonth)" :key="'r-' + d">
                                                <button
                                                    @click="selectRangeDay(rightYear + '-' + String(rightMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0'))"
                                                    :disabled="(rightYear + '-' + String(rightMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) > '{{ now()->toDateString() }}'"
                                                    :class="{
                                                        'bg-accent text-white rounded-full': ['pendingStart','pendingEnd'].some(k => (rightYear + '-' + String(rightMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) === (k === 'pendingStart' ? pendingStart : pendingEnd)),
                                                        'bg-accent-tint text-accent-ink': pendingStart && pendingEnd && (rightYear + '-' + String(rightMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) > pendingStart && (rightYear + '-' + String(rightMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) < pendingEnd,
                                                        'text-text3 hover:bg-sunken': ! (pendingStart === (rightYear + '-' + String(rightMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) || pendingEnd === (rightYear + '-' + String(rightMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) || (pendingStart && pendingEnd && (rightYear + '-' + String(rightMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) > pendingStart && (rightYear + '-' + String(rightMonth).padStart(2, '0') + '-' + String(d).padStart(2, '0')) < pendingEnd)),
                                                    }"
                                                    class="{{ $dayBtnClasses }}"
                                                    x-text="d"
                                                ></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($period === 'month')
                            <div class="flex flex-col gap-2 w-[218px]">
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
                        @else
                            <button wire:click="previousYear" title="ปีก่อนหน้า" class="w-8 h-8 shrink-0 rounded-lg border border-border4 flex items-center justify-center text-text3 hover:border-accent hover:text-accent">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"></path></svg>
                            </button>
                            <span class="flex-1 text-center text-[13px] font-medium tabular-nums">{{ $selectedYear + 543 }}</span>
                            <button wire:click="nextYear" title="ปีถัดไป" @disabled($isCurrentMonth)
                                class="w-8 h-8 shrink-0 rounded-lg border border-border4 flex items-center justify-center text-text3 hover:border-accent hover:text-accent disabled:opacity-30 disabled:pointer-events-none">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"></path></svg>
                            </button>
                        @endif
                    </div>
                </div>
                </div>
            </div>
        </div>
        @foreach ($restKpis as $k)
            <div class="bg-surface border border-border rounded-[15px] p-4 shadow-sm flex flex-col gap-1.5">
                <span class="text-[12.5px] text-muted font-medium">{{ $k['label'] }}</span>
                <span class="text-[19px] font-semibold tracking-tight tabular-nums">{{ $k['value'] }}</span>
                @if ($k['delta'] ?? null)
                    <span @class(['text-[11px] font-medium whitespace-nowrap', 'text-accent' => $k['delta']['tone'] === 'accent', 'text-danger' => $k['delta']['tone'] === 'danger'])>{{ $k['delta']['text'] }}</span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap gap-2.5 items-center">
        <div class="flex-1 min-w-[190px] flex items-center gap-2 bg-surface border border-border2 rounded-[10px] px-3 py-2 shadow-sm focus-within:border-accent">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-muted3" stroke-width="1.9" stroke-linecap="round"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3"></path></svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="ค้นหาเลขที่คำสั่งซื้อ หรือรายการสินค้า" class="flex-1 min-w-0 text-[13px] border-0 p-0 focus:ring-0 focus:outline-none bg-transparent">
        </div>
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
        @can('online_sales')
            <button wire:click="openCreate" class="flex items-center gap-1.5 px-4 py-2.5 rounded-[10px] bg-accent text-white text-[12.5px] font-medium hover:bg-accent-hover">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>เพิ่มออเดอร์
            </button>
        @endcan
    </div>

    {{-- Orders table --}}
    <div class="table-scroll-container bg-surface border border-border rounded-[15px] shadow-sm overflow-auto max-h-[65vh]">
        @php $filterScopeKey = $period.'-'.$rangeStart.'-'.$rangeEnd.'-'.$selectedMonth.'-'.$selectedYear; @endphp
        <table class="w-full text-[12.5px]">
            <thead>
                <tr class="sticky top-0 z-20 bg-surface2 border-b border-line text-[11px] font-semibold tracking-wide text-muted2">
                    <th class="text-left px-4 py-2.5 relative">
                        <span>วัน/เดือน/ปี</span>
                        <x-excel-filter field="date" :options="$columnOptionsMap['date']" :selected="$columnFilters['date'] ?? null" align="left" wire:key="filter-date-{{ $filterScopeKey }}" />
                    </th>
                    <th class="text-left px-4 py-2.5 relative">
                        <span>เลขที่คำสั่งซื้อ</span>
                        <x-excel-filter field="orderNo" :options="$columnOptionsMap['orderNo']" :selected="$columnFilters['orderNo'] ?? null" align="left" wire:key="filter-orderNo-{{ $filterScopeKey }}" />
                    </th>
                    <th class="text-left px-4 py-2.5 relative">
                        <span>รายการ</span>
                        <x-excel-filter field="item" :options="$columnOptionsMap['item']" :selected="$columnFilters['item'] ?? null" align="left" wire:key="filter-item-{{ $filterScopeKey }}" />
                    </th>
                    <th class="text-left px-4 py-2.5 relative">
                        <span>ช่องทาง</span>
                        <x-excel-filter field="channel" :options="$columnOptionsMap['channel']" :selected="$columnFilters['channel'] ?? null" align="left" wire:key="filter-channel-{{ $filterScopeKey }}" />
                    </th>
                    <th class="text-right px-4 py-2.5 relative">
                        <span>รายรับ (บาท)</span>
                        <x-excel-filter field="revenue" :options="$columnOptionsMap['revenue']" :selected="$columnFilters['revenue'] ?? null" align="right" wire:key="filter-revenue-{{ $filterScopeKey }}" />
                    </th>
                    <th class="text-left px-4 py-2.5 relative">
                        <span>สถานะ</span>
                        <x-excel-filter field="status" :options="$columnOptionsMap['status']" :selected="$columnFilters['status'] ?? null" align="left" />
                    </th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $o)
                    @php $isHighlighted = $highlightOrderId && $o->id === $highlightOrderId; @endphp
                    <tr wire:click="openDetail({{ $o->id }})"
                        @if ($isHighlighted) x-data x-init="setTimeout(() => $el.scrollIntoView({ behavior: 'instant', block: 'center' }), 300)" @endif
                        @class(['border-b border-hairline2 hover:bg-surface2 transition-colors cursor-pointer', 'bg-accent-tint' => $isHighlighted])>
                        <td class="px-4 py-2.5 text-text4 tabular-nums whitespace-nowrap">{{ $o->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5 font-medium tabular-nums whitespace-nowrap">{{ $o->order_no }}</td>
                        <td class="px-4 py-2.5 min-w-0">
                            <span class="block truncate max-w-[380px]" title="{{ $o->item }}">{{ $o->item }}</span>
                            <span class="text-[10.5px] {{ $o->product ? 'text-accent' : 'text-muted3' }}">{{ $o->product ? 'จับคู่: '.$o->product->sku : 'ยังไม่จับคู่ SKU' }}</span>
                        </td>
                        <td class="px-4 py-2.5"><span class="text-[11px] px-2.5 py-0.5 rounded-full bg-chip text-text4 whitespace-nowrap">{{ $o->channel }}</span></td>
                        <td class="px-4 py-2.5 text-right font-semibold tabular-nums whitespace-nowrap">{{ number_format($o->revenue, 2) }}</td>
                        <td class="px-4 py-2.5">
                            @php $tone = ['success' => 'accent', 'failed' => 'danger', 'returned' => 'danger'][$o->status]; @endphp
                            <span @class(['text-[11px] px-2.5 py-0.5 rounded-full whitespace-nowrap', 'bg-accent-tint text-accent' => $tone === 'accent', 'bg-danger-tint text-danger' => $tone === 'danger'])>
                                {{ ['success' => 'สำเร็จ', 'failed' => 'จัดส่งไม่สำเร็จ', 'returned' => 'คืนสินค้า'][$o->status] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">
                            @can('online_sales')
                                <div class="flex items-center gap-1">
                                    <button wire:click.stop="openEdit({{ $o->id }})" title="แก้ไขออเดอร์นี้" class="w-6 h-6 rounded-lg flex items-center justify-center text-muted3 hover:bg-sunken hover:text-accent">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                                    </button>
                                    <button wire:click.stop="askDelete({{ $o->id }})" title="ลบออเดอร์นี้" class="w-6 h-6 rounded-lg flex items-center justify-center text-muted3 hover:bg-danger-tint hover:text-danger">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                                    </button>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-9 text-muted2">ไม่พบออเดอร์ที่ตรงกับคำค้น</td></tr>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Failed / returned --}}
        <div class="bg-surface border border-border rounded-[15px] shadow-sm">
            <div class="px-4.5 py-3.5 border-b border-line flex items-center justify-between gap-2.5">
                <span class="text-sm font-semibold">จัดส่งไม่สำเร็จ / คืนสินค้า</span>
                <span class="text-[11.5px] text-muted2 tabular-nums">{{ $failedOrders->count() }} รายการ</span>
            </div>
            @forelse ($failedOrders as $fl)
                <div class="px-4.5 py-3 border-b border-hairline2 last:border-0 flex flex-wrap items-center gap-2.5">
                    <span class="w-7 h-7 shrink-0 rounded-[9px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14L4 9l5-5M4 9h11a5 5 0 0 1 0 10h-4"></path></svg>
                    </span>
                    <div class="flex-1 min-w-[120px] flex flex-col leading-snug">
                        <span class="text-[12.5px] font-medium truncate">{{ $fl->item }}</span>
                        <span class="text-[11px] text-muted2 tabular-nums">{{ $fl->order_no }}</span>
                    </div>
                    <span class="text-[11.5px] px-2.5 py-0.5 rounded-full bg-danger-tint text-danger whitespace-nowrap">{{ $fl->status === 'returned' ? 'คืนสินค้า' : 'จัดส่งไม่สำเร็จ' }}</span>
                </div>
            @empty
                <div class="px-4.5 py-6 text-center text-sm text-muted2">ไม่มีออเดอร์ที่มีปัญหา</div>
            @endforelse
        </div>

        {{-- Expenses --}}
        <div class="bg-surface border border-border rounded-[15px] shadow-sm">
            <div class="px-4.5 py-3.5 border-b border-line flex items-center justify-between gap-2.5">
                <span class="text-sm font-semibold">รายจ่ายช่องทางออนไลน์</span>
                @can('online_sales')
                    <button wire:click="openExpenseForm" class="text-[11.5px] font-medium text-accent hover:underline">+ เพิ่มรายจ่าย</button>
                @endcan
            </div>
            @forelse ($expenses as $ex)
                <div class="px-4.5 py-3 border-b border-hairline2 flex items-center justify-between gap-2.5 text-[12.5px] group">
                    <div class="flex flex-col leading-snug min-w-0">
                        <span class="truncate">{{ $ex->label }}</span>
                        <span class="text-[11px] text-muted2 tabular-nums">{{ $ex->date->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="font-semibold tabular-nums text-danger whitespace-nowrap">−{{ number_format($ex->amount, 2) }}</span>
                        @can('online_sales')
                            <button wire:click="askDeleteExpense({{ $ex->id }})" title="ลบรายจ่ายนี้" class="w-6 h-6 rounded-lg flex items-center justify-center text-muted3 opacity-0 group-hover:opacity-100 hover:bg-danger-tint hover:text-danger">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                            </button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="px-4.5 py-6 text-center text-sm text-muted2">ยังไม่มีรายจ่าย</div>
            @endforelse
            <div class="px-4.5 py-3 flex justify-between gap-2.5 text-[13px] bg-surface2">
                <span class="font-medium">รวมรายจ่าย</span>
                <span class="font-semibold tabular-nums">{{ number_format($expenseTotal, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Create order modal --}}
    @if ($showForm)
        <div wire:click="closeForm" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[440px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <span class="text-[17px] font-semibold tracking-tight">{{ $editingOrderId ? 'แก้ไขออเดอร์ออนไลน์' : 'เพิ่มออเดอร์ออนไลน์' }}</span>
                    <button wire:click="closeForm" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">วันที่</label>
                        <input type="date" wire:model="form.date" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">ช่องทาง</label>
                        <select wire:model="form.channel" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                            <option value="Shopee">Shopee</option>
                            <option value="Lazada">Lazada</option>
                            <option value="TikTok">TikTok</option>
                            <option value="Facebook">Facebook</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">เลขที่คำสั่งซื้อ</label>
                    <input type="text" wire:model="form.order_no" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                    @error('form.order_no') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">รายการสินค้า</label>
                    <input type="text" wire:model="form.item" placeholder="ชื่อสินค้าตามที่ลูกค้าสั่งซื้อ" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                </div>
                <div class="grid grid-cols-3 gap-2.5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">รายรับ (บาท)</label>
                        <input type="number" step="0.01" wire:model="form.revenue" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] text-right tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">จำนวน</label>
                        <input type="number" min="1" wire:model="form.qty" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] text-right tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">สถานะ</label>
                        <select wire:model="form.status" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                            <option value="success">สำเร็จ</option>
                            <option value="failed">จัดส่งไม่สำเร็จ</option>
                            <option value="returned">คืนสินค้า</option>
                        </select>
                    </div>
                </div>
                @if ($formError)
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3 py-2.5">{{ $formError }}</span>
                @endif
                <div class="flex gap-2.5">
                    <button wire:click="save" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">{{ $editingOrderId ? 'บันทึกการแก้ไข' : 'บันทึก' }}</button>
                    <button wire:click="closeForm" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif

    {{-- SKU match modal --}}
    @if ($matchOrder)
        <div wire:click="closeSkuMatch" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[420px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[17px] font-semibold tracking-tight">จับคู่ SKU</span>
                        <span class="text-[12.5px] text-muted2">{{ $matchOrder->order_no }} · &ldquo;{{ $matchOrder->item }}&rdquo;</span>
                    </div>
                    <button wire:click="closeSkuMatch" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">สินค้าในระบบ</label>
                    <select wire:model.live="matchProductId" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                        <option value="">ไม่จับคู่</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                        @endforeach
                    </select>
                </div>

                @if ($matchProductId !== '')
                    <div class="grid grid-cols-2 gap-2.5">
                        @if ($matchProduct && $matchProduct->variants->isNotEmpty())
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[12.5px] font-medium text-text2">ขนาดที่ขาย</label>
                                <select wire:model="matchVariantId" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                                    <option value="">หน่วยฐาน ({{ $matchProduct->unit?->name }})</option>
                                    @foreach ($matchProduct->variants as $v)
                                        <option value="{{ $v->id }}">{{ $v->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="flex flex-col gap-1.5 {{ $matchProduct && $matchProduct->variants->isNotEmpty() ? '' : 'col-span-2' }}">
                            <label class="text-[12.5px] font-medium text-text2">จำนวนที่ขาย</label>
                            <input type="number" min="1" wire:model="matchQty" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                        </div>
                    </div>
                    <span class="text-[11.5px] text-muted2 leading-relaxed">จับคู่แล้วระบบจะตัดสต็อกสินค้านี้ให้อัตโนมัติ (ถ้าออเดอร์นี้สถานะ &ldquo;สำเร็จ&rdquo;)</span>
                @endif

                @if ($matchError)
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3.5 py-2.5">{{ $matchError }}</span>
                @endif

                <div class="flex gap-2.5">
                    <button wire:click="saveSkuMatch" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">บันทึกการจับคู่</button>
                    <button wire:click="closeSkuMatch" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Order detail panel --}}
    @if ($detailOrder)
        <div wire:click="closeDetail" class="fixed inset-0 bg-black/35 z-[80] flex justify-end">
            <div wire:click.stop class="w-full sm:w-[440px] max-w-full h-full bg-surface shadow-2xl overflow-y-auto flex flex-col">
                <div class="sticky top-0 z-10 p-5 bg-surface border-b border-line flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-0.5 leading-relaxed">
                        <span class="text-[11.5px] text-muted2 tabular-nums">{{ $detailOrder->date->format('d/m/Y') }} · {{ $detailOrder->channel }}</span>
                        <span class="text-[16px] font-semibold tracking-tight">{{ $detailOrder->order_no }}</span>
                    </div>
                    <button wire:click="closeDetail" class="w-[29px] h-[29px] shrink-0 rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>

                <div class="p-5 flex flex-col gap-4">
                    <div class="border border-border rounded-xl overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-b border-hairline2 text-[13px]">
                            <span class="text-muted">รายการ</span><span class="font-medium text-right max-w-[240px]">{{ $detailOrder->item }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-b border-hairline2 text-[13px]">
                            <span class="text-muted">รายรับ</span><span class="font-semibold tabular-nums">{{ number_format($detailOrder->revenue, 2) }} บาท</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-b border-hairline2 text-[13px]">
                            @php $tone = ['success' => 'accent', 'failed' => 'danger', 'returned' => 'danger'][$detailOrder->status]; @endphp
                            <span class="text-muted">สถานะ</span>
                            <span @class(['font-medium', 'text-accent' => $tone === 'accent', 'text-danger' => $tone === 'danger'])>{{ ['success' => 'สำเร็จ', 'failed' => 'จัดส่งไม่สำเร็จ', 'returned' => 'คืนสินค้า'][$detailOrder->status] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-b border-hairline2 text-[13px]">
                            <span class="text-muted">จับคู่สินค้า</span>
                            <button wire:click="openSkuMatch({{ $detailOrder->id }})"
                                @class(['font-medium hover:underline text-right', 'text-accent' => $detailOrder->product, 'text-muted3' => ! $detailOrder->product])>
                                {{ $detailOrder->product ? $detailOrder->product->sku.($detailOrder->variant ? ' · '.$detailOrder->variant->label : '').' × '.$detailOrder->qty : 'ยังไม่จับคู่ · แก้ไข' }}
                            </button>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 text-[13px]">
                            <span class="text-muted">ตัดสต็อก</span>
                            @if ($detailOrder->stockMovement)
                                <span class="font-medium text-accent">แล้ว · {{ $detailOrder->stockMovement->doc_no }}</span>
                            @elseif ($detailOrder->product)
                                <span class="font-medium text-warn">ยังไม่ได้ตัด</span>
                            @else
                                <span class="text-muted3">—</span>
                            @endif
                        </div>
                    </div>

                    @if (! empty($detailOrder->shopee_raw_data))
                        <div class="flex flex-col gap-2">
                            <span class="text-[12.5px] font-semibold text-muted2">ข้อมูลจากไฟล์ Export Shopee</span>
                            <div class="border border-border rounded-xl overflow-hidden">
                                @foreach ($detailOrder->shopee_raw_data as $label => $value)
                                    <div class="flex flex-col gap-0.5 px-3.5 py-2 border-b border-hairline2 last:border-0 text-[12.5px]">
                                        <span class="text-muted2">{{ $label }}</span>
                                        <span class="font-medium break-words">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <span class="text-[12.5px] text-muted2">ออเดอร์นี้ไม่มีข้อมูลเพิ่มเติมจาก Shopee (บันทึกด้วยตนเอง)</span>
                    @endif

                    @can('online_sales')
                        <button wire:click="openEdit({{ $detailOrder->id }})" class="py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:border-accent hover:text-accent">แก้ไขออเดอร์</button>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirm --}}
    @if ($deleteOrder)
        <div wire:click="cancelDelete" class="fixed inset-0 bg-black/45 z-[95] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ลบออเดอร์ &ldquo;{{ $deleteOrder->order_no }}&rdquo;?</span>
                        <span class="text-[12.5px] text-muted">ข้อมูลออเดอร์นี้จะถูกลบออกจากระบบถาวร</span>
                        @if ($deleteOrder->stock_movement_id)
                            <span class="text-[12.5px] text-warn">สต็อกที่เคยตัดไว้จากออเดอร์นี้จะถูกคืนกลับด้วย</span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="cancelDelete" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ยกเลิก</button>
                    <button wire:click="delete" class="flex-1 py-2.5 rounded-[10px] bg-danger text-white text-[13px] font-medium hover:bg-danger-ink2">ลบออเดอร์</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Add expense modal --}}
    @if ($showExpenseForm)
        <div wire:click="closeExpenseForm" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <span class="text-[17px] font-semibold tracking-tight">เพิ่มรายจ่าย</span>
                    <button wire:click="closeExpenseForm" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">วันที่</label>
                    <input type="date" wire:model="expenseForm.date" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    @error('date') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">รายการ</label>
                    <input type="text" wire:model="expenseForm.label" placeholder="เช่น ค่าโฆษณา Shopee Ads" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                    @error('label') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">จำนวนเงิน (บาท)</label>
                    <input type="number" step="0.01" wire:model="expenseForm.amount" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] text-right tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    @error('amount') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="saveExpense" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">บันทึก</button>
                    <button wire:click="closeExpenseForm" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete expense confirm --}}
    @if ($deleteExpense)
        <div wire:click="cancelDeleteExpense" class="fixed inset-0 bg-black/45 z-[95] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ลบรายจ่าย &ldquo;{{ $deleteExpense->label }}&rdquo;?</span>
                        <span class="text-[12.5px] text-muted">ลบออกจากระบบถาวร</span>
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="cancelDeleteExpense" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ยกเลิก</button>
                    <button wire:click="deleteExpense" class="flex-1 py-2.5 rounded-[10px] bg-danger text-white text-[13px] font-medium hover:bg-danger-ink2">ลบรายจ่าย</button>
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
                        <span class="text-[16px] font-semibold">นำเข้าออเดอร์ออนไลน์จาก Excel</span>
                        <span class="text-[12.5px] text-muted2">ใช้ได้ทั้งไฟล์ Template ของเรา และไฟล์ Export ออเดอร์ที่ดาวน์โหลดตรงจาก Shopee Seller Centre — ระบบจะจำรูปแบบไฟล์ให้อัตโนมัติ ถ้าเลขที่คำสั่งซื้อซ้ำกับที่มีอยู่แล้วในช่องทางเดียวกันจะข้ามแถวนั้น</span>
                    </div>
                    <button wire:click="closeImportModal" class="text-danger hover:opacity-70 text-[19px] leading-none shrink-0">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto p-5 flex flex-col gap-4">

                    <div class="bg-surface2 border border-border rounded-[10px] px-3.5 py-3 flex flex-col gap-1.5">
                        <span class="text-[12.5px] font-semibold text-text2">วิธีดาวน์โหลดไฟล์ออเดอร์จาก Shopee</span>
                        <ol class="text-[12px] text-muted2 leading-relaxed list-decimal list-inside flex flex-col gap-0.5">
                            <li>เข้าสู่ระบบ Shopee ที่ Seller Centre</li>
                            <li>ไปที่ <b class="text-text2 font-medium">คำสั่งซื้อของฉัน</b></li>
                            <li>กด <b class="text-text2 font-medium">ทั้งหมด</b></li>
                            <li>กด <b class="text-text2 font-medium">ดาวน์โหลด</b></li>
                            <li>เลือกช่วงเวลาที่ต้องการ</li>
                            <li>กด <b class="text-text2 font-medium">ดาวน์โหลด</b> อีกครั้งเพื่อรับไฟล์</li>
                        </ol>
                        <span class="text-[11.5px] text-muted3">ได้ไฟล์แล้วนำมาอัปโหลดด้านล่างนี้ได้เลย</span>
                    </div>

                    <div>
                        <input type="file" wire:model="importFile" accept=".xlsx,.xls"
                            class="w-full text-[12.5px] text-text3 border border-border3 rounded-lg bg-surface pl-1 pr-3 py-1 cursor-pointer focus:outline-none focus:border-accent
                            file:mr-3 file:rounded-lg file:border-0 file:bg-chip file:text-text2 file:px-3.5 file:py-2 file:text-[12.5px] file:font-medium file:cursor-pointer hover:file:bg-chip2">
                        <div wire:loading wire:target="importFile" class="text-[12px] text-muted2 mt-1.5">กำลังอ่านไฟล์...</div>
                    </div>

                    @if ($importFileError)
                        <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3.5 py-2.5">{{ $importFileError }}</span>
                    @endif

                    @if ($importRows !== null)
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center gap-2 bg-accent-tint text-accent-ink rounded-lg px-3.5 py-2.5 text-[12.5px] font-medium">
                                พบ {{ count($importRows) }} รายการที่ถูกต้อง
                            </div>

                            @if (! empty($importErrors))
                                <div class="bg-danger-tint rounded-lg px-3.5 py-2.5 flex flex-col gap-1">
                                    <span class="text-[12.5px] font-medium text-danger">ข้าม/เตือน {{ count($importErrors) }} รายการ:</span>
                                    <ul class="text-[11.5px] text-danger-ink2 list-disc list-inside flex flex-col gap-0.5 max-h-[100px] overflow-y-auto">
                                        @foreach ($importErrors as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (count($importRows) > 0)
                                <div class="border border-border rounded-[10px] overflow-auto max-h-[280px]">
                                    <table class="w-full text-[12px] whitespace-nowrap">
                                        <thead>
                                            <tr class="sticky top-0 z-10 bg-surface2 border-b border-line text-[10.5px] font-semibold tracking-wide text-muted2">
                                                <th class="text-left px-3 py-2">วันที่</th>
                                                <th class="text-left px-3 py-2">เลขที่คำสั่งซื้อ</th>
                                                <th class="text-left px-3 py-2">รายการ</th>
                                                <th class="text-left px-3 py-2">สินค้าที่จับคู่</th>
                                                <th class="text-left px-3 py-2">ช่องทาง</th>
                                                <th class="text-right px-3 py-2">รายรับ</th>
                                                <th class="text-right px-3 py-2">จำนวน</th>
                                                <th class="text-center px-3 py-2">สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-hairline2">
                                            @foreach ($importRows as $row)
                                                <tr class="hover:bg-surface2">
                                                    <td class="px-3 py-2 text-muted2 tabular-nums">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                                    <td class="px-3 py-2 font-medium">{{ $row['order_no'] }}</td>
                                                    <td class="px-3 py-2 max-w-[170px] truncate">{{ $row['item'] }}</td>
                                                    <td class="px-3 py-2 text-muted2">{{ $row['product_name'] ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-muted2">{{ $row['channel'] }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row['revenue'], 2) }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums">{{ $row['qty'] ?? 1 }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        <span @class([
                                                            'text-[10.5px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap',
                                                            'bg-accent-tint text-accent' => $row['status'] === 'success',
                                                            'bg-danger-tint text-danger' => $row['status'] === 'failed',
                                                            'bg-warn-tint text-warn' => $row['status'] === 'returned',
                                                        ])>{{ ['success' => 'สำเร็จ', 'failed' => 'จัดส่งไม่สำเร็จ', 'returned' => 'คืนสินค้า'][$row['status']] }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex gap-2.5 p-5 pt-0">
                    @if ($importRows !== null && count($importRows) > 0)
                        <button wire:click="confirmImport" wire:loading.attr="disabled" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover disabled:opacity-50">ยืนยันนำเข้า {{ count($importRows) }} รายการ</button>
                    @endif
                    <button wire:click="closeImportModal" class="px-5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif
</div>
