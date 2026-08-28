<?php

namespace App\Livewire\StockCount;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public string $newCategoryId = '';

    public string $countSearch = '';

    public array $realQty = [];

    public ?string $startError = null;

    public ?int $confirmCancelId = null;

    public ?int $highlightCountId = null;

    public int $completedPerPage = 10;

    public function mount(): void
    {
        $draft = StockCount::with('lines')->where('status', 'draft')->first();

        if ($draft) {
            $this->realQty = $draft->lines->mapWithKeys(fn ($l) => [$l->id => $l->real_qty])->all();
        }

        $highlightId = request()->query('count');
        if ($highlightId && StockCount::where('id', $highlightId)->where('status', 'completed')->exists()) {
            $this->highlightCountId = (int) $highlightId;

            // The completed list only ever shows the latest N — make sure the
            // highlighted one is actually within the loaded page.
            $rank = StockCount::where('status', 'completed')
                ->where(function ($q) use ($highlightId) {
                    $target = StockCount::find($highlightId);
                    $q->where('date', '>', $target->date)
                        ->orWhere(fn ($q2) => $q2->where('date', $target->date)->where('id', '>=', $target->id));
                })
                ->count();
            $this->completedPerPage = max($this->completedPerPage, $rank);
        }
    }

    public function updatedRealQty($value, $key): void
    {
        abort_unless(auth()->user()->can('stock_count'), 403);

        StockCountLine::whereKey($key)
            ->whereHas('stockCount', fn ($q) => $q->where('status', 'draft'))
            ->update(['real_qty' => $value === '' ? null : (int) $value]);
    }

    public function startCount(StockService $stock): void
    {
        abort_unless(auth()->user()->can('stock_count'), 403);

        $products = Product::when($this->newCategoryId !== '', fn ($q) => $q->where('category_id', $this->newCategoryId))->get();

        if ($products->isEmpty()) {
            $this->startError = 'ไม่มีสินค้าในประเภทที่เลือก';

            return;
        }

        $category = $this->newCategoryId !== '' ? Category::find($this->newCategoryId) : null;

        // Locks the draft-count row range for the duration of the check+create so two
        // near-simultaneous submits (e.g. a double-click) can't both pass the exists()
        // check and create two draft counts at once.
        $count = DB::transaction(function () use ($stock, $category) {
            if (StockCount::where('status', 'draft')->lockForUpdate()->exists()) {
                return null;
            }

            return StockCount::create([
                'count_no' => $stock->nextCountNo(),
                'date' => now()->toDateString(),
                'category_id' => $category?->id,
                'scope_label' => $category ? $category->name : 'ทั้งร้าน',
                'status' => 'draft',
                'user_id' => auth()->id(),
            ]);
        });

        if (! $count) {
            $this->startError = 'มีรอบนับที่ยังไม่ปิดอยู่แล้ว ปิดรอบนั้นก่อนเริ่มรอบใหม่';

            return;
        }

        foreach ($products as $product) {
            $count->lines()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'system_qty' => $product->stock,
                'real_qty' => null,
            ]);
        }

        $this->newCategoryId = '';
        $this->startError = null;
        $this->realQty = [];
        $this->countSearch = '';
    }

    public function completeCount(int $countId, StockService $stock): void
    {
        abort_unless(auth()->user()->can('stock_count'), 403);

        $count = StockCount::findOrFail($countId);
        $stock->completeStockCount($count, auth()->user());
        $this->countSearch = '';
    }

    public function askCancelDraft(int $countId): void
    {
        abort_unless(auth()->user()->can('stock_count'), 403);

        $this->confirmCancelId = $countId;
    }

    public function cancelCancelDraft(): void
    {
        $this->confirmCancelId = null;
    }

    public function cancelDraft(): void
    {
        abort_unless(auth()->user()->can('stock_count'), 403);

        if ($this->confirmCancelId) {
            $count = StockCount::find($this->confirmCancelId);
            if ($count && $count->status === 'draft') {
                $count->lines()->delete();
                $count->delete();
            }
        }

        $this->confirmCancelId = null;
        $this->countSearch = '';
    }

    public function render()
    {
        $draft = StockCount::with(['lines.product.unit', 'user'])->where('status', 'draft')->first();
        $completed = StockCount::with(['lines', 'user'])->where('status', 'completed')->latest('date')->latest('id')->limit($this->completedPerPage)->get();
        $categories = Category::orderBy('name')->get();

        return view('livewire.stock-count.index', [
            'draft' => $draft,
            'completed' => $completed,
            'categories' => $categories,
            'categoryOptions' => collect([['value' => '', 'label' => 'ทั้งร้าน']])
                ->concat($categories->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name]))
                ->values()->all(),
            'confirmCancel' => $this->confirmCancelId ? StockCount::find($this->confirmCancelId) : null,
            'highlightCountId' => $this->highlightCountId,
        ])->layout('components.layouts.app', ['title' => 'นับสต็อก', 'subtitle' => 'เทียบยอดจริงกับระบบและเก็บประวัติทุกรอบ']);
    }
}
