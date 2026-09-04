<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $formError = null;

    public ?int $confirmDeleteId = null;

    public function openCreate(): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $this->editingId = null;
        $this->name = '';
        $this->formError = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $category = Category::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->formError = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $name = trim($this->name);

        if ($name === '') {
            $this->formError = 'กรอกชื่อประเภท';

            return;
        }

        $exists = Category::where('name', $name)->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))->exists();

        if ($exists) {
            $this->formError = 'มีประเภทนี้อยู่แล้ว';

            return;
        }

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update(['name' => $name]);
        } else {
            // Category uses SoftDeletes — a name once used by a deleted category
            // still occupies the DB's unique index, so a plain create() here would
            // throw a UniqueConstraintViolationException even though the $exists
            // check above (which excludes trashed rows) said the name was free.
            // Bring the old row back instead of crashing on a name that "looks" new.
            $trashed = Category::onlyTrashed()->where('name', $name)->first();
            $trashed ? $trashed->restore() : Category::create(['name' => $name]);
        }

        $this->showForm = false;
    }

    public function askDelete(int $id): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $category = Category::find($this->confirmDeleteId);

        if ($category && $category->products()->count() === 0) {
            $category->delete();
        }

        $this->confirmDeleteId = null;
    }

    public function render()
    {
        $categories = Category::withCount('products')
            ->with(['products' => fn ($q) => $q->select('id', 'category_id', 'cost', 'price', 'stock', 'reorder_point')])
            ->orderBy('name')
            ->get()
            ->map(function (Category $category) {
                $products = $category->products;

                return [
                    'category' => $category,
                    'count' => $products->count(),
                    'stockValue' => $products->sum(fn ($p) => $p->stock * $p->cost),
                    'avgMargin' => $products->count()
                        ? $products->avg(fn ($p) => $p->price > 0 ? (($p->price - $p->cost) / $p->price) * 100 : 0)
                        : 0,
                    'lowCount' => $products->filter(fn ($p) => $p->stock <= $p->reorder_point)->count(),
                ];
            });

        $kpis = [
            ['label' => 'ประเภทสินค้าทั้งหมด', 'value' => number_format($categories->count())],
            ['label' => 'มูลค่าสต็อกรวม', 'value' => number_format($categories->sum('stockValue'), 0).' บาท'],
            ['label' => 'สินค้าใกล้หมดรวม', 'value' => number_format($categories->sum('lowCount'))],
        ];

        return view('livewire.categories.index', [
            'categories' => $categories,
            'kpis' => $kpis,
            'deleteCategory' => $this->confirmDeleteId ? Category::withCount('products')->find($this->confirmDeleteId) : null,
        ])->layout('components.layouts.app', ['title' => 'ประเภทสินค้า', 'subtitle' => 'มูลค่าสต็อกและอัตรากำไรแยกตามประเภท']);
    }
}
