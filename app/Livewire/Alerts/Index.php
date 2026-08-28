<?php

namespace App\Livewire\Alerts;

use App\Models\Product;
use App\Services\StockService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public array $productIds = [];

    public array $frozenStatus = [];

    public array $suggestQtyInput = [];

    public array $reorderPointInput = [];

    public ?int $highlightProductId = null;

    // Quick-receipt modal (single product from a card, or every low-stock product at once)
    public bool $showReceiptModal = false;

    public array $receiptForm = ['date' => '', 'party' => ''];

    public array $receiptLines = [];

    public ?string $receiptError = null;

    public function mount(): void
    {
        $this->refreshLowStockList();

        $highlightId = request()->query('product');
        if ($highlightId && in_array((int) $highlightId, $this->productIds, true)) {
            $this->highlightProductId = (int) $highlightId;
        }
    }

    /** Re-snapshots the low-stock list — called on load, and again after a receipt is saved so restocked items drop off. */
    protected function refreshLowStockList(): void
    {
        $products = Product::whereColumn('stock', '<=', 'reorder_point')
            ->orderByRaw('stock / GREATEST(reorder_point, 1) asc')
            ->get();
        $this->productIds = $products->pluck('id')->all();

        // Snapshot each product's severity at page-load, so adjusting the reorder point
        // afterwards can't flip a card's badge/color or position out from under the list it's sitting in.
        $this->frozenStatus = $products->mapWithKeys(fn (Product $p) => [$p->id => $p->stock_status])->all();
    }

    public function incrementReorder(int $productId): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $product = Product::find($productId);
        if ($product) {
            $product->increment('reorder_point');
            $this->syncAfterReorderPointChange($product);
        }
    }

    public function decrementReorder(int $productId): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $product = Product::find($productId);
        if ($product && $product->reorder_point > 0) {
            $product->decrement('reorder_point');
            $this->syncAfterReorderPointChange($product);
        }
    }

    /** Keep the reorder-point input and the (non-overridden) suggested-qty input in sync with the saved value. */
    protected function syncAfterReorderPointChange(Product $product): void
    {
        $this->reorderPointInput[$product->id] = $product->reorder_point_display;

        if ($product->suggested_reorder_qty === null) {
            $this->suggestQtyInput[$product->id] = $this->autoSuggestQty($product);
        }
    }

    public function saveReorderPoint(int $productId): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $value = $this->reorderPointInput[$productId] ?? null;
        if (is_numeric($value) && (int) $value >= 0) {
            $product = Product::find($productId);
            if ($product) {
                $product->update(['reorder_point' => (int) $value]);
                $this->syncAfterReorderPointChange($product);
            }
        }
    }

    protected function autoSuggestQty(Product $product): int
    {
        return max(0, $product->reorder_point * 2 - $product->stock);
    }

    public function saveSuggestQty(int $productId): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $value = $this->suggestQtyInput[$productId] ?? null;
        if (is_numeric($value) && (int) $value >= 0) {
            Product::whereKey($productId)->update(['suggested_reorder_qty' => (int) $value]);
        }
    }

    public function resetSuggestQty(int $productId): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $product = Product::find($productId);
        if ($product) {
            $product->update(['suggested_reorder_qty' => null]);
            $this->suggestQtyInput[$productId] = $this->autoSuggestQty($product);
        }
    }

    /**
     * Opens the quick-receipt modal, pre-filled from either one product (called from a
     * card's "สร้างใบรับเข้าตามจำนวนแนะนำ" button) or, when $productId is null, every
     * low-stock product at once (the "สร้างใบรับเข้ารวมทุกรายการ" button) — reviewed and
     * saved right here instead of navigating to the Movements page.
     */
    public function openReceiptModal(?int $productId = null): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        $products = $productId
            ? Product::with(['category', 'unit'])->whereKey($productId)->get()
            : $this->orderedProducts();

        $this->receiptLines = $products->map(function (Product $p) {
            $suggestQty = $p->suggested_reorder_qty ?? $this->autoSuggestQty($p);

            return $suggestQty > 0 ? [
                'product_id' => $p->id,
                'name' => $p->name,
                'category_name' => $p->category?->name,
                'unit' => $p->unit?->name,
                'qty' => $suggestQty,
                'unit_price' => (float) $p->cost,
            ] : null;
        })->filter()->values()->all();

        $this->receiptForm = ['date' => now()->toDateString(), 'party' => ''];
        $this->receiptError = empty($this->receiptLines) ? 'จำนวนแนะนำของสินค้าที่เลือกเป็น 0 ไม่มีอะไรให้สร้างใบรับเข้า' : null;
        $this->showReceiptModal = true;
    }

    public function removeReceiptLine(int $index): void
    {
        unset($this->receiptLines[$index]);
        $this->receiptLines = array_values($this->receiptLines);
    }

    public function receiptTotal(): float
    {
        // qty is bound live to a text input, so mid-edit it can briefly be "" (or anything
        // non-numeric) while the user is retyping it — cast rather than let that 500.
        return collect($this->receiptLines)->sum(fn ($l) => (float) ($l['qty'] ?: 0) * (float) ($l['unit_price'] ?: 0));
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
    }

    public function saveReceipt(StockService $stock): void
    {
        abort_unless(auth()->user()->can('stock_movements'), 403);

        if (empty($this->receiptLines)) {
            $this->receiptError = 'ไม่มีรายการให้บันทึก';

            return;
        }
        if ($this->receiptForm['date'] === '') {
            $this->receiptError = 'เลือกวันที่';

            return;
        }
        if (collect($this->receiptLines)->contains(fn ($l) => ! is_numeric($l['qty']) || (int) $l['qty'] <= 0)) {
            $this->receiptError = 'กรอกจำนวนให้ครบทุกรายการ (มากกว่า 0)';

            return;
        }

        try {
            $stock->createMovement(
                [
                    'type' => 'in',
                    'date' => $this->receiptForm['date'],
                    'party' => $this->receiptForm['party'] ?: null,
                    'note' => null,
                ],
                collect($this->receiptLines)->map(fn ($l) => [
                    'product_id' => $l['product_id'],
                    'qty' => (int) $l['qty'],
                    'unit_price' => (float) $l['unit_price'],
                    'category_name' => $l['category_name'],
                    'unit' => $l['unit'],
                ])->all(),
                auth()->user()
            );
        } catch (\RuntimeException $e) {
            $this->receiptError = $e->getMessage();

            return;
        }

        $this->showReceiptModal = false;
        $this->refreshLowStockList();
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()->can('view_reports') || auth()->user()->can('edit_products'), 403);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AlertsExport($this->orderedProducts(), fn (Product $p) => $p->suggested_reorder_qty ?? $this->autoSuggestQty($p)),
            'ใกล้หมด-ต้องสั่งซื้อ-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf()
    {
        abort_unless(auth()->user()->can('view_reports') || auth()->user()->can('edit_products'), 403);

        \App\Support\PdfFonts::registerThai();

        $products = $this->orderedProducts();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.alerts-pdf', [
            'products' => $products,
            'suggestQtyFor' => fn (Product $p) => $p->suggested_reorder_qty ?? $this->autoSuggestQty($p),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'ใกล้หมด-ต้องสั่งซื้อ-'.now()->format('Ymd-His').'.pdf'
        );
    }

    /** Same product set/order as render() — factored out so the export actions don't drift from what's on screen. */
    protected function orderedProducts()
    {
        $order = array_flip($this->productIds);

        return Product::with(['category', 'unit'])
            ->whereIn('id', $this->productIds)
            ->get()
            ->sortBy(fn (Product $p) => $order[$p->id] ?? PHP_INT_MAX)
            ->values();
    }

    public function render()
    {
        $products = $this->orderedProducts();

        // Only seed defaults for rows not already tracked, so an in-progress edit in one
        // row isn't clobbered by a render triggered from another row's action.
        foreach ($products as $p) {
            if (! array_key_exists($p->id, $this->suggestQtyInput)) {
                $this->suggestQtyInput[$p->id] = $p->suggested_reorder_qty ?? $this->autoSuggestQty($p);
            }
            if (! array_key_exists($p->id, $this->reorderPointInput)) {
                $this->reorderPointInput[$p->id] = $p->reorder_point_display;
            }
        }

        $suggestValue = $products->sum(function (Product $p) {
            $suggestQty = $p->suggested_reorder_qty ?? $this->autoSuggestQty($p);

            return $suggestQty * $p->cost;
        });

        return view('livewire.alerts.index', [
            'products' => $products,
            'lowCount' => $products->count(),
            'suggestValue' => $suggestValue,
            'highlightProductId' => $this->highlightProductId,
        ])->layout('components.layouts.app', ['title' => 'ใกล้หมด / ต้องสั่งซื้อ', 'subtitle' => 'จุดสั่งซื้อขั้นต่ำและจำนวนที่ระบบแนะนำ']);
    }
}
