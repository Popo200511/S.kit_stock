<?php

namespace App\Livewire\Online;

use App\Models\OnlineExpense;
use App\Models\OnlineOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public array $columnFilters = [];

    public int $tablePerPage = 15;

    // Create/edit form — editingOrderId null means "create"; set, means "edit that order".
    // Editing re-syncs the order's linked stock movement (if matched), since status/qty/
    // revenue changing after the fact (e.g. Shopee later marks it returned) should be
    // reflected in stock too, not just the order's own log.
    public bool $showForm = false;

    public ?int $editingOrderId = null;

    public array $form = ['date' => '', 'order_no' => '', 'item' => '', 'channel' => 'Shopee', 'revenue' => '', 'qty' => '1', 'status' => 'success'];

    public ?string $formError = null;

    // SKU match — matching (and rematching/unmatching) a *successful* order to a product
    // auto-syncs a linked "out" stock movement (see syncStockForOrder()) so the sale
    // actually leaves the system's stock, not just its own revenue log.
    public ?int $matchOrderId = null;

    public string $matchProductId = '';

    public string $matchVariantId = '';

    public string $matchQty = '1';

    public ?string $matchError = null;

    // Delete confirm
    public ?int $confirmDeleteId = null;

    // Order detail panel
    public ?int $detailOrderId = null;

    // Expense create + delete
    public bool $showExpenseForm = false;

    public array $expenseForm = ['date' => '', 'label' => '', 'amount' => ''];

    public ?int $confirmDeleteExpenseId = null;

    public function openDetail(int $id): void
    {
        $this->detailOrderId = $id;
    }

    public function closeDetail(): void
    {
        $this->detailOrderId = null;
    }

    // Excel import
    public bool $showImportModal = false;

    public $importFile = null;

    public ?array $importRows = null;

    public array $importErrors = [];

    public ?string $importFileError = null;

    public ?string $importSuccessMessage = null;

    public string $selectedMonth;

    /** 'range', 'month', or 'year' — which range the KPIs, order table, and filter options are scoped to. */
    public string $period = 'month';

    public int $selectedYear;

    // Custom date range (period === 'range') — a single day is just start === end.
    public string $rangeStart;

    public string $rangeEnd;

    public ?int $highlightOrderId = null;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
        $this->selectedYear = now()->year;
        $this->rangeStart = now()->toDateString();
        $this->rangeEnd = now()->toDateString();

        $highlightId = request()->query('order');
        $target = $highlightId ? OnlineOrder::find($highlightId) : null;
        if ($target) {
            $this->highlightOrderId = $target->id;

            // The table is now scoped to the selected month — jump to whichever month
            // the highlighted order actually falls in (switching back to month view if
            // year view was active), or it'd never show up in the list.
            $this->period = 'month';
            $this->selectedMonth = $target->date->format('Y-m');

            // The list only ever grows via "load more" (infinite scroll) — make sure the
            // highlighted order's row is within the currently loaded page.
            $rank = OnlineOrder::whereBetween('date', [$target->date->copy()->startOfMonth(), $target->date->copy()->endOfMonth()])
                ->where(fn ($q) => $q->where('date', '>', $target->date)
                    ->orWhere(fn ($q2) => $q2->where('date', $target->date)->where('id', '>=', $target->id))
                )->count();
            $this->tablePerPage = max($this->tablePerPage, $rank);
        }
    }

    public function selectMonth(string $month): void
    {
        $this->selectedMonth = $month;
        $this->resetPage();
        $this->tablePerPage = 15;
    }

    public function selectRange(string $start, string $end): void
    {
        // Normalize order defensively — the calendar UI already sorts them before calling
        // this, but a server-side call shouldn't trust that.
        $this->rangeStart = min($start, $end);
        $this->rangeEnd = max($start, $end);
        $this->resetPage();
        $this->tablePerPage = 15;
    }

    public function setPeriod(string $period): void
    {
        $this->period = in_array($period, ['range', 'month', 'year'], true) ? $period : 'month';
        $this->resetPage();
        $this->tablePerPage = 15;
    }

    public function selectYear(int $year): void
    {
        $this->selectedYear = $year;
        $this->resetPage();
        $this->tablePerPage = 15;
    }

    public function previousYear(): void
    {
        $this->selectYear($this->selectedYear - 1);
    }

    public function nextYear(): void
    {
        $this->selectYear($this->selectedYear + 1);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->tablePerPage = 15;
    }

    public function loadMore(): void
    {
        $this->tablePerPage += 15;
    }

    public function applyColumnFilter(string $column, array $values): void
    {
        $all = $this->columnOptionValues($column);
        sort($values);
        sort($all);

        if ($values === $all) {
            unset($this->columnFilters[$column]);
        } else {
            $this->columnFilters[$column] = $values;
        }

        $this->resetPage();
        $this->tablePerPage = 15;
    }

    public function clearColumnFilter(string $column): void
    {
        unset($this->columnFilters[$column]);
        $this->resetPage();
        $this->tablePerPage = 15;
    }

    protected function columnOptions(string $column): array
    {
        // Scoped to the selected month/year, same as baseQuery() — otherwise the checkbox
        // list offers dates/items/etc. from outside the current range that can't ever
        // match a row in the currently-filtered table, and "select all" no longer means "no filter".
        [$periodStart, $periodEnd] = $this->periodRange();
        $scoped = fn () => OnlineOrder::query()->whereBetween('date', [$periodStart, $periodEnd]);

        return match ($column) {
            'date' => $scoped()->orderBy('date')->pluck('date')->unique()
                ->map(fn ($v) => ['value' => $v->toDateString(), 'label' => $v->format('d/m/Y')])->values()->all(),
            'orderNo' => $scoped()->orderBy('order_no')->pluck('order_no')
                ->map(fn ($v) => ['value' => (string) $v, 'label' => $v])->all(),
            'item' => $scoped()->distinct()->orderBy('item')->pluck('item')
                ->map(fn ($v) => ['value' => (string) $v, 'label' => $v])->all(),
            'channel' => $scoped()->distinct()->orderBy('channel')->pluck('channel')
                ->map(fn ($v) => ['value' => (string) $v, 'label' => $v])->all(),
            'revenue' => $this->numericOptions($scoped()->distinct()->pluck('revenue')),
            'status' => [
                ['value' => 'success', 'label' => 'สำเร็จ'],
                ['value' => 'failed', 'label' => 'จัดส่งไม่สำเร็จ'],
                ['value' => 'returned', 'label' => 'คืนสินค้า'],
            ],
            default => [],
        };
    }

    protected function numericOptions($values, int $decimals = 2): array
    {
        return $values->filter(fn ($v) => $v !== null)->unique()->sort()->values()
            ->map(fn ($v) => ['value' => (string) $v, 'label' => number_format((float) $v, $decimals)])
            ->all();
    }

    protected function columnOptionValues(string $column): array
    {
        return array_column($this->columnOptions($column), 'value');
    }

    public function hasChipFilters(): bool
    {
        return ! empty($this->columnFilters);
    }

    public function clearFilters(): void
    {
        $this->reset(['search']);
        $this->columnFilters = [];
        $this->resetPage();
        $this->tablePerPage = 15;
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $this->editingOrderId = null;
        $this->form = ['date' => now()->toDateString(), 'order_no' => '', 'item' => '', 'channel' => 'Shopee', 'revenue' => '', 'qty' => '1', 'status' => 'success'];
        $this->formError = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $order = OnlineOrder::findOrFail($id);
        $this->editingOrderId = $order->id;
        $this->form = [
            'date' => $order->date->toDateString(),
            'order_no' => $order->order_no,
            'item' => $order->item,
            'channel' => $order->channel,
            'revenue' => (string) $order->revenue,
            'qty' => (string) $order->qty,
            'status' => $order->status,
        ];
        $this->formError = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingOrderId = null;
    }

    public function save(StockService $stock): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $validated = \Illuminate\Support\Facades\Validator::make($this->form, [
            'date' => 'required|date',
            'order_no' => 'required|string|max:100',
            'item' => 'required|string|max:255',
            'channel' => 'required|string|max:50',
            'revenue' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:1',
            'status' => 'required|in:success,failed,returned',
        ])->validate();

        $exists = OnlineOrder::where('order_no', $validated['order_no'])->where('channel', $validated['channel'])
            ->when($this->editingOrderId, fn ($q) => $q->whereKeyNot($this->editingOrderId))
            ->exists();
        if ($exists) {
            $this->formError = 'เลขที่คำสั่งซื้อนี้มีอยู่แล้วในช่องทางนี้';

            return;
        }

        if ($this->editingOrderId) {
            $order = OnlineOrder::findOrFail($this->editingOrderId);
            $order->update($validated);

            // status/qty/revenue may have just changed — if the order is matched to a
            // product, its stock deduction needs to reflect that (or be reversed, if it's
            // no longer a completed sale).
            $this->syncStockForOrder($order, $stock);
            if ($this->matchError !== null) {
                // Reuse the same message here — the edit itself still saved either way,
                // only the stock side of it needs another look.
                $this->formError = $this->matchError;
                $this->matchError = null;

                return;
            }
        } else {
            $validated['source'] = 'manual';
            $order = OnlineOrder::create($validated);
        }

        if (in_array($order->status, ['failed', 'returned'], true)) {
            $this->notifyOrderIssues([$order]);
        }

        $this->showForm = false;
        $this->editingOrderId = null;
    }

    /** @param array<OnlineOrder> $orders */
    protected function notifyOrderIssues(array $orders): void
    {
        $recipients = \App\Models\User::where('active', true)->whereKeyNot(auth()->id())->get()
            ->reject(fn ($u) => $u->mutesNotificationType(\App\Enums\NotificationType::OnlineOrderIssue));
        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($orders as $order) {
            \Illuminate\Support\Facades\Notification::send($recipients, new \App\Notifications\OnlineOrderIssue($order));
        }
    }

    public function openImportModal(): void
    {
        // นำเข้าจาก Excel จำกัดให้เฉพาะเจ้าของร้านเท่านั้น (เข้มกว่าสิทธิ์ online_sales ปกติ)
        abort_unless(auth()->user()->isOwner(), 403);

        $this->importFile = null;
        $this->importRows = null;
        $this->importErrors = [];
        $this->importFileError = null;
        $this->importSuccessMessage = null;
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importRows = null;
        $this->importErrors = [];
        $this->importFileError = null;
    }

    public function updatedImportFile(): void
    {
        $this->importRows = null;
        $this->importErrors = [];
        $this->importFileError = null;

        if (! $this->importFile) {
            return;
        }

        try {
            $this->validate(['importFile' => 'file|mimes:xlsx,xls|max:5120']);
        } catch (\Illuminate\Validation\ValidationException) {
            $this->importFileError = 'ไฟล์ต้องเป็น .xlsx หรือ .xls ขนาดไม่เกิน 5MB';
            $this->importFile = null;

            return;
        }

        try {
            $rows = \Maatwebsite\Excel\Facades\Excel::toArray(new \stdClass, $this->importFile->getRealPath())[0] ?? [];
        } catch (\Throwable) {
            $this->importFileError = 'ไม่สามารถอ่านไฟล์นี้ได้ กรุณาตรวจสอบว่าเป็นไฟล์ Excel (.xlsx) ที่ถูกต้องตามรูปแบบ Template';

            return;
        }

        if (count($rows) <= 1) {
            $this->importFileError = 'ไม่พบข้อมูลในไฟล์ (ต้องมีอย่างน้อย 1 แถวข้อมูลใต้หัวตาราง)';

            return;
        }

        // Shopee's own "Export Orders" file from Seller Centre — no Partner API needed
        // to get this, so it's a much faster path than typing every order by hand.
        $isShopeeNativeExport = trim((string) ($rows[0][0] ?? '')) === 'หมายเลขคำสั่งซื้อ';

        [$valid, $errors] = $isShopeeNativeExport
            ? $this->parseShopeeNativeExport($rows)
            : $this->parseOwnTemplate($rows);

        // Duplicate order_no+channel within the file itself: keep the last occurrence, warn about the rest.
        $byKey = [];
        foreach ($valid as $row) {
            $key = $row['order_no'].'|'.$row['channel'];
            if (isset($byKey[$key])) {
                $errors[] = "เลขที่คำสั่งซื้อ \"{$row['order_no']}\" ซ้ำในไฟล์ (แถวที่ {$byKey[$key]['row']} และ {$row['row']}) — ใช้ค่าจากแถวที่ {$row['row']}";
            }
            $byKey[$key] = $row;
        }

        $this->importErrors = $errors;
        $this->importRows = array_values($byKey);
    }

    /** @return array{0: array, 1: array} [$validRows, $errors] */
    protected function parseOwnTemplate(array $rows): array
    {
        $valid = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNo = $i + 2; // +1 for 0-index, +1 for the heading row
            $dateRaw = $row[0] ?? null;
            $orderNo = trim((string) ($row[1] ?? ''));
            $item = trim((string) ($row[2] ?? ''));
            $sku = trim((string) ($row[3] ?? ''));
            $channel = trim((string) ($row[4] ?? ''));
            $revenueRaw = $row[5] ?? null;
            $qtyRaw = $row[6] ?? null;
            $statusRaw = $row[7] ?? '';

            if ($orderNo === '' && $item === '') {
                continue; // fully blank row
            }

            $date = $this->parseImportDate($dateRaw);
            if ($date === null) {
                $errors[] = "แถวที่ {$rowNo}: วันที่ไม่ถูกต้อง";

                continue;
            }
            if ($orderNo === '') {
                $errors[] = "แถวที่ {$rowNo}: ไม่มีเลขที่คำสั่งซื้อ";

                continue;
            }
            if ($item === '') {
                $errors[] = "แถวที่ {$rowNo}: ไม่มีรายการสินค้า";

                continue;
            }
            if ($revenueRaw !== null && $revenueRaw !== '' && ! is_numeric($revenueRaw)) {
                $errors[] = "แถวที่ {$rowNo}: รายรับไม่ถูกต้อง";

                continue;
            }
            if ($qtyRaw !== null && $qtyRaw !== '' && (! is_numeric($qtyRaw) || (int) $qtyRaw < 1)) {
                $errors[] = "แถวที่ {$rowNo}: จำนวนไม่ถูกต้อง";

                continue;
            }

            $status = $this->parseImportStatus($statusRaw);
            if ($status === null) {
                $errors[] = "แถวที่ {$rowNo}: สถานะต้องเป็น \"สำเร็จ\" / \"จัดส่งไม่สำเร็จ\" / \"คืนสินค้า\"";

                continue;
            }

            $channel = $channel !== '' ? $channel : 'Shopee';

            $product = null;
            if ($sku !== '') {
                $product = Product::where('sku', $sku)->first();
                if (! $product) {
                    $errors[] = "แถวที่ {$rowNo}: ไม่พบสินค้ารหัส SKU \"{$sku}\" (จะนำเข้าโดยยังไม่จับคู่สินค้า)";
                }
            }

            if (OnlineOrder::where('order_no', $orderNo)->where('channel', $channel)->exists()) {
                $errors[] = "แถวที่ {$rowNo}: เลขที่คำสั่งซื้อ \"{$orderNo}\" มีอยู่แล้วในช่องทาง {$channel}";

                continue;
            }

            $valid[] = [
                'row' => $rowNo,
                'date' => $date,
                'order_no' => $orderNo,
                'item' => $item,
                'product_id' => $product?->id,
                'product_name' => $product?->name,
                'channel' => $channel,
                'revenue' => is_numeric($revenueRaw) ? (float) $revenueRaw : 0,
                'qty' => is_numeric($qtyRaw) && (int) $qtyRaw >= 1 ? (int) $qtyRaw : 1,
                'status' => $status,
            ];
        }

        return [$valid, $errors];
    }

    /**
     * Parses Shopee Seller Centre's native "Export Orders" .xlsx — the file a seller can
     * download directly from Shopee (คำสั่งซื้อของฉัน > Export) with zero API access needed.
     * One row per line item, so a multi-item order spans several rows sharing the same
     * order number; they're grouped back into a single OnlineOrder per order here since
     * that's what the rest of this page (and the DB's order_no+channel unique key) expects.
     *
     * @return array{0: array, 1: array} [$validRows, $errors]
     */
    /**
     * The columns the shop highlighted yellow in Shopee's own export file — everything
     * beyond what already has a dedicated field, kept as raw text/label pairs and shown
     * only in the order detail panel rather than as ~40 extra table columns.
     */
    protected function shopeeRawDataColumns(): array
    {
        return [
            1 => 'สถานะการสั่งซื้อ',
            3 => 'เหตุผลในการยกเลิกคำสั่งซื้อ',
            4 => 'สถานะการคืนเงินหรือคืนสินค้า',
            5 => 'ชื่อผู้ใช้ (ผู้ซื้อ)',
            7 => 'เวลาการชำระสินค้า',
            8 => 'ช่องทางการชำระเงิน',
            14 => '*หมายเลขติดตามพัสดุ',
            15 => 'วันที่คาดว่าจะทำการจัดส่งสินค้า',
            16 => 'เวลาส่งสินค้า',
            20 => 'ชื่อตัวเลือก',
            21 => 'ราคาตั้งต้น',
            22 => 'ราคาขาย',
            23 => 'จำนวน',
            24 => 'จำนวนที่ส่งคืน',
            26 => 'ส่วนลดจาก Shopee',
            27 => 'โค้ดส่วนลดชำระโดยผู้ขาย',
            28 => 'โค้ด Coins Cashback ชำระโดยผู้ขาย',
            29 => 'โค้ดส่วนลดชำระโดย Shopee (เช่น โค้ดจากโปรแกรม ร้านโค้ดคุ้ม, โค้ดส่วนลด Shopee, โค้ดส่วนลด Shopee Mall)',
            30 => 'โค้ดส่วนลด',
            31 => 'เข้าร่วมแคมเปญ bundle deal หรือไม่',
            32 => 'ส่วนลด bundle deal ชำระโดยผู้ขาย',
            33 => 'ส่วนลด bundle deal ชำระโดย Shopee',
            34 => 'ส่วนลดจากการใช้เหรียญ',
            35 => 'โปรโมชั่นช่องทางชำระเงินทั้งหมด',
            36 => 'ส่วนลดเครื่องเก่าแลกใหม่',
            37 => 'โบนัสส่วนลดเครื่องเก่าแลกใหม่',
            38 => 'ค่าคอมมิชชั่น',
            39 => 'Transaction Fee',
            40 => 'ราคาสินค้าที่ชำระโดยผู้ซื้อ (THB)',
            41 => 'ค่าจัดส่งที่ชำระโดยผู้ซื้อ',
            42 => 'ค่าจัดส่งที่ Shopee ออกให้โดยประมาณ',
            43 => 'ค่าจัดส่งสินค้าคืน',
            44 => 'ค่าบริการ',
            45 => 'จำนวนเงินทั้งหมด',
            46 => 'ค่าจัดส่งโดยประมาณ',
            47 => 'โบนัสส่วนลดเครื่องเก่าแลกใหม่จากผู้ขาย',
        ];
    }

    protected function parseShopeeNativeExport(array $rows): array
    {
        $groups = [];
        $rawColumns = $this->shopeeRawDataColumns();

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNo = $i + 2;
            $orderNo = trim((string) ($row[0] ?? ''));

            if ($orderNo === '') {
                continue; // fully blank row
            }

            if (! isset($groups[$orderNo])) {
                $raw = [];
                foreach ($rawColumns as $colIndex => $label) {
                    $value = trim((string) ($row[$colIndex] ?? ''));
                    if ($value !== '') {
                        $raw[$label] = $value;
                    }
                }

                $groups[$orderNo] = [
                    'row' => $rowNo,
                    'order_no' => $orderNo,
                    'date' => $this->parseImportDate($row[6] ?? null),
                    'status_raw' => trim((string) ($row[1] ?? '')),
                    'items' => [],
                    'revenue' => 0.0,
                    'sku_ref' => '',
                    // Summed only across line(s) sharing sku_ref below — an order can mix
                    // several different products, and only one gets matched (the first SKU
                    // seen), so its qty shouldn't be inflated by unrelated items' quantities.
                    'qty' => 0,
                    'raw' => $raw,
                ];
            }

            $itemName = trim((string) ($row[18] ?? '')) ?: '(ไม่ระบุชื่อสินค้า)';
            $variant = trim((string) ($row[20] ?? ''));
            $qty = $row[23] ?? null;
            $label = $variant !== '' ? "{$itemName} ({$variant})" : $itemName;
            if (is_numeric($qty) && (int) $qty > 1) {
                $label .= ' x'.(int) $qty;
            }
            $groups[$orderNo]['items'][] = $label;

            $net = $row[25] ?? null;
            $groups[$orderNo]['revenue'] += is_numeric($net) ? (float) $net : 0;

            $skuRef = trim((string) ($row[19] ?? ''));
            if ($groups[$orderNo]['sku_ref'] === '' && $skuRef !== '') {
                $groups[$orderNo]['sku_ref'] = $skuRef;
            }
            if ($skuRef !== '' && $skuRef === $groups[$orderNo]['sku_ref']) {
                $groups[$orderNo]['qty'] += is_numeric($qty) ? (int) $qty : 1;
            }
        }

        $valid = [];
        $errors = [];
        $skippedNotFinal = 0;

        foreach ($groups as $orderNo => $g) {
            if ($g['date'] === null) {
                $errors[] = "คำสั่งซื้อ \"{$orderNo}\": วันที่ไม่ถูกต้อง";

                continue;
            }

            $status = $this->parseShopeeNativeStatus($g['status_raw']);
            if ($status === null) {
                // Still in progress (unpaid / awaiting shipment) — not a completed sale yet,
                // so it's skipped rather than guessed at; re-export once it's settled.
                $skippedNotFinal++;

                continue;
            }

            if (OnlineOrder::where('order_no', $orderNo)->where('channel', 'Shopee')->exists()) {
                $errors[] = "คำสั่งซื้อ \"{$orderNo}\": มีอยู่แล้วในระบบ ข้ามการนำเข้า";

                continue;
            }

            $product = $g['sku_ref'] !== '' ? Product::where('sku', $g['sku_ref'])->first() : null;

            $valid[] = [
                'row' => $g['row'],
                'date' => $g['date'],
                'order_no' => $orderNo,
                'item' => implode('; ', $g['items']),
                'product_id' => $product?->id,
                'product_name' => $product?->name,
                'channel' => 'Shopee',
                'revenue' => $g['revenue'],
                'qty' => max(1, $g['qty']),
                'status' => $status,
                'shopee_order_sn' => $orderNo,
                'shopee_raw_data' => $g['raw'],
            ];
        }

        if ($skippedNotFinal > 0) {
            $errors[] = "ข้าม {$skippedNotFinal} คำสั่งซื้อที่ยังไม่เสร็จสิ้น (รอชำระเงิน/รอจัดส่ง) — นำเข้าใหม่ได้อีกครั้งเมื่อสถานะปิดแล้ว";
        }

        return [$valid, $errors];
    }

    protected function parseShopeeNativeStatus(string $raw): ?string
    {
        return match (true) {
            str_contains($raw, 'ยกเลิก') => 'failed',
            str_contains($raw, 'คืนเงิน'), str_contains($raw, 'คืนสินค้า') => 'returned',
            str_contains($raw, 'สำเร็จ'), str_contains($raw, 'ผู้ซื้อได้รับสินค้าแล้ว') => 'success',
            default => null,
        };
    }

    protected function parseImportDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        if (is_string($value) && trim($value) !== '') {
            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $format) {
                try {
                    return Carbon::createFromFormat($format, trim($value))->toDateString();
                } catch (\Throwable) {
                    continue;
                }
            }

            try {
                return Carbon::parse(trim($value))->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    protected function parseImportStatus($value): ?string
    {
        $v = trim((string) $value);

        return match (true) {
            $v === '' => 'success',
            str_contains($v, 'ไม่สำเร็จ') => 'failed',
            str_contains($v, 'คืน') => 'returned',
            str_contains($v, 'สำเร็จ') => 'success',
            strtolower($v) === 'success' => 'success',
            strtolower($v) === 'failed' => 'failed',
            strtolower($v) === 'returned' => 'returned',
            default => null,
        };
    }

    public function confirmImport(StockService $stock): void
    {
        abort_unless(auth()->user()->isOwner(), 403);

        if (empty($this->importRows)) {
            return;
        }

        $imported = 0;
        $skipped = 0;
        $problemOrders = [];
        $syncFailures = 0;

        foreach ($this->importRows as $row) {
            // Re-check here (not just at preview time) in case something else — another
            // import, or the Shopee sync job — inserted this same order in the meantime.
            if (OnlineOrder::where('order_no', $row['order_no'])->where('channel', $row['channel'])->exists()) {
                $skipped++;

                continue;
            }

            $order = OnlineOrder::create([
                'date' => $row['date'],
                'order_no' => $row['order_no'],
                'item' => $row['item'],
                'product_id' => $row['product_id'],
                'channel' => $row['channel'],
                'revenue' => $row['revenue'],
                'qty' => max(1, (int) ($row['qty'] ?? 1)),
                'status' => $row['status'],
                'shopee_order_sn' => $row['shopee_order_sn'] ?? null,
                'shopee_raw_data' => $row['shopee_raw_data'] ?? null,
                'source' => 'manual',
            ]);
            $imported++;

            if (in_array($order->status, ['failed', 'returned'], true)) {
                $problemOrders[] = $order;
            }

            // Import never picks a variant (own-template SKUs and Shopee's export both name
            // just a product) — deducts straight from base-unit stock, same as an unmatched
            // manual match would. Rematch via "จับคู่ SKU" afterward to pin a variant if needed.
            if ($order->product_id !== null && $order->status === 'success') {
                $this->syncStockForOrder($order, $stock);
                if ($this->matchError !== null) {
                    $syncFailures++;
                }
            }
        }

        if (! empty($problemOrders)) {
            $this->notifyOrderIssues($problemOrders);
        }

        $this->matchError = null;
        $this->closeImportModal();
        $message = $skipped > 0
            ? "นำเข้าสำเร็จ {$imported} รายการ (ข้าม {$skipped} รายการที่มีอยู่แล้ว)"
            : "นำเข้าสำเร็จ {$imported} รายการ";
        if ($syncFailures > 0) {
            $message .= " · ตัดสต็อกไม่สำเร็จ {$syncFailures} รายการ (คงเหลือไม่พอ) ตรวจสอบทีหลังได้ที่ปุ่มจับคู่ SKU";
        }
        $this->importSuccessMessage = $message;
    }

    public function dismissImportSuccess(): void
    {
        $this->importSuccessMessage = null;
    }

    public function askDelete(int $id): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(StockService $stock): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $order = OnlineOrder::find($this->confirmDeleteId);
        if ($order) {
            // Reverse any stock the order took out before the record itself disappears —
            // otherwise the deduction would silently stay applied forever.
            if ($order->stock_movement_id) {
                $movement = StockMovement::find($order->stock_movement_id);
                if ($movement) {
                    $stock->deleteMovement($movement);
                }
            }
            $order->delete();
        }

        $this->confirmDeleteId = null;
    }

    public function openSkuMatch(int $orderId): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $order = OnlineOrder::find($orderId);
        $this->matchOrderId = $orderId;
        $this->matchProductId = (string) ($order->product_id ?? '');
        $this->matchVariantId = (string) ($order->product_variant_id ?? '');
        $this->matchQty = (string) max(1, $order->qty);
        $this->matchError = null;
    }

    public function closeSkuMatch(): void
    {
        $this->matchOrderId = null;
    }

    public function updatedMatchProductId(): void
    {
        // Switching product invalidates whichever variant was picked for the old one.
        $this->matchVariantId = '';
    }

    public function saveSkuMatch(StockService $stock): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $order = OnlineOrder::find($this->matchOrderId);
        if (! $order) {
            $this->matchOrderId = null;

            return;
        }

        if ($this->matchProductId !== '' && (! is_numeric($this->matchQty) || (int) $this->matchQty < 1)) {
            $this->matchError = 'กรอกจำนวนให้ถูกต้อง (มากกว่า 0)';

            return;
        }

        $order->product_id = $this->matchProductId !== '' ? $this->matchProductId : null;
        $order->product_variant_id = $this->matchProductId !== '' && $this->matchVariantId !== '' ? $this->matchVariantId : null;
        $order->qty = $this->matchProductId !== '' ? (int) $this->matchQty : $order->qty;

        $this->syncStockForOrder($order, $stock);

        // Leave the modal open when the sync itself hit a snag (e.g. insufficient stock) so
        // the warning is actually seen — the match/qty were still saved either way.
        if ($this->matchError === null) {
            $this->matchOrderId = null;
        }
    }

    /**
     * Keeps an order's linked "out" stock movement (if any) in sync with its current
     * product/variant/qty and status — a *successful* order matched to a product should
     * always have exactly one movement backing it; anything else (unmatched, or the sale
     * didn't actually go through) should have none. Always deletes-then-recreates rather
     * than patching in place, so rematching/requantifying can't leave a stale deduction
     * behind. Insufficient stock doesn't block saving the match itself — it's surfaced as
     * a warning instead, since the sale already happened whether or not the system's
     * stock can currently account for it.
     */
    protected function syncStockForOrder(OnlineOrder $order, StockService $stock): void
    {
        if ($order->stock_movement_id) {
            $existing = StockMovement::find($order->stock_movement_id);
            if ($existing) {
                $stock->deleteMovement($existing);
            }
            $order->stock_movement_id = null;
        }

        $shouldSync = $order->status === 'success' && $order->product_id !== null;
        $this->matchError = null;

        if ($shouldSync) {
            $product = Product::find($order->product_id);
            $variant = $order->product_variant_id ? ProductVariant::where('product_id', $order->product_id)->find($order->product_variant_id) : null;
            $qty = max(1, (int) $order->qty);
            $unitPrice = $qty > 0 ? round((float) $order->revenue / $qty, 2) : 0;

            if ($product) {
                try {
                    $movement = $stock->createMovement(
                        [
                            'type' => 'out',
                            'date' => $order->date->toDateString(),
                            'party' => $order->channel.' · '.$order->order_no,
                            'note' => 'ตัดสต็อกอัตโนมัติจากออเดอร์ออนไลน์',
                        ],
                        [[
                            'product_id' => $product->id,
                            'product_variant_id' => $variant?->id,
                            'qty' => $qty,
                            'unit_price' => $unitPrice,
                            'category_name' => $product->category?->name,
                            'unit' => $variant?->label ?? $product->unit?->name,
                        ]],
                        auth()->user()
                    );
                    $order->stock_movement_id = $movement->id;
                } catch (\RuntimeException $e) {
                    $this->matchError = 'จับคู่สำเร็จ แต่ตัดสต็อกไม่ได้: '.$e->getMessage();
                }
            }
        }

        $order->save();
    }

    public function openExpenseForm(): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $this->expenseForm = ['date' => now()->toDateString(), 'label' => '', 'amount' => ''];
        $this->showExpenseForm = true;
    }

    public function closeExpenseForm(): void
    {
        $this->showExpenseForm = false;
    }

    public function saveExpense(): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $validated = \Illuminate\Support\Facades\Validator::make($this->expenseForm, [
            'date' => 'required|date',
            'label' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ])->validate();

        OnlineExpense::create($validated);

        $this->showExpenseForm = false;
    }

    public function askDeleteExpense(int $id): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $this->confirmDeleteExpenseId = $id;
    }

    public function cancelDeleteExpense(): void
    {
        $this->confirmDeleteExpenseId = null;
    }

    public function deleteExpense(): void
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        OnlineExpense::find($this->confirmDeleteExpenseId)?->delete();
        $this->confirmDeleteExpenseId = null;
    }

    /** @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon} */
    protected function selectedMonthRange(): array
    {
        $monthStart = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();

        return [$monthStart, (clone $monthStart)->endOfMonth()];
    }

    /** The date range everything on this page is scoped to — the custom range, selected month, or whole year. */
    protected function periodRange(): array
    {
        if ($this->period === 'range') {
            return [Carbon::parse($this->rangeStart)->startOfDay(), Carbon::parse($this->rangeEnd)->endOfDay()];
        }

        if ($this->period === 'year') {
            $yearStart = Carbon::create($this->selectedYear, 1, 1)->startOfDay();

            return [$yearStart, (clone $yearStart)->endOfYear()];
        }

        return $this->selectedMonthRange();
    }

    /**
     * The same-length range immediately before, for the "vs previous period" deltas — e.g.
     * an 8-day custom range compares against the 8 days right before it.
     */
    protected function previousPeriodRange(): array
    {
        if ($this->period === 'range') {
            $start = Carbon::parse($this->rangeStart)->startOfDay();
            // Both ends at startOfDay (not endOfDay) so diffInDays lands on a clean whole
            // number — comparing 00:00:00 to 23:59:59 comes out a hair short of a full day
            // (e.g. 7.999996 instead of 8), and that fraction throws the subtraction below
            // off by a day.
            $lengthDays = (int) round($start->diffInDays(Carbon::parse($this->rangeEnd)->startOfDay())) + 1;

            $prevEnd = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($lengthDays - 1)->startOfDay();

            return [$prevStart, $prevEnd];
        }

        if ($this->period === 'year') {
            $prevStart = Carbon::create($this->selectedYear - 1, 1, 1)->startOfDay();

            return [$prevStart, (clone $prevStart)->endOfYear()];
        }

        $prevStart = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth()->subMonthNoOverflow();

        return [$prevStart, (clone $prevStart)->endOfMonth()];
    }

    /**
     * "+12%" / "-8%" vs the previous period, or null when there's nothing to compare against.
     * $lowerIsBetter flips the accent/danger tone for cost-type metrics (e.g. expenses),
     * where an increase is the bad direction, not the good one.
     */
    protected function pctDelta(float $current, float $previous, bool $lowerIsBetter = false): ?array
    {
        if ($previous == 0.0) {
            return null;
        }

        $pct = round(($current - $previous) / abs($previous) * 100);
        $isGood = $lowerIsBetter ? $pct <= 0 : $pct >= 0;
        $compareLabel = match ($this->period) {
            'range' => 'ช่วงก่อนหน้า',
            'year' => 'ปีก่อน',
            default => 'เดือนก่อน',
        };

        return ['text' => ($pct >= 0 ? '+' : '').$pct.'% จาก'.$compareLabel, 'tone' => $isGood ? 'accent' : 'danger'];
    }

    protected function baseQuery()
    {
        [$periodStart, $periodEnd] = $this->periodRange();

        $query = OnlineOrder::query()
            ->with('product')
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('order_no', 'like', "%{$this->search}%")
                ->orWhere('item', 'like', "%{$this->search}%")));

        foreach ($this->columnFilters as $column => $values) {
            match ($column) {
                'date' => $query->whereIn('date', $values),
                'orderNo' => $query->whereIn('order_no', $values),
                'item' => $query->whereIn('item', $values),
                'channel' => $query->whereIn('channel', $values),
                'revenue' => $query->whereIn('revenue', $values),
                'status' => $query->whereIn('status', $values),
                default => null,
            };
        }

        return $query;
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()->can('view_reports') || auth()->user()->can('online_sales'), 403);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\OnlineOrdersExport($this->baseQuery()->latest('date')),
            'ขายออนไลน์-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf()
    {
        abort_unless(auth()->user()->can('view_reports') || auth()->user()->can('online_sales'), 403);

        \App\Support\PdfFonts::registerThai();

        $orders = $this->baseQuery()->latest('date')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.online-pdf', [
            'orders' => $orders,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'ขายออนไลน์-'.now()->format('Ymd-His').'.pdf'
        );
    }

    public function render()
    {
        [$periodStart, $periodEnd] = $this->periodRange();
        [$prevStart, $prevEnd] = $this->previousPeriodRange();

        $periodLabel = match ($this->period) {
            'range' => $periodStart->isSameDay($periodEnd)
                ? $periodStart->translatedFormat('d F Y')
                : ($periodStart->year === $periodEnd->year
                    ? $periodStart->translatedFormat('j M').' - '.$periodEnd->translatedFormat('j M').' '.($periodEnd->year + 543)
                    : $periodStart->translatedFormat('j M Y').' - '.$periodEnd->translatedFormat('j M Y')),
            'year' => 'ปี '.($this->selectedYear + 543),
            default => $periodStart->translatedFormat('F Y'),
        };
        $isCurrentPeriod = match ($this->period) {
            'range' => $this->rangeStart === now()->toDateString() && $this->rangeEnd === now()->toDateString(),
            'year' => $this->selectedYear === now()->year,
            default => $this->selectedMonth === now()->format('Y-m'),
        };

        $revenue = (float) OnlineOrder::where('status', 'success')->whereBetween('date', [$periodStart, $periodEnd])->sum('revenue');
        $orderCount = OnlineOrder::whereBetween('date', [$periodStart, $periodEnd])->count();
        $expense = (float) OnlineExpense::whereBetween('date', [$periodStart, $periodEnd])->sum('amount');

        $prevRevenue = (float) OnlineOrder::where('status', 'success')->whereBetween('date', [$prevStart, $prevEnd])->sum('revenue');
        $prevOrderCount = OnlineOrder::whereBetween('date', [$prevStart, $prevEnd])->count();
        $prevExpense = (float) OnlineExpense::whereBetween('date', [$prevStart, $prevEnd])->sum('amount');

        $kpis = [
            ['label' => 'ยอดขายออนไลน์ · '.$periodLabel, 'value' => number_format($revenue).' บาท', 'delta' => $this->pctDelta($revenue, $prevRevenue)],
            ['label' => 'จำนวนออเดอร์ · '.$periodLabel, 'value' => number_format($orderCount), 'delta' => $this->pctDelta($orderCount, $prevOrderCount)],
            ['label' => 'ยังไม่จับคู่ SKU (ทั้งหมด)', 'value' => number_format(OnlineOrder::whereNull('product_id')->count()), 'delta' => null],
            ['label' => 'รายจ่าย · '.$periodLabel, 'value' => number_format($expense).' บาท', 'delta' => $this->pctDelta($expense, $prevExpense, lowerIsBetter: true)],
        ];

        $orders = $this->baseQuery()->latest('date')->latest('id')->paginate($this->tablePerPage, ['*'], 'page', 1);

        return view('livewire.online.index', [
            'kpis' => $kpis,
            'monthLabel' => $periodLabel,
            'isCurrentMonth' => $isCurrentPeriod,
            'highlightOrderId' => $this->highlightOrderId,
            'orders' => $orders,
            'hasMoreRows' => $orders->hasMorePages(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'failedOrders' => OnlineOrder::whereIn('status', ['failed', 'returned'])->latest('date')->limit(5)->get(),
            'expenses' => OnlineExpense::latest('date')->limit(5)->get(),
            'expenseTotal' => OnlineExpense::sum('amount'),
            'matchOrder' => $this->matchOrderId ? OnlineOrder::find($this->matchOrderId) : null,
            'matchProduct' => $this->matchProductId !== '' ? Product::with(['variants', 'unit'])->find($this->matchProductId) : null,
            'deleteOrder' => $this->confirmDeleteId ? OnlineOrder::find($this->confirmDeleteId) : null,
            'detailOrder' => $this->detailOrderId ? OnlineOrder::with(['product', 'variant', 'stockMovement'])->find($this->detailOrderId) : null,
            'deleteExpense' => $this->confirmDeleteExpenseId ? OnlineExpense::find($this->confirmDeleteExpenseId) : null,
            'columnOptionsMap' => [
                'date' => $this->columnOptions('date'),
                'orderNo' => $this->columnOptions('orderNo'),
                'item' => $this->columnOptions('item'),
                'channel' => $this->columnOptions('channel'),
                'revenue' => $this->columnOptions('revenue'),
                'status' => $this->columnOptions('status'),
            ],
        ])->layout('components.layouts.app', ['title' => 'ขายออนไลน์ (Shopee)', 'subtitle' => 'รายรับ-รายจ่ายจากออเดอร์ออนไลน์']);
    }
}
