<?php

namespace App\Livewire\Movements;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\StockMovementLine;
use App\Models\Unit;
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

    public string $typeTab = 'all';

    public string $view = 'table';

    public array $columnFilters = [];

    public int $tablePerPage = 20;

    public int $cardPerPage = 12;

    // Create form
    public bool $showForm = false;

    public bool $showSaveConfirm = false;

    public array $form = ['type' => 'in', 'date' => '', 'party' => '', 'note' => ''];

    public array $formLines = [];

    /** index ใน $formLines ที่กำลังแก้ไขอยู่ (ยังไม่ได้บันทึกเอกสาร) — null คือกำลังเพิ่มรายการใหม่ ไม่ใช่แก้ไขของเดิม */
    public ?int $editingLineIndex = null;

    public string $lineProductId = '';

    public string $lineVariantId = '';

    public string $lineCategoryId = '';

    public string $lineQty = '';

    public string $lineUnitId = '';

    public string $lineUnitPrice = '';

    /** True when recording an "in" line for a product that has never had a real cost recorded (cost = 0). */
    public bool $lineCostMissing = false;

    public ?string $formError = null;

    // Document view
    public ?int $docMovementId = null;

    // Delete confirm
    public ?int $confirmDeleteLineId = null;

    public ?int $confirmDeleteMovementId = null;

    public ?string $deleteError = null;

    // Edit line
    public ?int $editingLineId = null;

    public array $editForm = ['qty' => '', 'unit_price' => '', 'date' => '', 'party' => '', 'note' => ''];

    public ?string $editFormError = null;

    // Excel import
    public bool $showImportModal = false;

    public $importFile = null;

    public ?array $importGroups = null;

    public array $importErrors = [];

    public ?string $importFileError = null;

    public ?int $highlightDocId = null;

    /** 'Y-m', or null for "ทั้งหมด" (no month scoping — the list's original unbounded view). */
    public ?string $selectedMonth = null;

    /** 'all' | 'range' | 'month' | 'year' — which range the KPIs and document list are scoped to. */
    public string $period = 'month';

    public int $selectedYear;

    // Custom date range (period === 'range') — a single day is just start === end.
    public string $rangeStart;

    public string $rangeEnd;

    public function mount(): void
    {
        $this->form['date'] = now()->toDateString();
        $this->selectedMonth = now()->format('Y-m');
        $this->selectedYear = now()->year;
        $this->rangeStart = now()->toDateString();
        $this->rangeEnd = now()->toDateString();

        $highlightId = request()->query('doc');
        if ($highlightId) {
            $target = StockMovement::find($highlightId);
            if ($target) {
                $this->highlightDocId = $target->id;

                // The list is now scoped to the selected month — jump to whichever month
                // the highlighted document actually falls in, or it'd never show up.
                $this->selectedMonth = $target->date->format('Y-m');

                // Make sure the highlighted document's lines are within the currently loaded
                // page, since the base list only ever grows via "load more" (infinite scroll).
                $minLineId = StockMovementLine::where('stock_movement_id', $this->highlightDocId)->min('id');
                if ($minLineId) {
                    $rank = StockMovementLine::where('id', '>=', $minLineId)
                        ->whereHas('stockMovement', fn ($q) => $q->whereBetween('date', [
                            $target->date->copy()->startOfMonth(), $target->date->copy()->endOfMonth(),
                        ]))
                        ->count();
                    $this->tablePerPage = max($this->tablePerPage, $rank);
                    $this->cardPerPage = max($this->cardPerPage, $rank);
                }
            }
        }

        // Deep link from the dashboard's "+ รับเข้า" / "+ เบิกออก" quick-action buttons —
        // opens straight into a blank create-document form of the requested type.
        $newType = request()->query('new');
        if (in_array($newType, ['in', 'out'], true) && auth()->user()->can('stock_movements')) {
            $this->openCreate();
            $this->setFormType($newType);
            $this->js('window.history.replaceState(null, "", window.location.pathname)');
        }

        // Deep link from the "ใกล้หมด / ต้องสั่งซื้อ" page's "สร้างใบรับเข้าตามจำนวนแนะนำ" button:
        // opens the create-document form pre-filled with this product and its suggested quantity.
        $quickInId = request()->query('quickIn');
        if ($quickInId && auth()->user()->can('stock_movements')) {
            $product = Product::find($quickInId);
            if ($product) {
                $this->form = ['type' => 'in', 'date' => now()->toDateString(), 'party' => '', 'note' => ''];
                $this->formLines = [];
                $this->formError = null;
                $this->showForm = true;

                $this->lineProductId = (string) $product->id;
                $this->lineCategoryId = (string) $product->category_id;
                $this->lineUnitId = (string) $product->unit_id;
                $this->lineUnitPrice = (string) $product->cost;

                $qty = request()->query('qty');
                $this->lineQty = is_numeric($qty) && (int) $qty > 0 ? (string) (int) $qty : '1';

                // Strip quickIn/qty from the URL now that they've been applied, so
                // refreshing the page doesn't keep reopening this form forever.
                $this->js('window.history.replaceState(null, "", window.location.pathname)');
            }
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function setTypeTab(string $tab): void
    {
        $this->typeTab = $tab;
        $this->resetPage();
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function selectMonth(string $month): void
    {
        $this->selectedMonth = $month;
        $this->period = 'month';
        $this->resetPage();
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function selectRange(string $start, string $end): void
    {
        // Normalize order defensively — the calendar UI already sorts them before calling
        // this, but a server-side call shouldn't trust that.
        $this->rangeStart = min($start, $end);
        $this->rangeEnd = max($start, $end);
        $this->period = 'range';
        $this->resetPage();
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function setPeriod(string $period): void
    {
        $this->period = in_array($period, ['range', 'month', 'year'], true) ? $period : 'month';
        $this->resetPage();
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function selectYear(int $year): void
    {
        $this->selectedYear = $year;
        $this->period = 'year';
        $this->resetPage();
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function previousYear(): void
    {
        $this->selectYear($this->selectedYear - 1);
    }

    public function nextYear(): void
    {
        $this->selectYear($this->selectedYear + 1);
    }

    public function setAllTime(): void
    {
        $this->selectedMonth = null;
        $this->period = 'all';
        $this->resetPage();
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function loadMore(): void
    {
        if ($this->view === 'card') {
            $this->cardPerPage += 12;
        } else {
            $this->tablePerPage += 20;
        }
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
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function clearColumnFilter(string $column): void
    {
        unset($this->columnFilters[$column]);
        $this->resetPage();
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    /**
     * ช่วงวันที่ที่ใช้กรองเอกสารทั้งหน้าตอนนี้ — null คือ "ทั้งหมด" (ไม่กรองเลย)
     *
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}|null
     */
    protected function activeDateRange(): ?array
    {
        if ($this->period === 'all') {
            return null;
        }

        if ($this->period === 'range') {
            return [Carbon::parse($this->rangeStart)->startOfDay(), Carbon::parse($this->rangeEnd)->endOfDay()];
        }

        if ($this->period === 'year') {
            $yearStart = Carbon::create($this->selectedYear, 1, 1)->startOfDay();

            return [$yearStart, (clone $yearStart)->endOfYear()];
        }

        if ($this->selectedMonth === null) {
            return null;
        }

        $start = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();

        return [$start, (clone $start)->endOfMonth()];
    }

    /** ช่วงก่อนหน้าที่มีความยาวเท่ากัน สำหรับเทียบเปอร์เซ็นต์เปลี่ยนแปลง — null ถ้าตอนนี้เป็น "ทั้งหมด" (ไม่มีช่วงให้เทียบ) */
    protected function previousActiveDateRange(): ?array
    {
        if ($this->period === 'range') {
            $start = Carbon::parse($this->rangeStart)->startOfDay();
            $lengthDays = (int) round($start->diffInDays(Carbon::parse($this->rangeEnd)->startOfDay())) + 1;

            $prevEnd = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($lengthDays - 1)->startOfDay();

            return [$prevStart, $prevEnd];
        }

        if ($this->period === 'year') {
            $prevStart = Carbon::create($this->selectedYear - 1, 1, 1)->startOfDay();

            return [$prevStart, (clone $prevStart)->endOfYear()];
        }

        if ($this->period === 'all' || $this->selectedMonth === null) {
            return null;
        }

        $start = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth()->subMonthNoOverflow();

        return [$start, (clone $start)->endOfMonth()];
    }

    protected function columnOptions(string $column): array
    {
        // Scoped to the active tab and month, same as baseQuery() — otherwise the checkbox
        // list offers doc numbers/products/etc. from OUTSIDE the current filter (the other
        // tab, or a different month), which can never match a row in the currently-filtered table.
        $monthRange = $this->activeDateRange();
        $scopedMovements = fn () => StockMovement::query()
            ->when($this->typeTab !== 'all', fn ($q) => $q->where('type', $this->typeTab))
            ->when($monthRange, fn ($q) => $q->whereBetween('date', $monthRange));
        $scopedLines = fn () => StockMovementLine::query()
            ->whereHas('stockMovement', fn ($q) => $q
                ->when($this->typeTab !== 'all', fn ($q2) => $q2->where('type', $this->typeTab))
                ->when($monthRange, fn ($q2) => $q2->whereBetween('date', $monthRange)));

        return match ($column) {
            'docNo' => $scopedMovements()->orderBy('doc_no')->pluck('doc_no')->unique()
                ->map(fn ($v) => ['value' => (string) $v, 'label' => $v])->values()->all(),
            'date' => $scopedMovements()->orderBy('date')->pluck('date')->unique()
                ->map(fn ($v) => ['value' => $v->toDateString(), 'label' => $v->format('d/m/Y')])->values()->all(),
            'productName' => $scopedLines()->distinct()->orderBy('product_name')->pluck('product_name')
                ->map(fn ($v) => ['value' => (string) $v, 'label' => $v])->all(),
            'category' => $scopedLines()->distinct()->orderBy('category_name')->pluck('category_name')
                ->map(fn ($v) => ['value' => (string) $v, 'label' => $v])->all(),
            'qty' => $this->numericOptions($scopedLines()->distinct()->pluck('qty'), 0),
            'unitPrice' => $this->numericOptions($scopedLines()->distinct()->pluck('unit_price')),
            'amount' => $this->numericOptions($scopedLines()->distinct()->pluck('line_total')),
            'user' => \App\Models\User::orderBy('name')->get(['id', 'name'])
                ->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->all(),
            'note' => $scopedMovements()->whereNotNull('note')->where('note', '!=', '')
                ->distinct()->orderBy('note')->pluck('note')
                ->map(fn ($v) => ['value' => (string) $v, 'label' => $v])->all(),
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
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function setView(string $view): void
    {
        $this->view = $view;
        $this->tablePerPage = 20;
        $this->cardPerPage = 12;
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        $this->form = ['type' => 'in', 'date' => now()->toDateString(), 'party' => '', 'note' => ''];
        $this->formLines = [];
        $this->editingLineIndex = null;
        $this->resetLineEntry();
        $this->formError = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingLineIndex = null;
    }

    public function setFormType(string $type): void
    {
        $this->form['type'] = $type;
        $this->formLines = [];
        $this->editingLineIndex = null;
        $this->resetLineEntry();
    }

    protected function resetLineEntry(): void
    {
        $this->lineProductId = '';
        $this->lineVariantId = '';
        $this->lineCategoryId = '';
        $this->lineQty = '';
        $this->lineUnitId = '';
        $this->lineUnitPrice = '';
        $this->lineCostMissing = false;
    }

    public function updatedLineProductId(): void
    {
        $this->lineVariantId = '';
        $this->lineCostMissing = false;

        if ($this->lineProductId === '') {
            return;
        }

        $product = Product::with('variants')->find($this->lineProductId);

        if ($product) {
            $this->lineCategoryId = (string) $product->category_id;
            $this->lineUnitId = (string) $product->unit_id;

            $firstVariant = $product->variants->first();
            if ($firstVariant) {
                $this->lineVariantId = (string) $firstVariant->id;
                $this->applyVariantPrice($product, $firstVariant);
            } else {
                $this->lineUnitPrice = (string) ($this->form['type'] === 'in' ? $product->cost : $product->price);
            }

            $this->lineCostMissing = $this->form['type'] === 'in' && (float) $product->cost <= 0;
        }
    }

    public function updatedLineVariantId(): void
    {
        if ($this->lineProductId === '') {
            return;
        }

        $product = Product::find($this->lineProductId);
        if (! $product) {
            return;
        }

        $this->lineCostMissing = $this->form['type'] === 'in' && (float) $product->cost <= 0;

        if ($this->lineVariantId === '') {
            $this->lineUnitPrice = (string) ($this->form['type'] === 'in' ? $product->cost : $product->price);

            return;
        }

        $variant = ProductVariant::where('product_id', $product->id)->find($this->lineVariantId);
        if ($variant) {
            $this->applyVariantPrice($product, $variant);
        }
    }

    /**
     * "in" documents receive base-unit stock, so the line's unit price defaults to what
     * that many base units cost (product cost × the variant's conversion factor) — e.g.
     * buying a 20kg bag defaults to 20 × cost-per-kg. "out" documents sell the variant
     * directly at its own listed price.
     */
    protected function applyVariantPrice(Product $product, ProductVariant $variant): void
    {
        $this->lineUnitPrice = $this->form['type'] === 'in'
            ? (string) round((float) $product->cost * (float) $variant->unit_qty, 2)
            : (string) $variant->price;
    }

    public function lineTotal(): float
    {
        return (float) ($this->lineQty ?: 0) * (float) ($this->lineUnitPrice ?: 0);
    }

    public function addLine(): void
    {
        if ($this->lineProductId === '' || (float) $this->lineQty <= 0) {
            $this->formError = 'เลือกสินค้าและกรอกจำนวนก่อนเพิ่มรายการ';

            return;
        }

        $product = Product::find($this->lineProductId);
        if (! $product) {
            $this->formError = 'ไม่พบสินค้าที่เลือก อาจถูกลบไปแล้ว กรุณาเลือกใหม่';

            return;
        }

        $category = Category::find($this->lineCategoryId);
        $unit = Unit::find($this->lineUnitId);

        $variant = null;
        if ($this->lineVariantId !== '') {
            $variant = ProductVariant::where('product_id', $product->id)->find($this->lineVariantId);
            if (! $variant) {
                $this->formError = 'ขนาดที่เลือกไม่ถูกต้อง อาจถูกลบไปแล้ว กรุณาเลือกใหม่';

                return;
            }
        }

        $qty = (int) $this->lineQty;
        $unitPrice = (float) $this->lineUnitPrice;

        // "in" documents are the moment the real purchase cost is known — if the price
        // entered implies a different base-unit cost than what's on the product record,
        // offer to update it (checked by default; the pending-line row lets it be unchecked).
        $impliedCost = null;
        $updateCost = false;
        if ($this->form['type'] === 'in') {
            $unitQty = $variant ? (float) $variant->unit_qty : 1.0;
            if ($unitQty > 0) {
                $impliedCost = round($unitPrice / $unitQty, 2);
                $updateCost = abs($impliedCost - (float) $product->cost) >= 0.01;
            }
        }

        $line = [
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'name' => $product->name,
            'variant_label' => $variant?->label,
            'category_name' => $category?->name,
            'unit' => $variant?->label ?? $unit?->name,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => $qty * $unitPrice,
            'current_cost' => (float) $product->cost,
            'implied_cost' => $impliedCost,
            'update_cost' => $updateCost,
        ];

        if ($this->editingLineIndex !== null && array_key_exists($this->editingLineIndex, $this->formLines)) {
            $this->formLines[$this->editingLineIndex] = $line;
            $this->editingLineIndex = null;
        } else {
            $this->formLines[] = $line;
        }

        $this->resetLineEntry();
        $this->formError = null;
    }

    /** โหลดรายการที่เพิ่มไว้แล้ว (ในเอกสารที่ยังไม่บันทึก) กลับเข้าช่องกรอก เพื่อแก้ไขก่อนกดบันทึกจริง */
    public function editLine(int $index): void
    {
        if (! array_key_exists($index, $this->formLines)) {
            return;
        }

        $line = $this->formLines[$index];

        $this->lineProductId = (string) $line['product_id'];
        $this->lineVariantId = $line['product_variant_id'] ? (string) $line['product_variant_id'] : '';
        $this->lineQty = (string) $line['qty'];
        $this->lineUnitPrice = (string) $line['unit_price'];

        $product = Product::find($line['product_id']);
        $this->lineCategoryId = $product?->category_id ? (string) $product->category_id : '';
        $this->lineUnitId = $product?->unit_id ? (string) $product->unit_id : '';
        $this->lineCostMissing = $this->form['type'] === 'in' && (float) ($product?->cost ?? 0) <= 0;

        $this->editingLineIndex = $index;
        $this->formError = null;
    }

    /** ยกเลิกการแก้ไขรายการ กลับไปเป็นโหมด "เพิ่มรายการใหม่" โดยไม่แตะรายการเดิมที่เพิ่งกดแก้ไข */
    public function cancelEditLine(): void
    {
        $this->editingLineIndex = null;
        $this->resetLineEntry();
        $this->formError = null;
    }

    public function removeLine(int $index): void
    {
        if ($this->editingLineIndex === $index) {
            $this->cancelEditLine();
        } elseif ($this->editingLineIndex !== null && $this->editingLineIndex > $index) {
            // ลบรายการก่อนหน้ารายการที่กำลังแก้ไขอยู่ — array_values() ด้านล่างจะเลื่อน index
            // ทุกตัวหลังจากนี้ขึ้นมา 1 ตำแหน่ง ต้องขยับตัวชี้ตามไปด้วย ไม่งั้นจะไปชี้ผิดรายการ
            $this->editingLineIndex--;
        }

        unset($this->formLines[$index]);
        $this->formLines = array_values($this->formLines);
    }

    public function formTotal(): float
    {
        return collect($this->formLines)->sum('line_total');
    }

    /**
     * กดปุ่ม "บันทึก" ครั้งแรก — ตรวจข้อมูลเบื้องต้นเหมือน save() เป๊ะ แต่ยังไม่บันทึกจริง
     * แค่เปิด popup ให้ยืนยันอีกที กันกดพลาด/กดซ้ำโดยไม่ตั้งใจ
     */
    public function askSaveConfirm(): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        // A line still sitting in the entry fields but not yet added counts too.
        if ($this->lineProductId !== '' && (float) $this->lineQty > 0) {
            $this->addLine();
        }

        if (empty($this->formLines)) {
            $this->formError = 'เพิ่มอย่างน้อย 1 รายการก่อนบันทึก';

            return;
        }

        if ($this->form['date'] === '') {
            $this->formError = 'เลือกวันที่';

            return;
        }

        $this->formError = null;
        $this->showSaveConfirm = true;
    }

    public function cancelSaveConfirm(): void
    {
        $this->showSaveConfirm = false;
    }

    public function save(StockService $stock): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        // ปิด popup ยืนยันเสมอไม่ว่าผลจะสำเร็จหรือ error — ถ้า error จะได้เห็นข้อความ error
        // บนฟอร์มด้านหลังที่ popup นี้บังอยู่
        $this->showSaveConfirm = false;

        if (empty($this->formLines)) {
            $this->formError = 'เพิ่มอย่างน้อย 1 รายการก่อนบันทึก';

            return;
        }

        if ($this->form['date'] === '') {
            $this->formError = 'เลือกวันที่';

            return;
        }

        try {
            $stock->createMovement(
                [
                    'type' => $this->form['type'],
                    'date' => $this->form['date'],
                    'party' => $this->form['party'] ?: null,
                    'note' => $this->form['note'] ?: null,
                ],
                collect($this->formLines)->map(fn ($l) => [
                    'product_id' => $l['product_id'],
                    'product_variant_id' => $l['product_variant_id'] ?? null,
                    'qty' => $l['qty'],
                    'unit_price' => $l['unit_price'],
                    'category_name' => $l['category_name'] ?? null,
                    'unit' => $l['unit'] ?? null,
                ])->all(),
                auth()->user()
            );
        } catch (\RuntimeException $e) {
            $this->formError = $e->getMessage();

            return;
        }

        foreach ($this->formLines as $l) {
            if (! empty($l['update_cost']) && $l['implied_cost'] !== null) {
                Product::whereKey($l['product_id'])->update(['cost' => $l['implied_cost']]);
            }
        }

        $this->showForm = false;
        $this->editingLineIndex = null;
    }

    /**
     * Pre-fills the create form from a past document (same type/party/lines, today's
     * date) so a recurring order doesn't need retyping — the user still reviews and
     * saves it as a brand new document. Lines whose product was since deleted are
     * dropped, since there's nothing left to receive/issue against.
     */
    public function duplicateDocument(int $movementId): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        $movement = StockMovement::with('lines.variant')->find($movementId);
        if (! $movement) {
            return;
        }

        $skipped = 0;
        $this->form = [
            'type' => $movement->type,
            'date' => now()->toDateString(),
            'party' => $movement->party ?? '',
            'note' => '',
        ];
        $this->formLines = $movement->lines
            ->filter(function ($l) use (&$skipped) {
                if (! $l->product_id) {
                    $skipped++;

                    return false;
                }

                return true;
            })
            ->map(fn ($l) => [
                'product_id' => $l->product_id,
                'product_variant_id' => $l->product_variant_id,
                'name' => $l->product_name,
                'variant_label' => $l->variant?->label,
                'category_name' => $l->category_name,
                'unit' => $l->unit,
                'qty' => $l->qty,
                'unit_price' => (float) $l->unit_price,
                'line_total' => $l->qty * (float) $l->unit_price,
                'current_cost' => null,
                'implied_cost' => null,
                'update_cost' => false,
            ])->values()->all();

        $this->resetLineEntry();
        $this->formError = $skipped > 0
            ? "ข้าม {$skipped} รายการเพราะสินค้าถูกลบไปแล้ว กรุณาตรวจสอบรายการก่อนบันทึก"
            : null;
        $this->docMovementId = null;
        $this->showForm = true;
    }

    public function openDocument(int $movementId): void
    {
        $this->docMovementId = $movementId;
    }

    public function closeDocument(): void
    {
        $this->docMovementId = null;
    }

    public function askDeleteLine(int $lineId): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        $this->confirmDeleteLineId = $lineId;
        $this->deleteError = null;
    }

    public function cancelDeleteLine(): void
    {
        $this->confirmDeleteLineId = null;
        $this->deleteError = null;
    }

    public function deleteLine(StockService $stock): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        if ($this->confirmDeleteLineId) {
            $line = StockMovementLine::find($this->confirmDeleteLineId);
            if ($line) {
                try {
                    $stock->deleteLine($line);
                } catch (\RuntimeException $e) {
                    $this->deleteError = $e->getMessage();

                    return;
                }
            }
        }

        $this->confirmDeleteLineId = null;
        $this->deleteError = null;
        $this->docMovementId = null;
    }

    public function openEditLine(int $lineId): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        $line = StockMovementLine::with('stockMovement')->find($lineId);
        if (! $line) {
            return;
        }

        $this->editingLineId = $line->id;
        $this->editForm = [
            'qty' => (string) $line->qty,
            'unit_price' => (string) $line->unit_price,
            'date' => $line->stockMovement->date->toDateString(),
            'party' => $line->stockMovement->party ?? '',
            'note' => $line->stockMovement->note ?? '',
        ];
        $this->editFormError = null;
    }

    public function closeEditLine(): void
    {
        $this->editingLineId = null;
        $this->editFormError = null;
    }

    public function saveEditLine(StockService $stock): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        if (! $this->editingLineId) {
            return;
        }

        $line = StockMovementLine::with('stockMovement')->find($this->editingLineId);
        if (! $line) {
            $this->editingLineId = null;

            return;
        }

        if (! is_numeric($this->editForm['qty']) || (int) $this->editForm['qty'] <= 0) {
            $this->editFormError = 'กรอกจำนวนให้ถูกต้อง';

            return;
        }

        if (! is_numeric($this->editForm['unit_price']) || (float) $this->editForm['unit_price'] < 0) {
            $this->editFormError = 'กรอกราคาต่อหน่วยให้ถูกต้อง';

            return;
        }

        if ($this->editForm['date'] === '') {
            $this->editFormError = 'เลือกวันที่';

            return;
        }

        try {
            $stock->updateLine($line, [
                'qty' => (int) $this->editForm['qty'],
                'unit_price' => (float) $this->editForm['unit_price'],
            ]);
        } catch (\RuntimeException $e) {
            $this->editFormError = $e->getMessage();

            return;
        }

        $line->stockMovement->update([
            'date' => $this->editForm['date'],
            'party' => $this->editForm['party'] !== '' ? $this->editForm['party'] : null,
            'note' => $this->editForm['note'] !== '' ? $this->editForm['note'] : null,
        ]);

        $this->editingLineId = null;
        $this->editFormError = null;
    }

    public function askDeleteMovement(int $movementId): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        $this->confirmDeleteMovementId = $movementId;
        $this->deleteError = null;
    }

    public function cancelDeleteMovement(): void
    {
        $this->confirmDeleteMovementId = null;
        $this->deleteError = null;
    }

    public function deleteMovement(StockService $stock): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        if ($this->confirmDeleteMovementId) {
            $movement = StockMovement::find($this->confirmDeleteMovementId);
            if ($movement) {
                try {
                    $stock->deleteMovement($movement);
                } catch (\RuntimeException $e) {
                    $this->deleteError = $e->getMessage();

                    return;
                }
            }
        }

        $this->confirmDeleteMovementId = null;
        $this->deleteError = null;
        $this->docMovementId = null;
    }

    protected function baseQuery()
    {
        $monthRange = $this->activeDateRange();

        $query = StockMovementLine::query()
            ->with(['stockMovement.user'])
            ->whereHas('stockMovement', fn ($q) => $q
                ->when($this->typeTab !== 'all', fn ($q2) => $q2->where('type', $this->typeTab))
                ->when($monthRange, fn ($q2) => $q2->whereBetween('date', $monthRange)))
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('product_name', 'like', "%{$this->search}%")
                ->orWhereHas('stockMovement', fn ($q3) => $q3->where('doc_no', 'like', "%{$this->search}%")->orWhere('party', 'like', "%{$this->search}%"))));

        foreach ($this->columnFilters as $column => $values) {
            match ($column) {
                'docNo' => $query->whereHas('stockMovement', fn ($q) => $q->whereIn('doc_no', $values)),
                'date' => $query->whereHas('stockMovement', fn ($q) => $q->whereIn('date', $values)),
                'productName' => $query->whereIn('product_name', $values),
                'category' => $query->whereIn('category_name', $values),
                'qty' => $query->whereIn('qty', $values),
                'unitPrice' => $query->whereIn('unit_price', $values),
                'amount' => $query->whereIn('line_total', $values),
                'user' => $query->whereHas('stockMovement', fn ($q) => $q->whereIn('user_id', $values)),
                'note' => $query->whereHas('stockMovement', fn ($q) => $q->whereIn('note', $values)),
                default => null,
            };
        }

        return $query;
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()->can('view_reports') || auth()->user()->can('stock_movements'), 403);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MovementLinesExport($this->baseQuery()->latest('id')),
            'รับเข้า-เบิกออก-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf()
    {
        abort_unless(auth()->user()->can('view_reports') || auth()->user()->can('stock_movements'), 403);

        \App\Support\PdfFonts::registerThai();

        $lines = $this->baseQuery()->latest('id')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.movements-pdf', [
            'lines' => $lines,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'รับเข้า-เบิกออก-'.now()->format('Ymd-His').'.pdf'
        );
    }

    public function openImportModal(): void
    {
        // นำเข้าจาก Excel จำกัดให้เฉพาะเจ้าของร้านเท่านั้น (เข้มกว่าสิทธิ์ stock_movements ปกติ)
        abort_unless(auth()->user()->isOwner(), 403);

        $this->importFile = null;
        $this->importGroups = null;
        $this->importErrors = [];
        $this->importFileError = null;
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importGroups = null;
        $this->importErrors = [];
        $this->importFileError = null;
    }

    public function updatedImportFile(): void
    {
        $this->importGroups = null;
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

        $groups = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNo = $i + 2; // +1 for 0-index, +1 for the heading row
            $docLabel = trim((string) ($row[0] ?? ''));
            $dateRaw = $row[1] ?? null;
            $typeRaw = $row[2] ?? null;
            $sku = trim((string) ($row[3] ?? ''));
            $qtyRaw = $row[5] ?? null;
            $priceRaw = $row[6] ?? null;
            $party = trim((string) ($row[7] ?? ''));
            $note = trim((string) ($row[8] ?? ''));

            if ($docLabel === '' && $sku === '' && ($qtyRaw === null || $qtyRaw === '')) {
                continue; // fully blank row
            }

            $date = $this->parseImportDate($dateRaw);
            $type = $this->parseImportType($typeRaw);
            $product = $sku !== '' ? Product::where('sku', $sku)->first() : null;
            $qty = is_numeric($qtyRaw) ? (float) $qtyRaw : null;

            if ($date === null) {
                $errors[] = "แถวที่ {$rowNo}: วันที่ไม่ถูกต้อง";

                continue;
            }
            if ($type === null) {
                $errors[] = "แถวที่ {$rowNo}: ประเภทต้องเป็น \"รับเข้า\" หรือ \"เบิกออก\"";

                continue;
            }
            if (! $product) {
                $errors[] = "แถวที่ {$rowNo}: ไม่พบสินค้ารหัส SKU \"{$sku}\"";

                continue;
            }
            if ($qty === null || $qty <= 0) {
                $errors[] = "แถวที่ {$rowNo}: จำนวนไม่ถูกต้อง";

                continue;
            }

            $unitPrice = is_numeric($priceRaw) ? (float) $priceRaw : (float) ($type === 'in' ? $product->cost : $product->price);

            $key = $docLabel.'|'.$date.'|'.$type.'|'.$party;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'label' => $docLabel !== '' ? $docLabel : '(ไม่ระบุเลขที่)',
                    'type' => $type,
                    'date' => $date,
                    'party' => $party !== '' ? $party : null,
                    'note' => $note !== '' ? $note : null,
                    'lines' => [],
                ];
            } elseif ($note !== '' && $groups[$key]['note'] === null) {
                $groups[$key]['note'] = $note;
            }

            $groups[$key]['lines'][] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'qty' => (int) $qty,
                'unit_price' => $unitPrice,
                'category_name' => $product->category?->name,
                'unit' => $product->unit?->name,
                'line_total' => (int) $qty * $unitPrice,
            ];
        }

        $this->importErrors = $errors;
        $this->importGroups = array_values($groups);
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

    protected function parseImportType($value): ?string
    {
        $v = trim((string) $value);

        return match (true) {
            str_contains($v, 'รับ') => 'in',
            str_contains($v, 'เบิก') => 'out',
            strtolower($v) === 'in' => 'in',
            strtolower($v) === 'out' => 'out',
            default => null,
        };
    }

    public function confirmImport(StockService $stock): void
    {
        abort_unless(auth()->user()->isOwner(), 403);

        if (empty($this->importGroups)) {
            return;
        }

        foreach ($this->importGroups as $group) {
            try {
                $stock->createMovement(
                    [
                        'type' => $group['type'],
                        'date' => $group['date'],
                        'party' => $group['party'],
                        'note' => $group['note'],
                    ],
                    collect($group['lines'])->map(fn ($l) => [
                        'product_id' => $l['product_id'],
                        'qty' => $l['qty'],
                        'unit_price' => $l['unit_price'],
                        'category_name' => $l['category_name'],
                        'unit' => $l['unit'],
                    ])->all(),
                    auth()->user()
                );
            } catch (\RuntimeException $e) {
                $this->importFileError = "เอกสาร \"{$group['label']}\": {$e->getMessage()} — เอกสารก่อนหน้านี้ถูกนำเข้าไปแล้ว กรุณาตรวจสอบและนำเข้าส่วนที่เหลือแยกต่างหาก";

                return;
            }
        }

        $this->closeImportModal();
    }

    /** "+12%" / "-8%" vs the previous period, or null when there's nothing to compare against. */
    protected function pctDelta(float $current, float $previous, string $suffix = 'จากเดือนก่อน'): ?array
    {
        if ($previous == 0.0) {
            return null;
        }

        $pct = round(($current - $previous) / abs($previous) * 100);

        return ['text' => ($pct >= 0 ? '+' : '').$pct.'% '.$suffix, 'tone' => $pct >= 0 ? 'accent' : 'danger'];
    }

    public function render()
    {
        $lines = $this->view === 'card'
            ? $this->baseQuery()->latest('id')->paginate($this->cardPerPage, ['*'], 'page', 1)
            : $this->baseQuery()->latest('id')->paginate($this->tablePerPage, ['*'], 'page', 1);

        $monthRange = $this->activeDateRange();
        if ($monthRange) {
            [$periodStart, $periodEnd] = $monthRange;
            $periodLabel = match ($this->period) {
                'range' => $periodStart->isSameDay($periodEnd)
                    ? $periodStart->translatedFormat('d F Y')
                    : ($periodStart->year === $periodEnd->year
                        ? $periodStart->translatedFormat('j M').' - '.$periodEnd->translatedFormat('j M').' '.($periodEnd->year + 543)
                        : $periodStart->translatedFormat('j M Y').' - '.$periodEnd->translatedFormat('j M Y')),
                'year' => 'ปี '.($this->selectedYear + 543),
                default => $periodStart->translatedFormat('F Y'),
            };
            $deltaSuffix = match ($this->period) {
                'range' => 'จากช่วงก่อนหน้า',
                'year' => 'จากปีก่อน',
                default => 'จากเดือนก่อน',
            };

            $inValue = (float) StockMovement::where('type', 'in')->whereBetween('date', [$periodStart, $periodEnd])->sum('total');
            $outValue = (float) StockMovement::where('type', 'out')->whereBetween('date', [$periodStart, $periodEnd])->sum('total');
            $docCount = StockMovement::whereBetween('date', [$periodStart, $periodEnd])->count();

            [$prevIn, $prevOut, $prevDocCount] = [0.0, 0.0, 0];
            if ($prevRange = $this->previousActiveDateRange()) {
                [$prevStart, $prevEnd] = $prevRange;
                $prevIn = (float) StockMovement::where('type', 'in')->whereBetween('date', [$prevStart, $prevEnd])->sum('total');
                $prevOut = (float) StockMovement::where('type', 'out')->whereBetween('date', [$prevStart, $prevEnd])->sum('total');
                $prevDocCount = StockMovement::whereBetween('date', [$prevStart, $prevEnd])->count();
            }

            $kpis = [
                ['label' => 'มูลค่ารับเข้า · '.$periodLabel, 'value' => number_format($inValue).' บาท', 'delta' => $this->pctDelta($inValue, $prevIn, $deltaSuffix)],
                ['label' => 'มูลค่าเบิกออก · '.$periodLabel, 'value' => number_format($outValue).' บาท', 'delta' => $this->pctDelta($outValue, $prevOut, $deltaSuffix)],
                ['label' => 'จำนวนเอกสาร · '.$periodLabel, 'value' => number_format($docCount), 'delta' => $this->pctDelta($docCount, $prevDocCount, $deltaSuffix)],
            ];
        } else {
            $kpis = [
                ['label' => 'มูลค่ารับเข้าทั้งหมด', 'value' => number_format((float) StockMovement::where('type', 'in')->sum('total')).' บาท', 'delta' => null],
                ['label' => 'มูลค่าเบิกออกทั้งหมด', 'value' => number_format((float) StockMovement::where('type', 'out')->sum('total')).' บาท', 'delta' => null],
                ['label' => 'จำนวนเอกสารทั้งหมด', 'value' => number_format(StockMovement::count()), 'delta' => null],
            ];
        }

        $products = Product::with('unit:id,name')->orderBy('name')->get(['id', 'name', 'sku', 'cost', 'price', 'stock', 'unit_id']);
        $isIn = $this->form['type'] === 'in';

        $lineProduct = null;
        $lineVariantOptions = [];
        if ($this->lineProductId !== '') {
            $lineProduct = $products->firstWhere('id', (int) $this->lineProductId);
            $lineVariantOptions = ProductVariant::where('product_id', $this->lineProductId)->orderBy('sort_order')->get()
                ->map(function ($v) use ($isIn, $lineProduct) {
                    // Mirrors applyVariantPrice(): "in" shows what that many base units
                    // cost, "out" shows the variant's own listed selling price.
                    $refPrice = $isIn ? (float) ($lineProduct?->cost ?? 0) * (float) $v->unit_qty : (float) $v->price;

                    return ['value' => (string) $v->id, 'label' => "{$v->label} — ".number_format($refPrice, 2).' บาท'];
                })->values()->all();
        }

        $isCurrentPeriod = match ($this->period) {
            'range' => $this->rangeStart === now()->toDateString() && $this->rangeEnd === now()->toDateString(),
            'year' => $this->selectedYear === now()->year,
            'all' => true,
            default => $this->selectedMonth === now()->format('Y-m'),
        };

        return view('livewire.movements.index', [
            'lines' => $lines,
            'hasMoreRows' => $lines->hasMorePages(),
            'kpis' => $kpis,
            'isCurrentPeriod' => $isCurrentPeriod,
            'products' => $products,
            'productOptions' => $products->map(fn ($p) => ['value' => (string) $p->id, 'label' => $p->name])->values()->all(),
            'lineProduct' => $lineProduct,
            'lineVariantOptions' => $lineVariantOptions,
            'lineCategoryOptions' => Category::orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->values()->all(),
            'lineUnitOptions' => Unit::orderBy('name')->get(['id', 'name'])
                ->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->values()->all(),
            'docMovement' => $this->docMovementId ? StockMovement::with(['lines.product', 'lines.variant', 'user'])->find($this->docMovementId) : null,
            'deleteLine' => $this->confirmDeleteLineId ? StockMovementLine::with('stockMovement')->find($this->confirmDeleteLineId) : null,
            'editingLine' => $this->editingLineId ? StockMovementLine::with('stockMovement')->find($this->editingLineId) : null,
            'deleteMovement' => $this->confirmDeleteMovementId ? StockMovement::find($this->confirmDeleteMovementId) : null,
            'columnOptionsMap' => [
                'docNo' => $this->columnOptions('docNo'),
                'date' => $this->columnOptions('date'),
                'productName' => $this->columnOptions('productName'),
                'category' => $this->columnOptions('category'),
                'qty' => $this->columnOptions('qty'),
                'unitPrice' => $this->columnOptions('unitPrice'),
                'amount' => $this->columnOptions('amount'),
                'user' => $this->columnOptions('user'),
                'note' => $this->columnOptions('note'),
            ],
        ])->layout('components.layouts.app', ['title' => 'รับเข้า–เบิกออก', 'subtitle' => 'เอกสารทั้งหมด พิมพ์ใบเสร็จและใบส่งของได้']);
    }
}
