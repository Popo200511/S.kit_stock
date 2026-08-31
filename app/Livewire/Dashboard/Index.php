<?php

namespace App\Livewire\Dashboard;

use App\Models\OnlineOrder;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockMovementLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public string $chartView = 'bar';

    public string $period = 'recent';

    public int $selectedYear;

    public ?string $selectedMonth = null;

    public ?string $selectedType = null;

    public ?string $selectedCategory = null;

    public function mount(): void
    {
        $this->selectedYear = (int) now()->year;
    }

    public function setChartView(string $view): void
    {
        $this->chartView = $view;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->clearMonthSelection();
    }

    public function prevYear(): void
    {
        $this->selectedYear--;
        $this->clearMonthSelection();
    }

    public function nextYear(): void
    {
        $this->selectedYear++;
        $this->clearMonthSelection();
    }

    public function selectBar(string $month, string $type): void
    {
        $this->selectedCategory = null;

        if ($this->selectedMonth === $month && $this->selectedType === $type) {
            $this->selectedMonth = null;
            $this->selectedType = null;
        } else {
            $this->selectedMonth = $month;
            $this->selectedType = $type;
        }
    }

    public function selectCategory(string $category): void
    {
        $this->selectedMonth = null;
        $this->selectedType = null;
        $this->selectedCategory = $this->selectedCategory === $category ? null : $category;
    }

    public function clearMonthSelection(): void
    {
        $this->selectedMonth = null;
        $this->selectedType = null;
        $this->selectedCategory = null;
    }

    /**
     * Either the trailing 7 months (oldest first) or all 12 months of $selectedYear
     * (Jan–Dec), in/out totals per month from real stock_movements.
     */
    protected function series(): array
    {
        $months = $this->period === 'year'
            ? collect(range(1, 12))->map(fn ($m) => Carbon::create($this->selectedYear, $m, 1))
            : collect(range(6, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));

        $totals = StockMovement::query()
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym, type, SUM(total) as total")
            ->whereBetween('date', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->groupBy('ym', 'type')
            ->get()
            ->groupBy('ym');

        return $months->map(function ($month) use ($totals) {
            $key = $month->format('Y-m');
            $rows = $totals->get($key, collect());

            return [
                'key' => $key,
                'label' => $month->translatedFormat('M'),
                'in' => (float) $rows->firstWhere('type', 'in')?->total,
                'out' => (float) $rows->firstWhere('type', 'out')?->total,
            ];
        })->all();
    }

    public function render()
    {
        $series = $this->series();
        $maxSeries = max(1, collect($series)->flatMap(fn ($s) => [$s['in'], $s['out']])->max());

        $docsThisWeek = StockMovement::where('date', '>=', now()->subDays(7))->count();
        $docsPrevWeek = StockMovement::whereBetween('date', [now()->subDays(14), now()->subDays(7)])->count();

        $curMonthStart = now()->startOfMonth();
        $curMonthEnd = now()->endOfMonth();
        $prevMonthStart = $curMonthStart->copy()->subMonth();
        $prevMonthEnd = $prevMonthStart->copy()->endOfMonth();
        $profitThisMonth = $this->profitFor($curMonthStart, $curMonthEnd);
        $profitPrevMonth = $this->profitFor($prevMonthStart, $prevMonthEnd);

        $kpis = [
            [
                'label' => 'มูลค่าสต็อกทั้งหมด',
                'value' => number_format(Product::sum(DB::raw('stock * cost'))).' บาท',
                'tone' => 'accent',
                'd' => 'M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8',
                'delta' => null,
            ],
            [
                'label' => 'จำนวนสินค้า (SKU)',
                'value' => number_format(Product::count()),
                'tone' => 'accent',
                'd' => 'M3 7a2 2 0 0 1 2-2h4l2 3h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
                'delta' => null,
            ],
            [
                'label' => 'สินค้าใกล้หมด / หมด',
                'value' => number_format(Product::whereColumn('stock', '<=', 'reorder_point')->count()),
                'tone' => 'warn',
                'd' => 'M12 3l9 16H3zM12 9v5M12 17h.01',
                'delta' => null,
            ],
            [
                'label' => 'เอกสาร 7 วันล่าสุด',
                'value' => number_format($docsThisWeek),
                'tone' => 'accent',
                'd' => 'M7 3v18M7 3 3 7M7 3l4 4M17 21V3M17 21l4-4M17 21l-4-4',
                'delta' => $this->pctDelta($docsThisWeek, $docsPrevWeek, 'จาก 7 วันก่อนหน้า'),
            ],
            [
                'label' => 'กำไรขั้นต้นเดือนนี้',
                'value' => number_format($profitThisMonth).' บาท',
                'tone' => 'accent',
                'd' => 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
                'delta' => $this->pctDelta($profitThisMonth, $profitPrevMonth, 'จากเดือนก่อน'),
            ],
        ];

        $lowTop = Product::whereColumn('stock', '<=', 'reorder_point')
            ->orderByRaw('stock / GREATEST(reorder_point, 1) asc')
            ->limit(4)
            ->get()
            ->map(fn ($p) => [
                'product' => $p,
                'fillPct' => $p->reorder_point > 0 ? min(100, round($p->stock / $p->reorder_point * 100)) : 0,
            ]);

        if ($this->selectedMonth) {
            $monthStart = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $recent = StockMovement::with('lines')
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->when($this->selectedType, fn ($q) => $q->where('type', $this->selectedType))
                ->latest('date')->latest('id')->get();
            $typeLabel = match ($this->selectedType) {
                'in' => ' · รับเข้า',
                'out' => ' · เบิกออก',
                default => '',
            };
            $recentLabel = 'เอกสารเดือน'.$monthStart->translatedFormat('F Y').$typeLabel;
        } elseif ($this->selectedCategory) {
            [$rangeStart, $rangeEnd] = $this->periodRange();
            $recent = StockMovement::with('lines')
                ->where('type', 'out')
                ->whereBetween('date', [$rangeStart, $rangeEnd])
                ->whereHas('lines', fn ($q) => $q->where('category_name', $this->selectedCategory))
                ->latest('date')->latest('id')->get();
            $recentLabel = 'เอกสารเบิกออก · '.$this->selectedCategory;
        } else {
            $recent = StockMovement::with('lines')->latest('date')->latest('id')->limit(5)->get();
            $recentLabel = 'เอกสารล่าสุด';
        }

        $pie = $this->pieByCategory();

        return view('livewire.dashboard.index', [
            'kpis' => $kpis,
            'series' => $series,
            'maxSeries' => $maxSeries,
            'lowTop' => $lowTop,
            'recent' => $recent,
            'recentLabel' => $recentLabel,
            'pie' => $pie,
            'onlineNeedsAction' => auth()->user()->can('online_sales') ? $this->onlineNeedsAction() : null,
            'deadStock' => $this->deadStock(),
        ])->layout('components.layouts.app', ['title' => 'ภาพรวมร้าน', 'subtitle' => 'สรุปสต็อก ยอดขาย และงานที่ต้องทำวันนี้']);
    }

    protected array $palette = ['#0b7a55', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#ec4899'];

    /** Matches whichever window series() is currently drawing from — the trailing 7 months, or the whole selected year. */
    protected function periodRange(): array
    {
        if ($this->period === 'year') {
            return [Carbon::create($this->selectedYear, 1, 1), Carbon::create($this->selectedYear, 12, 31)->endOfDay()];
        }

        return [now()->startOfMonth()->subMonths(6), now()->endOfDay()];
    }

    /** เบิกออกรวมแยกตามหมวด, matching the current period selection — powers the pie chart view. */
    protected function pieByCategory(): array
    {
        [$rangeStart, $rangeEnd] = $this->periodRange();

        $rows = \App\Models\StockMovementLine::query()
            ->join('stock_movements', 'stock_movements.id', '=', 'stock_movement_lines.stock_movement_id')
            ->where('stock_movements.type', 'out')
            ->whereBetween('stock_movements.date', [$rangeStart, $rangeEnd])
            ->selectRaw('stock_movement_lines.category_name as category, SUM(stock_movement_lines.line_total) as total')
            ->groupBy('stock_movement_lines.category_name')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');
        $cumulative = 0;
        $stops = [];
        $legend = [];

        foreach ($rows as $i => $row) {
            $color = $this->palette[$i % count($this->palette)];
            $label = $row->category ?: 'ไม่ระบุหมวด';
            $isDimmed = $this->selectedCategory && $this->selectedCategory !== $label;
            $sliceColor = $isDimmed ? "color-mix(in oklab, {$color} 25%, var(--surface))" : $color;
            $pct = $grandTotal > 0 ? $row->total / $grandTotal * 100 : 0;
            $from = $cumulative;
            $cumulative += $pct;
            $stops[] = "{$sliceColor} {$from}% {$cumulative}%";
            $legend[] = [
                'label' => $label,
                'color' => $color,
                'value' => $row->total,
                'pct' => round($pct),
            ];
        }

        return [
            'gradient' => $stops ? 'conic-gradient('.implode(', ', $stops).')' : 'conic-gradient(var(--hairline) 0% 100%)',
            'total' => $grandTotal,
            'legend' => $legend,
        ];
    }

    /** กำไรขั้นต้นของเอกสารเบิกออกในช่วงเวลาที่กำหนด (ราคาขาย - ต้นทุนปัจจุบันของสินค้า) เหมือนหน้ารายงาน */
    protected function profitFor(Carbon $start, Carbon $end): float
    {
        return (float) StockMovementLine::query()
            ->join('stock_movements', 'stock_movements.id', '=', 'stock_movement_lines.stock_movement_id')
            ->leftJoin('products', 'products.id', '=', 'stock_movement_lines.product_id')
            ->where('stock_movements.type', 'out')
            ->whereBetween('stock_movements.date', [$start, $end])
            ->sum(DB::raw('stock_movement_lines.qty * (stock_movement_lines.unit_price - products.cost * stock_movement_lines.unit_qty)'));
    }

    /** "+12% จากเดือนก่อน" / "-8% จาก 7 วันก่อนหน้า" หรือ null ถ้าไม่มีค่าก่อนหน้าให้เทียบ */
    protected function pctDelta(float $current, float $previous, string $suffix): ?array
    {
        if ($previous == 0.0) {
            return null;
        }

        $pct = round(($current - $previous) / abs($previous) * 100);

        return ['text' => ($pct >= 0 ? '+' : '').$pct.'% '.$suffix, 'tone' => $pct >= 0 ? 'accent' : 'danger'];
    }

    /**
     * ออเดอร์ออนไลน์ที่ยังต้องจัดการ: ยังไม่จับคู่สินค้า, จับคู่แล้วแต่ยังไม่ตัดสต็อก (ทั้งที่สถานะ
     * "สำเร็จ"), หรือสถานะล้มเหลว/ตีคืน — ตรงกับตรรกะ "ต้องดำเนินการ" ที่ใช้อยู่แล้วในหน้าขายออนไลน์
     */
    protected function onlineNeedsAction(): int
    {
        return OnlineOrder::query()
            ->where(function ($q) {
                $q->whereNull('product_id')
                    ->orWhere(fn ($q2) => $q2->where('status', 'success')->whereNull('stock_movement_id'))
                    ->orWhereIn('status', ['failed', 'returned']);
            })
            ->count();
    }

    /**
     * สินค้าที่ยังมีสต็อกอยู่ (เงินทุนจม) แต่ไม่มีการรับเข้า/เบิกออกเลยในช่วง $days วันล่าสุด —
     * เรียงตามมูลค่าที่จมอยู่มากที่สุดก่อน จะได้เห็นตัวที่ควรจัดการก่อน
     */
    protected function deadStock(int $days = 60, int $limit = 4)
    {
        $cutoff = now()->subDays($days);

        return Product::query()
            ->where('stock', '>', 0)
            ->whereDoesntHave('stockMovementLines', fn ($q) => $q->whereHas(
                'stockMovement', fn ($q2) => $q2->where('date', '>=', $cutoff)
            ))
            ->orderByRaw('stock * cost desc')
            ->limit($limit)
            ->get();
    }
}
