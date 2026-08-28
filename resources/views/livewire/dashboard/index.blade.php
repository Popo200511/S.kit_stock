<div class="flex flex-col gap-4">

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ($kpis as $k)
            <div class="bg-surface border border-border rounded-[15px] p-4 shadow-sm flex flex-col gap-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[12.5px] text-muted font-medium truncate">{{ $k['label'] }}</span>
                    <span @class(['w-[27px] h-[27px] shrink-0 rounded-lg flex items-center justify-center', 'bg-accent-tint text-accent' => $k['tone'] === 'accent', 'bg-warn-tint text-warn' => $k['tone'] === 'warn'])>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $k['d'] }}"></path></svg>
                    </span>
                </div>
                <span class="text-[19px] font-semibold tracking-tight tabular-nums">{{ $k['value'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-3">
        {{-- Chart --}}
        <div class="bg-surface border border-border rounded-[15px] p-4.5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4.5">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[14.5px] font-semibold">มูลค่ารับเข้า–เบิกออก {{ $period === 'year' ? 'รายปี' : '7 เดือน' }}</span>
                    <span class="text-xs text-muted2">{{ $series[0]['label'] }} – {{ $series[count($series) - 1]['label'] }}{{ $period === 'year' ? ' '.($selectedYear + 543) : '' }} · หน่วยบาท</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($period === 'year')
                        <div class="flex items-center gap-1 bg-chip p-[3px] rounded-[9px]">
                            <button wire:click="prevYear" title="ปีก่อนหน้า" class="w-[26px] h-[26px] rounded-[7px] flex items-center justify-center text-muted2 hover:bg-surface hover:shadow-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"></path></svg>
                            </button>
                            <span class="px-1.5 text-xs font-semibold tabular-nums">{{ $selectedYear + 543 }}</span>
                            <button wire:click="nextYear" title="ปีถัดไป" class="w-[26px] h-[26px] rounded-[7px] flex items-center justify-center text-muted2 hover:bg-surface hover:shadow-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"></path></svg>
                            </button>
                        </div>
                    @endif
                    <div class="flex gap-1 bg-chip p-[3px] rounded-[9px]">
                        <button wire:click="setPeriod('recent')" class="px-2.5 py-1.5 rounded-[7px] text-xs font-medium {{ $period === 'recent' ? 'bg-surface shadow-sm' : 'text-muted2' }}">7 เดือนล่าสุด</button>
                        <button wire:click="setPeriod('year')" class="px-2.5 py-1.5 rounded-[7px] text-xs font-medium {{ $period === 'year' ? 'bg-surface shadow-sm' : 'text-muted2' }}">รายปี</button>
                    </div>
                    <div class="flex gap-1 bg-chip p-[3px] rounded-[9px]">
                        <button wire:click="setChartView('bar')" class="px-2.5 py-1.5 rounded-[7px] text-xs font-medium {{ $chartView === 'bar' ? 'bg-surface shadow-sm' : 'text-muted2' }}">แท่ง</button>
                        <button wire:click="setChartView('pie')" class="px-2.5 py-1.5 rounded-[7px] text-xs font-medium {{ $chartView === 'pie' ? 'bg-surface shadow-sm' : 'text-muted2' }}">วงกลม</button>
                        <button wire:click="setChartView('table')" class="px-2.5 py-1.5 rounded-[7px] text-xs font-medium {{ $chartView === 'table' ? 'bg-surface shadow-sm' : 'text-muted2' }}">ตาราง</button>
                    </div>
                </div>
            </div>

            @if ($chartView === 'bar')
                <div class="flex items-center justify-between gap-3.5 mb-2.5">
                    <span class="text-[11px] text-muted2">คลิกแท่งรับเข้า/เบิกออกเพื่อดูเอกสาร</span>
                    <div class="flex justify-end gap-3.5 text-[11.5px] text-muted">
                        <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-[3px] bg-accent inline-block"></i>รับเข้า</span>
                        <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-[3px] bg-bar-neutral inline-block"></i>เบิกออก</span>
                    </div>
                </div>
                <div class="flex items-end gap-2 h-[180px] border-b border-line">
                    @foreach ($series as $s)
                        @php
                            $inActive = ! $selectedMonth || ($selectedMonth === $s['key'] && $selectedType === 'in');
                            $outActive = ! $selectedMonth || ($selectedMonth === $s['key'] && $selectedType === 'out');
                        @endphp
                        <div class="flex-1 h-full flex items-end justify-center gap-1">
                            <button type="button" wire:key="dash-bar-in-{{ $s['key'] }}" wire:click="selectBar('{{ $s['key'] }}', 'in')"
                                title="ดูเอกสารรับเข้าเดือน{{ $s['label'] }} ({{ number_format($s['in']) }} บาท)"
                                @class(['w-[42%] max-w-[19px] rounded-t-[5px] bg-accent transition-opacity cursor-pointer hover:opacity-80', 'opacity-40' => ! $inActive])
                                style="height:{{ round($s['in'] / $maxSeries * 100) }}%"></button>
                            <button type="button" wire:key="dash-bar-out-{{ $s['key'] }}" wire:click="selectBar('{{ $s['key'] }}', 'out')"
                                title="ดูเอกสารเบิกออกเดือน{{ $s['label'] }} ({{ number_format($s['out']) }} บาท)"
                                @class(['w-[42%] max-w-[19px] rounded-t-[5px] bg-bar-neutral transition-opacity cursor-pointer hover:opacity-80', 'opacity-40' => ! $outActive])
                                style="height:{{ round($s['out'] / $maxSeries * 100) }}%"></button>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-2 mt-2">
                    @foreach ($series as $s)
                        <span wire:key="dash-lbl-{{ $s['key'] }}" @class(['flex-1 text-center text-[11px]', 'text-accent font-semibold' => $s['key'] === $selectedMonth, 'text-muted2' => $s['key'] !== $selectedMonth])>{{ $s['label'] }}</span>
                    @endforeach
                </div>
            @endif

            @if ($chartView === 'pie')
                @php $selectedLegend = collect($pie['legend'])->firstWhere('label', $selectedCategory); @endphp
                <span class="text-[11px] text-muted2 block mb-3">คลิกหมวดในรายการเพื่อดูเอกสาร</span>
                <div class="flex flex-wrap items-center gap-5">
                    <div class="relative w-[186px] h-[186px] shrink-0 rounded-full" style="background:{{ $pie['gradient'] }}">
                        <div class="absolute inset-[31%] rounded-full bg-surface flex flex-col items-center justify-center gap-0.5 shadow-[inset_0_0_0_1px_var(--line)] text-center px-2">
                            @if ($selectedLegend)
                                <span class="text-[10.5px] text-muted2 truncate max-w-full">{{ $selectedLegend['label'] }}</span>
                                <span class="text-sm font-semibold tabular-nums tracking-tight">{{ number_format($selectedLegend['value']) }} บาท</span>
                            @else
                                <span class="text-[10.5px] text-muted2">เบิกออกรวม</span>
                                <span class="text-sm font-semibold tabular-nums tracking-tight">{{ number_format($pie['total']) }} บาท</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex-1 min-w-[190px] flex flex-col gap-1">
                        @forelse ($pie['legend'] as $l)
                            <button type="button" wire:key="pie-{{ $l['label'] }}" wire:click="selectCategory('{{ $l['label'] }}')"
                                @class([
                                    'flex items-center gap-2 text-[12.5px] rounded-lg px-1.5 py-1 -mx-1.5 text-left transition-colors hover:bg-surface2 cursor-pointer',
                                    'bg-surface2' => $selectedCategory === $l['label'],
                                ])>
                                <i class="w-2.5 h-2.5 shrink-0 rounded-[3px] inline-block" style="background:{{ $l['color'] }}"></i>
                                <span @class(['flex-1 min-w-0 truncate', 'font-semibold' => $selectedCategory === $l['label']])>{{ $l['label'] }}</span>
                                <span class="tabular-nums text-text4 whitespace-nowrap">{{ number_format($l['value']) }} บาท</span>
                                <span class="tabular-nums font-semibold min-w-[38px] text-right whitespace-nowrap">{{ $l['pct'] }}%</span>
                            </button>
                        @empty
                            <span class="text-sm text-muted2">ยังไม่มีข้อมูลเบิกออกในช่วงนี้</span>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($chartView === 'table')
                <div class="border border-line rounded-xl overflow-hidden">
                    <div class="grid grid-cols-4 gap-2.5 px-3.5 py-2.5 bg-surface2 border-b border-line text-[11px] font-semibold text-muted2">
                        <span>เดือน</span><span class="text-right">รับเข้า</span><span class="text-right">เบิกออก</span><span class="text-right">ส่วนต่าง</span>
                    </div>
                    @php $totIn = 0; $totOut = 0; @endphp
                    @foreach ($series as $s)
                        @php $diff = $s['in'] - $s['out']; $totIn += $s['in']; $totOut += $s['out']; @endphp
                        <div class="grid grid-cols-4 gap-2.5 px-3.5 py-2.5 border-b border-hairline2 text-[12.5px] items-center">
                            <span class="font-medium">{{ $s['label'] }}</span>
                            <span class="text-right tabular-nums text-text4">{{ number_format($s['in']) }}</span>
                            <span class="text-right tabular-nums text-text4">{{ number_format($s['out']) }}</span>
                            <span @class(['text-right tabular-nums font-semibold', 'text-accent' => $diff >= 0, 'text-danger' => $diff < 0])>{{ $diff >= 0 ? '+' : '' }}{{ number_format($diff) }}</span>
                        </div>
                    @endforeach
                    <div class="grid grid-cols-4 gap-2.5 px-3.5 py-2.5 bg-surface2 text-[12.5px] font-semibold">
                        <span>รวม</span>
                        <span class="text-right tabular-nums">{{ number_format($totIn) }}</span>
                        <span class="text-right tabular-nums">{{ number_format($totOut) }}</span>
                        <span @class(['text-right tabular-nums', 'text-accent' => $totIn - $totOut >= 0, 'text-danger' => $totIn - $totOut < 0])>{{ number_format($totIn - $totOut) }}</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-3">
            {{-- Quick actions --}}
            <div class="bg-surface border border-border rounded-[15px] p-4.5 shadow-sm flex flex-col gap-3.5">
                <span class="text-sm font-semibold">ทางด่วนงานประจำวัน</span>
                <div class="grid grid-cols-2 gap-2.5">
                    @can('stock_movements')
                        <a href="{{ route('movements.index') }}" wire:navigate class="flex flex-col gap-2 p-3.5 border border-border rounded-xl hover:border-accent hover:bg-accent-soft">
                            <span class="w-7 h-7 rounded-lg bg-accent-tint text-accent flex items-center justify-center">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3v18M7 3 3 7M7 3l4 4M17 21V3M17 21l4-4M17 21l-4-4"></path></svg>
                            </span>
                            <span class="text-[12.5px] font-medium leading-snug">บันทึกรับเข้า–เบิกออก</span>
                        </a>
                    @endcan
                    @can('edit_products')
                        <a href="{{ route('products.index') }}" wire:navigate class="flex flex-col gap-2 p-3.5 border border-border rounded-xl hover:border-accent hover:bg-accent-soft">
                            <span class="w-7 h-7 rounded-lg bg-accent-tint text-accent flex items-center justify-center">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>
                            </span>
                            <span class="text-[12.5px] font-medium leading-snug">เพิ่มสินค้าใหม่</span>
                        </a>
                    @endcan
                    <a href="{{ route('alerts.index') }}" wire:navigate class="flex flex-col gap-2 p-3.5 border border-border rounded-xl hover:border-accent hover:bg-accent-soft">
                        <span class="w-7 h-7 rounded-lg bg-accent-tint text-accent flex items-center justify-center">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 16H3zM12 9v5M12 17h.01"></path></svg>
                        </span>
                        <span class="text-[12.5px] font-medium leading-snug">ดูสินค้าใกล้หมด</span>
                    </a>
                    <a href="{{ route('reports.index') }}" wire:navigate class="flex flex-col gap-2 p-3.5 border border-border rounded-xl hover:border-accent hover:bg-accent-soft">
                        <span class="w-7 h-7 rounded-lg bg-accent-tint text-accent flex items-center justify-center">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"></path></svg>
                        </span>
                        <span class="text-[12.5px] font-medium leading-snug">ดูรายงาน / กำไร</span>
                    </a>
                </div>
            </div>

            {{-- Urgent reorder --}}
            <div class="bg-surface border border-border rounded-[15px] p-4.5 shadow-sm flex flex-col gap-3.5">
                <div class="flex items-center justify-between gap-2.5">
                    <span class="text-sm font-semibold">ต้องสั่งซื้อด่วน</span>
                    <a href="{{ route('alerts.index') }}" wire:navigate class="text-xs text-accent font-medium hover:underline">ดูทั้งหมด</a>
                </div>
                @forelse ($lowTop as $row)
                    @php $status = $row['product']->stock_status; @endphp
                    <div class="flex flex-col gap-1.5 pb-2.5 border-b border-hairline last:border-0 last:pb-0">
                        <div class="flex items-baseline justify-between gap-2.5">
                            <span class="text-[12.5px] font-medium truncate">{{ $row['product']->name }}</span>
                            <span @class(['text-xs font-semibold tabular-nums whitespace-nowrap', 'text-warn' => $status['tone'] === 'warn', 'text-danger' => $status['tone'] === 'danger'])>{{ $row['product']->stock_display }} {{ $row['product']->unit?->name }}</span>
                        </div>
                        <div class="h-1 rounded-full bg-hairline overflow-hidden">
                            <div @class(['h-full rounded-full', 'bg-warn' => $status['tone'] === 'warn', 'bg-danger' => $status['tone'] === 'danger']) style="width:{{ $row['fillPct'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <span class="text-[12.5px] text-muted2">ยังไม่มีสินค้าที่ต้องสั่งซื้อ</span>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent documents --}}
    <div class="bg-surface border border-border rounded-[15px] shadow-sm">
        <div class="px-4.5 py-3.5 border-b border-line flex items-center justify-between gap-3">
            <span class="text-sm font-semibold">{{ $recentLabel }}</span>
            <div class="flex items-center gap-3">
                @if ($selectedMonth || $selectedCategory)
                    <button wire:click="clearMonthSelection" class="text-xs text-muted2 font-medium hover:underline">ล้างตัวกรอง</button>
                @endif
                <a href="{{ route('movements.index') }}" wire:navigate class="text-xs text-accent font-medium hover:underline">ดูทั้งหมด</a>
            </div>
        </div>
        @forelse ($recent as $m)
            <a href="{{ route('movements.index', ['doc' => $m->id]) }}"
                wire:navigate
                class="flex items-center gap-3.5 px-4.5 py-3.5 border-b border-hairline2 last:border-0 hover:bg-surface2 transition-colors">
                <span @class(['w-8 h-8 shrink-0 rounded-lg flex items-center justify-center text-[15px] font-semibold', 'bg-accent-tint text-accent' => $m->type === 'in', 'bg-chip text-text4' => $m->type === 'out'])>{{ $m->type === 'in' ? '+' : '−' }}</span>
                <div class="flex-1 min-w-0 flex flex-col leading-snug">
                    <span class="text-[13px] font-medium truncate">{{ $m->party ?: $m->doc_no }}</span>
                    <span class="text-[11.5px] text-muted2 tabular-nums truncate">{{ $m->doc_no }} · {{ $m->date->format('d/m/Y') }} · {{ $m->lines->count() }} รายการ</span>
                </div>
                <span @class(['text-[13px] font-semibold tabular-nums whitespace-nowrap', 'text-accent' => $m->type === 'in', 'text-text4' => $m->type === 'out'])>{{ number_format($m->total) }} บาท</span>
            </a>
        @empty
            <div class="px-4.5 py-9 text-center text-sm text-muted2">{{ ($selectedMonth || $selectedCategory) ? 'ไม่พบเอกสารที่ตรงกับตัวกรอง' : 'ยังไม่มีเอกสาร' }}</div>
        @endforelse
    </div>
</div>
