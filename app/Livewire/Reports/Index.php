<?php

namespace App\Livewire\Reports;

use App\Models\Category;
use App\Models\StockMovement;
use App\Models\StockMovementLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public string $selectedMonth;

    /** 'monthly' (7 เดือนล่าสุด) | 'daily' (รายวันของเดือนที่เลือก) | 'yearly' (12 เดือนของปีที่เลือก) */
    public string $chartMode = 'monthly';

    public int $selectedYear;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
        $this->selectedYear = (int) now()->year;
    }

    public function selectMonth(string $month): void
    {
        $this->selectedMonth = $month;
    }

    public function setChartMode(string $mode): void
    {
        $this->chartMode = $mode;
    }

    public function prevYear(): void
    {
        $this->selectedYear--;
    }

    public function nextYear(): void
    {
        $this->selectedYear++;
    }

    protected function monthlyOutSeries(): array
    {
        $months = collect(range(6, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));

        $totals = StockMovement::query()
            ->where('type', 'out')
            ->where('date', '>=', $months->first())
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym, SUM(total) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $months->map(fn ($m) => [
            'key' => $m->format('Y-m'),
            'label' => $m->translatedFormat('M'),
            'out' => (float) ($totals[$m->format('Y-m')] ?? 0),
        ])->all();
    }

    /** ยอดเบิกออกแต่ละเดือน (ม.ค.–ธ.ค.) ของปี $year */
    protected function yearlyOutSeries(int $year): array
    {
        $months = collect(range(1, 12))->map(fn ($m) => Carbon::create($year, $m, 1));

        $totals = StockMovement::query()
            ->where('type', 'out')
            ->whereBetween('date', [$months->first(), $months->last()->copy()->endOfMonth()])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym, SUM(total) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $months->map(fn ($m) => [
            'key' => $m->format('Y-m'),
            'label' => $m->translatedFormat('M'),
            'out' => (float) ($totals[$m->format('Y-m')] ?? 0),
        ])->all();
    }

    /** ยอดเบิกออกแต่ละวันของเดือน $monthStart — วันที่ยังไม่ถึง (ของเดือนปัจจุบัน) ก็ยังโชว์เป็นแท่ง 0 ไว้ตามปกติ */
    protected function dailyOutSeries(Carbon $monthStart, Carbon $monthEnd): array
    {
        $totals = StockMovement::query()
            ->where('type', 'out')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m-%d') as ymd, SUM(total) as total")
            ->groupBy('ymd')
            ->pluck('total', 'ymd');

        $days = collect();
        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
            $days->push($d->copy());
        }

        return $days->map(fn ($d) => [
            'key' => $d->format('Y-m-d'),
            'label' => $d->format('j'),
            'out' => (float) ($totals[$d->format('Y-m-d')] ?? 0),
        ])->all();
    }

    /**
     * @return array{sales: float, profit: float, docCount: int, margin: float}
     */
    protected function monthMetrics(Carbon $start, Carbon $end): array
    {
        $lines = StockMovementLine::query()
            ->join('stock_movements', 'stock_movements.id', '=', 'stock_movement_lines.stock_movement_id')
            ->where('stock_movements.type', 'out')
            ->whereBetween('stock_movements.date', [$start, $end])
            ->select('stock_movement_lines.*');

        $sales = (float) (clone $lines)->sum('line_total');
        $docCount = StockMovement::where('type', 'out')->whereBetween('date', [$start, $end])->count();

        // qty is a count of the sold variant's unit (e.g. bags), not the base unit products.cost
        // is priced per — cost must be scaled by unit_qty (1 for lines with no variant) first.
        // leftJoin (not join): a line whose product was since deleted has product_id = null
        // (nullOnDelete) — an inner join would silently drop its revenue from every total on
        // this page instead of just leaving its profit contribution unknown. MySQL's SUM()
        // ignores the NULL that "unit_price - null_cost * unit_qty" produces for those lines,
        // so they still count everywhere else (sales, doc count, top-products list).
        $profit = (float) (clone $lines)
            ->leftJoin('products', 'products.id', '=', 'stock_movement_lines.product_id')
            ->sum(DB::raw('stock_movement_lines.qty * (stock_movement_lines.unit_price - products.cost * stock_movement_lines.unit_qty)'));

        return [
            'sales' => $sales,
            'profit' => $profit,
            'docCount' => $docCount,
            'margin' => $sales > 0 ? $profit / $sales * 100 : 0.0,
        ];
    }

    /** "+12%" / "-8%" vs the previous month, or null when the previous month has nothing to compare against. */
    protected function pctDelta(float $current, float $previous): ?array
    {
        if ($previous == 0.0) {
            return null;
        }

        $pct = round(($current - $previous) / abs($previous) * 100);

        return ['text' => ($pct >= 0 ? '+' : '').$pct.'% จากเดือนก่อน', 'tone' => $pct >= 0 ? 'accent' : 'danger'];
    }

    public function render()
    {
        $monthStart = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $prevStart = $monthStart->copy()->subMonth();
        $prevEnd = $prevStart->copy()->endOfMonth();

        $current = $this->monthMetrics($monthStart, $monthEnd);
        $previous = $this->monthMetrics($prevStart, $prevEnd);

        $sales = $current['sales'];
        $profit = $current['profit'];
        $docCount = $current['docCount'];

        $kpis = [
            ['label' => 'ยอดเบิกออก', 'value' => number_format($sales).' บาท', 'note' => $monthStart->translatedFormat('F Y'), 'delta' => $this->pctDelta($sales, $previous['sales'])],
            ['label' => 'กำไรขั้นต้น', 'value' => number_format($profit).' บาท', 'note' => 'จากราคาต้นทุนปัจจุบัน', 'delta' => $this->pctDelta($profit, $previous['profit'])],
            ['label' => 'จำนวนเอกสารเบิกออก', 'value' => number_format($docCount), 'note' => $monthStart->translatedFormat('F Y'), 'delta' => $this->pctDelta($docCount, $previous['docCount'])],
            ['label' => 'อัตรากำไรเฉลี่ย', 'value' => round($current['margin']).'%', 'note' => 'กำไร / ยอดขาย', 'delta' => null],
        ];

        if ($previous['sales'] > 0 || $previous['profit'] > 0) {
            $pointDiff = round($current['margin'] - $previous['margin']);
            $kpis[3]['delta'] = ['text' => ($pointDiff >= 0 ? '+' : '').$pointDiff.' จุด จากเดือนก่อน', 'tone' => $pointDiff >= 0 ? 'accent' : 'danger'];
        }

        $series = $this->monthlyOutSeries();
        $maxOut = max(1, collect($series)->max('out'));

        $dailySeries = $this->dailyOutSeries($monthStart, $monthEnd);
        $maxDaily = max(1, collect($dailySeries)->max('out'));

        $yearlySeries = $this->yearlyOutSeries($this->selectedYear);
        $maxYearly = max(1, collect($yearlySeries)->max('out'));

        $catBars = Category::withCount('products')
            ->with(['products' => fn ($q) => $q->select('id', 'category_id', 'cost', 'stock')])
            ->get()
            ->map(fn ($c) => ['name' => $c->name, 'value' => $c->products->sum(fn ($p) => $p->stock * $p->cost)])
            ->filter(fn ($c) => $c['value'] > 0)
            ->sortByDesc('value')
            ->take(8)
            ->values();
        $maxCat = max(1, $catBars->max('value'));

        $topProducts = StockMovementLine::query()
            ->join('stock_movements', 'stock_movements.id', '=', 'stock_movement_lines.stock_movement_id')
            ->leftJoin('products', 'products.id', '=', 'stock_movement_lines.product_id')
            ->where('stock_movements.type', 'out')
            ->whereBetween('stock_movements.date', [$monthStart, $monthEnd])
            ->groupBy('stock_movement_lines.product_id', 'stock_movement_lines.product_name')
            ->selectRaw('stock_movement_lines.product_name, SUM(stock_movement_lines.qty) as qty, SUM(stock_movement_lines.line_total) as revenue, SUM(stock_movement_lines.qty * (stock_movement_lines.unit_price - products.cost * stock_movement_lines.unit_qty)) as profit')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $topCustomers = StockMovement::query()
            ->where('type', 'out')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->whereNotNull('party')
            ->where('party', '!=', '')
            ->groupBy('party')
            ->selectRaw('party, COUNT(*) as doc_count, SUM(total) as revenue')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return view('livewire.reports.index', [
            'kpis' => $kpis,
            'series' => $series,
            'maxOut' => $maxOut,
            'dailySeries' => $dailySeries,
            'maxDaily' => $maxDaily,
            'yearlySeries' => $yearlySeries,
            'maxYearly' => $maxYearly,
            'catBars' => $catBars,
            'maxCat' => $maxCat,
            'topProducts' => $topProducts,
            'topCustomers' => $topCustomers,
            'selectedMonthLabel' => $monthStart->translatedFormat('F Y'),
        ])->layout('components.layouts.app', ['title' => 'รายงานและกำไร', 'subtitle' => 'ยอดขาย กำไรขั้นต้น และสินค้าขายดี']);
    }
}
