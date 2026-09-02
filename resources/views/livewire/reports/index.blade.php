<div class="flex flex-col gap-4">

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ($kpis as $k)
            <div class="bg-surface border border-border rounded-[15px] p-4 shadow-sm flex flex-col gap-1.5">
                <span class="text-[12.5px] text-muted font-medium">{{ $k['label'] }}</span>
                <span class="text-[19px] font-semibold tracking-tight tabular-nums">{{ $k['value'] }}</span>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11.5px] text-muted2">{{ $k['note'] }}</span>
                    @if ($k['delta'])
                        <span @class(['text-[11px] font-medium whitespace-nowrap', 'text-accent' => $k['delta']['tone'] === 'accent', 'text-danger' => $k['delta']['tone'] === 'danger'])>{{ $k['delta']['text'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Sales chart: monthly (7 เดือนล่าสุด) หรือ รายวัน (ของเดือนที่เลือก) --}}
        <div class="bg-surface border border-border rounded-[15px] p-4.5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                <div class="flex flex-col gap-0.5">
                    @if ($chartMode === 'monthly')
                        <span class="text-[14.5px] font-semibold">ยอดขายรายเดือน</span>
                        <span class="text-xs text-muted2">มูลค่าเบิกออก {{ $series[0]['label'] }} – {{ $series[count($series) - 1]['label'] }} · คลิกแท่งเพื่อดูข้อมูลเดือนนั้น</span>
                    @elseif ($chartMode === 'daily')
                        <span class="text-[14.5px] font-semibold">ยอดขายรายวัน · {{ $selectedMonthLabel }}</span>
                        <span class="text-xs text-muted2">มูลค่าเบิกออกแต่ละวันในเดือนที่เลือก</span>
                    @else
                        <span class="text-[14.5px] font-semibold">ยอดขายรายปี · {{ $selectedYear + 543 }}</span>
                        <span class="text-xs text-muted2">มูลค่าเบิกออกแต่ละเดือนตลอดปี · คลิกแท่งเพื่อดูข้อมูลเดือนนั้น</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($chartMode === 'yearly')
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
                        <button wire:click="setChartMode('daily')" class="px-2.5 py-1.5 rounded-[7px] text-xs font-medium {{ $chartMode === 'daily' ? 'bg-surface shadow-sm' : 'text-muted2' }}">รายวัน</button>
                        <button wire:click="setChartMode('monthly')" class="px-2.5 py-1.5 rounded-[7px] text-xs font-medium {{ $chartMode === 'monthly' ? 'bg-surface shadow-sm' : 'text-muted2' }}">รายเดือน</button>
                        <button wire:click="setChartMode('yearly')" class="px-2.5 py-1.5 rounded-[7px] text-xs font-medium {{ $chartMode === 'yearly' ? 'bg-surface shadow-sm' : 'text-muted2' }}">รายปี</button>
                    </div>
                </div>
            </div>

            @if ($chartMode === 'monthly')
                <div class="flex items-end gap-2 h-[196px] border-b border-line">
                    @foreach ($series as $s)
                        <button type="button" wire:key="bar-{{ $s['key'] }}" wire:click="selectMonth('{{ $s['key'] }}')" title="ดูข้อมูลเดือน{{ $s['label'] }}"
                            class="flex-1 h-full flex flex-col justify-end items-center gap-1.5 group cursor-pointer">
                            <span @class(['text-[10.5px] tabular-nums', 'text-accent font-semibold' => $s['key'] === $selectedMonth, 'text-muted' => $s['key'] !== $selectedMonth])>{{ $s['out'] > 0 ? number_format($s['out'] / 1000, 1).'k' : '' }}</span>
                            <div @class([
                                'w-full max-w-[42px] rounded-t-[6px] bg-accent transition-opacity',
                                'opacity-100' => $s['key'] === $selectedMonth,
                                'opacity-40 group-hover:opacity-70' => $s['key'] !== $selectedMonth,
                            ]) style="height:{{ round($s['out'] / $maxOut * 100) }}%"></div>
                        </button>
                    @endforeach
                </div>
                <div class="flex gap-2 mt-2">
                    @foreach ($series as $s)
                        <span wire:key="lbl-{{ $s['key'] }}" @class(['flex-1 text-center text-[11px]', 'text-accent font-semibold' => $s['key'] === $selectedMonth, 'text-muted2' => $s['key'] !== $selectedMonth])>{{ $s['label'] }}</span>
                    @endforeach
                </div>
            @elseif ($chartMode === 'daily')
                <div class="flex items-end gap-[3px] h-[196px] border-b border-line overflow-x-auto">
                    @foreach ($dailySeries as $d)
                        <div wire:key="daybar-{{ $d['key'] }}" title="{{ \Illuminate\Support\Carbon::parse($d['key'])->format('d/m/Y') }} · {{ number_format($d['out']) }} บาท"
                            class="flex-1 min-w-[9px] h-full flex flex-col justify-end items-center group">
                            <div @class(['w-full rounded-t-[3px] transition-opacity', 'bg-accent' => $d['out'] > 0, 'bg-hairline' => $d['out'] == 0, 'opacity-70 group-hover:opacity-100' => $d['out'] > 0])
                                style="height:{{ $d['out'] > 0 ? round($d['out'] / $maxDaily * 100) : 2 }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-[3px] mt-2">
                    @foreach ($dailySeries as $d)
                        <span wire:key="daylbl-{{ $d['key'] }}" class="flex-1 min-w-[9px] text-center text-[9px] text-muted2">{{ $d['label'] % 5 === 0 || $d['label'] == 1 ? $d['label'] : '' }}</span>
                    @endforeach
                </div>
            @else
                <div class="flex items-end gap-2 h-[196px] border-b border-line">
                    @foreach ($yearlySeries as $s)
                        <button type="button" wire:key="ybar-{{ $s['key'] }}" wire:click="selectMonth('{{ $s['key'] }}')" title="ดูข้อมูลเดือน{{ $s['label'] }} ({{ number_format($s['out']) }} บาท)"
                            class="flex-1 h-full flex flex-col justify-end items-center gap-1.5 group cursor-pointer">
                            <span @class(['text-[10.5px] tabular-nums', 'text-accent font-semibold' => $s['key'] === $selectedMonth, 'text-muted' => $s['key'] !== $selectedMonth])>{{ $s['out'] > 0 ? number_format($s['out'] / 1000, 1).'k' : '' }}</span>
                            <div @class([
                                'w-full max-w-[42px] rounded-t-[6px] bg-accent transition-opacity',
                                'opacity-100' => $s['key'] === $selectedMonth,
                                'opacity-40 group-hover:opacity-70' => $s['key'] !== $selectedMonth,
                            ]) style="height:{{ round($s['out'] / $maxYearly * 100) }}%"></div>
                        </button>
                    @endforeach
                </div>
                <div class="flex gap-2 mt-2">
                    @foreach ($yearlySeries as $s)
                        <span wire:key="ylbl-{{ $s['key'] }}" @class(['flex-1 text-center text-[11px]', 'text-accent font-semibold' => $s['key'] === $selectedMonth, 'text-muted2' => $s['key'] !== $selectedMonth])>{{ $s['label'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Stock value by category --}}
        <div class="bg-surface border border-border rounded-[15px] p-4.5 shadow-sm flex flex-col gap-3.5">
            <span class="text-sm font-semibold">มูลค่าสต็อกตามประเภท</span>
            @forelse ($catBars as $c)
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-baseline justify-between gap-2.5">
                        <span class="text-[12.5px] truncate">{{ $c['name'] }}</span>
                        <span class="text-xs font-semibold tabular-nums whitespace-nowrap">{{ number_format($c['value']) }} บาท</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-hairline overflow-hidden">
                        <div class="h-full rounded-full bg-accent" style="width:{{ round($c['value'] / $maxCat * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <span class="text-sm text-muted2">ยังไม่มีข้อมูลสต็อก</span>
            @endforelse
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Top products --}}
        <div class="bg-surface border border-border rounded-[15px] shadow-sm">
            <div class="px-4.5 py-3.5 border-b border-line text-sm font-semibold">สินค้าขายดี · {{ $selectedMonthLabel }}</div>
            @forelse ($topProducts as $i => $t)
                <div class="grid grid-cols-[1fr_.7fr_1fr_1fr] gap-3 px-4.5 py-3.5 border-b border-hairline2 last:border-0 items-center text-[13px]">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-[23px] h-[23px] shrink-0 rounded-lg bg-hairline text-muted text-[11.5px] font-semibold flex items-center justify-center tabular-nums">{{ $i + 1 }}</span>
                        <span class="font-medium truncate">{{ $t->product_name }}</span>
                    </div>
                    <span class="text-right tabular-nums text-text4">{{ number_format($t->qty) }}</span>
                    <span class="text-right tabular-nums font-medium">{{ number_format($t->revenue) }} บาท</span>
                    <span class="text-right tabular-nums text-accent font-medium">{{ number_format($t->profit) }} บาท</span>
                </div>
            @empty
                <div class="px-4.5 py-9 text-center text-sm text-muted2">ยังไม่มีการเบิกออกในเดือน{{ $selectedMonthLabel }}</div>
            @endforelse
        </div>

        {{-- Top customers --}}
        <div class="bg-surface border border-border rounded-[15px] shadow-sm">
            <div class="px-4.5 py-3.5 border-b border-line text-sm font-semibold">คู่ค้า/ลูกค้าขาประจำ · {{ $selectedMonthLabel }}</div>
            @forelse ($topCustomers as $i => $c)
                <div class="grid grid-cols-[1fr_.6fr_1fr] gap-3 px-4.5 py-3.5 border-b border-hairline2 last:border-0 items-center text-[13px]">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-[23px] h-[23px] shrink-0 rounded-lg bg-hairline text-muted text-[11.5px] font-semibold flex items-center justify-center tabular-nums">{{ $i + 1 }}</span>
                        <span class="font-medium truncate">{{ $c->party }}</span>
                    </div>
                    <span class="text-right tabular-nums text-text4">{{ number_format($c->doc_count) }} เอกสาร</span>
                    <span class="text-right tabular-nums font-medium">{{ number_format($c->revenue) }} บาท</span>
                </div>
            @empty
                <div class="px-4.5 py-9 text-center text-sm text-muted2">ยังไม่มีข้อมูลคู่ค้าในเดือน{{ $selectedMonthLabel }}</div>
            @endforelse
        </div>
    </div>
</div>
