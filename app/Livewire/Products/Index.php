<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $categoryFilter = 'all';

    public string $statusFilter = 'all';

    public string $view = 'grid';

    public string $sortField = 'name';

    public string $sortDir = 'asc';

    public array $columnFilters = [];

    public int $tablePerPage = 15;

    public int $gridPerPage = 24;

    public ?int $highlightProductId = null;

    // Form modal
    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '', 'sku' => '', 'size' => '', 'description' => '', 'category_id' => '', 'unit_id' => '',
        'cost' => '', 'price' => '', 'online_price' => '', 'stock' => 0, 'reorder_point' => 0,
    ];

    /**
     * Helper input, not a product field itself — ต้นทุน + กำไรสอบ = ราคาขาย,
     * recomputed into form.price whenever cost or this changes (see updated()).
     */
    public string $profitCheck = '';

    public $photo = null;

    /**
     * The photo already saved on the product being edited, kept separate
     * from $photo (a newly-selected upload) so the form can show it and
     * offer to remove it without requiring a replacement upload.
     */
    public ?string $existingPhotoPath = null;

    public ?string $formError = null;

    /**
     * Editable "ขนาดที่ขาย" (sellable sizes) rows for the product currently
     * being created/edited. Each row: [id (existing variant id or null),
     * label, unit_qty, price, online_price]. Synced wholesale into
     * ProductVariant rows on save() — existing variants not present here
     * anymore are deleted, others updated/created.
     */
    public array $variantRows = [];

    // Detail drawer
    public ?int $detailId = null;

    // Delete confirm
    public ?int $confirmDeleteId = null;

    // Bulk selection
    public array $selectedIds = [];

    public bool $showBulkCategoryModal = false;

    public string $bulkCategoryId = '';

    public bool $confirmBulkDelete = false;

    // Excel import
    public bool $showImportModal = false;

    public $importFile = null;

    public ?array $importRows = null;

    public array $importErrors = [];

    public ?string $importFileError = null;

    /**
     * Deep-link support: /products?category={id} (e.g. from the category cards page)
     * preselects that category's filter on first load.
     */
    public function mount(): void
    {
        $categoryId = request()->query('category');

        if ($categoryId && Category::whereKey($categoryId)->exists()) {
            $this->categoryFilter = (string) $categoryId;
        }
    }

    /**
     * ต้นทุน + กำไรสอบ = ราคาขาย, recalculated live whenever either input changes.
     * ราคาขาย itself stays a normal editable field — typing into it directly
     * (e.g. to round to a nicer price) doesn't get overwritten back.
     */
    public function updated(string $name): void
    {
        if (in_array($name, ['form.cost', 'profitCheck'], true)) {
            if (is_numeric($this->form['cost']) && is_numeric($this->profitCheck)) {
                $this->form['price'] = (string) round((float) $this->form['cost'] + (float) $this->profitCheck, 2);
            }
        }
    }

    public function updatingSearch(): void
    {
        $this->highlightProductId = null;
        $this->resetPage();
        $this->tablePerPage = 15;
        $this->gridPerPage = 24;
    }

    public function updatingCategoryFilter(): void
    {
        $this->highlightProductId = null;
        $this->resetPage();
        $this->tablePerPage = 15;
        $this->gridPerPage = 24;
    }

    public function updatingStatusFilter(): void
    {
        $this->highlightProductId = null;
        $this->resetPage();
        $this->tablePerPage = 15;
        $this->gridPerPage = 24;
    }

    public function setView(string $view): void
    {
        $this->view = $view;
        $this->tablePerPage = 15;
        $this->gridPerPage = 24;
    }

    public function loadMore(): void
    {
        if ($this->view === 'grid') {
            $this->gridPerPage += 24;
        } else {
            $this->tablePerPage += 15;
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
    }

    public function setSort(string $field, string $dir): void
    {
        $this->sortField = $field;
        $this->sortDir = $dir === 'desc' ? 'desc' : 'asc';
        $this->resetPage();
        $this->tablePerPage = 15;
        $this->gridPerPage = 24;
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
        $this->gridPerPage = 24;
    }

    public function clearColumnFilter(string $column): void
    {
        unset($this->columnFilters[$column]);
        $this->resetPage();
        $this->tablePerPage = 15;
        $this->gridPerPage = 24;
    }

    protected function columnOptions(string $column): array
    {
        // Scoped to the active category/status filters, same as baseQuery() — otherwise
        // the checkbox list offers values from products outside the current filter
        // (e.g. other categories) that can never match a row in the filtered table.
        $scoped = fn () => Product::query()
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter !== 'all', function ($q) {
                match ($this->statusFilter) {
                    'out' => $q->where('stock', 0),
                    'low' => $q->where('stock', '>', 0)->whereColumn('stock', '<=', 'reorder_point'),
                    'watch' => $q->whereColumn('stock', '>', 'reorder_point')->whereRaw('stock <= reorder_point * 1.5'),
                    'normal' => $q->whereRaw('stock > reorder_point * 1.5'),
                    'no_cost' => $q->where('cost', '<=', 0),
                    default => null,
                };
            });

        return match ($column) {
            'name' => $scoped()->orderBy('name')->get(['name', 'sku'])
                ->map(fn ($p) => ['value' => (string) $p->sku, 'label' => "{$p->name} ({$p->sku})"])
                ->unique('value')->values()->all(),
            'cost' => $this->numericOptions($scoped()->distinct()->pluck('cost')),
            'price' => $this->numericOptions($scoped()->distinct()->pluck('price')),
            'online_price' => $this->numericOptions($scoped()->distinct()->pluck('online_price')),
            'profit' => $this->numericOptions($scoped()->selectRaw('DISTINCT (price - cost) as profit')->pluck('profit')),
            'stock' => $this->numericOptions($scoped()->distinct()->pluck('stock'), 0),
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

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'statusFilter']);
        $this->columnFilters = [];
        $this->highlightProductId = null;
        $this->resetPage();
        $this->tablePerPage = 15;
        $this->gridPerPage = 24;
    }

    public function hasChipFilters(): bool
    {
        return $this->categoryFilter !== 'all'
            || $this->statusFilter !== 'all'
            || ! empty($this->columnFilters);
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $product = Product::findOrFail($id);
        $this->editingId = $product->id;
        $this->form = [
            'name' => $product->name,
            'sku' => $product->sku,
            'size' => $product->size,
            'description' => $product->description,
            'category_id' => (string) $product->category_id,
            'unit_id' => (string) $product->unit_id,
            'cost' => (string) $product->cost,
            'price' => (string) $product->price,
            'online_price' => (string) $product->online_price,
            'stock' => $product->stock_display,
            'reorder_point' => $product->reorder_point_display,
        ];
        $this->profitCheck = (string) round((float) $product->price - (float) $product->cost, 2);
        $this->variantRows = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'label' => $v->label,
            'unit_qty' => rtrim(rtrim((string) $v->unit_qty, '0'), '.'),
            'price' => (string) $v->price,
            'online_price' => $v->online_price !== null ? (string) $v->online_price : '',
        ])->all();
        $this->existingPhotoPath = $product->photo_path;
        $this->formError = null;
        $this->showForm = true;
        $this->detailId = null;
    }

    public function removePhoto(): void
    {
        $this->photo = null;
        $this->existingPhotoPath = null;
    }

    public function addVariantRow(): void
    {
        $this->variantRows[] = ['id' => null, 'label' => '', 'unit_qty' => '', 'price' => '', 'online_price' => ''];
    }

    public function removeVariantRow(int $index): void
    {
        unset($this->variantRows[$index]);
        $this->variantRows = array_values($this->variantRows);
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '', 'sku' => '', 'size' => '', 'description' => '', 'category_id' => '', 'unit_id' => '',
            'cost' => '', 'price' => '', 'online_price' => '', 'stock' => 0, 'reorder_point' => 0,
        ];
        $this->profitCheck = '';
        $this->variantRows = [];
        $this->photo = null;
        $this->existingPhotoPath = null;
        $this->formError = null;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Called from the category combobox when the typed name has no existing
     * match — firstOrCreate() means picking an already-existing name (e.g.
     * different casing) just selects it instead of creating a duplicate.
     */
    public function addCategory(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return (string) ($this->form['category_id'] ?? '');
        }

        $category = $this->findOrCreateCategory($name);

        return (string) $category->id;
    }

    /**
     * Category::firstOrCreate() alone breaks here: Category uses SoftDeletes, so
     * its unique index still blocks re-inserting a name that belongs to a
     * soft-deleted row — firstOrCreate()'s own SELECT excludes trashed rows by
     * default, so it tries to INSERT and hits a UniqueConstraintViolationException.
     * Look through trashed rows too and restore instead, same as how Product
     * rows are matched by SKU (including trashed ones) a few lines below.
     */
    private function findOrCreateCategory(string $name): Category
    {
        $category = Category::withTrashed()->where('name', $name)->first()
            ?? Category::create(['name' => $name]);

        if ($category->trashed()) {
            $category->restore();
        }

        return $category;
    }

    public function addUnit(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return (string) ($this->form['unit_id'] ?? '');
        }

        $unit = Unit::firstOrCreate(['name' => $name]);

        return (string) $unit->id;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $validated = \Illuminate\Support\Facades\Validator::make($this->form, [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku,'.($this->editingId ?? 'NULL').',id',
            'size' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'cost' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'online_price' => 'nullable|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'reorder_point' => 'required|numeric|min:0',
        ])->validate();

        $validated['online_price'] = $validated['online_price'] === '' ? null : $validated['online_price'];
        $validated['size'] = $validated['size'] === '' ? null : $validated['size'];
        $validated['description'] = $validated['description'] === '' ? null : $validated['description'];

        $variants = \Illuminate\Support\Facades\Validator::make(['variants' => $this->variantRows], [
            'variants' => 'array',
            'variants.*.label' => 'required|string|max:100',
            'variants.*.unit_qty' => 'required|numeric|min:0.001',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.online_price' => 'nullable|numeric|min:0',
        ])->validate()['variants'];

        if ($this->photo) {
            \Illuminate\Support\Facades\Validator::make(['photo' => $this->photo], [
                'photo' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            ])->validate();

            if ($this->editingId && $this->existingPhotoPath) {
                Storage::disk('public')->delete($this->existingPhotoPath);
            }

            $validated['photo_path'] = $this->photo->store('products', 'public');
        } elseif ($this->editingId && $this->existingPhotoPath === null) {
            $current = Product::find($this->editingId);
            if ($current?->photo_path) {
                Storage::disk('public')->delete($current->photo_path);
                $validated['photo_path'] = null;
            }
        }

        if ($this->editingId) {
            $product = Product::findOrFail($this->editingId);
            $product->update($validated);
        } else {
            $product = Product::create($validated);
            $this->highlightProductId = $product->id;
        }

        $keptIds = [];
        foreach ($variants as $sort => $row) {
            $data = [
                'label' => $row['label'],
                'unit_qty' => $row['unit_qty'],
                'price' => $row['price'],
                'online_price' => $row['online_price'] === '' || $row['online_price'] === null ? null : $row['online_price'],
                'sort_order' => $sort,
            ];

            $variant = ! empty($row['id'])
                ? $product->variants()->whereKey($row['id'])->first()
                : null;

            if ($variant) {
                $variant->update($data);
            } else {
                $variant = $product->variants()->create($data);
            }

            $keptIds[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keptIds)->delete();

        $this->showForm = false;
        $this->resetForm();
    }

    public function openImportModal(): void
    {
        // นำเข้าจาก Excel จำกัดให้เฉพาะเจ้าของร้านเท่านั้น (เข้มกว่าสิทธิ์ edit_products ปกติ)
        abort_unless(auth()->user()->isOwner(), 403);

        $this->importFile = null;
        $this->importRows = null;
        $this->importErrors = [];
        $this->importFileError = null;
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

        $valid = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNo = $i + 2; // +1 for 0-index, +1 for the heading row
            $sku = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            $size = trim((string) ($row[2] ?? ''));
            $categoryName = trim((string) ($row[3] ?? ''));
            $unitName = trim((string) ($row[4] ?? ''));
            $costRaw = $row[5] ?? null;
            $priceRaw = $row[6] ?? null;
            $onlinePriceRaw = $row[7] ?? null;
            $reorderRaw = $row[8] ?? null;

            if ($sku === '' && $name === '') {
                continue; // fully blank row
            }

            if ($sku === '') {
                $errors[] = "แถวที่ {$rowNo}: ไม่มีรหัส SKU";

                continue;
            }
            if ($name === '') {
                $errors[] = "แถวที่ {$rowNo}: ไม่มีชื่อสินค้า";

                continue;
            }
            if ($categoryName === '') {
                $errors[] = "แถวที่ {$rowNo}: ไม่ได้ระบุประเภท";

                continue;
            }
            if ($unitName === '') {
                $errors[] = "แถวที่ {$rowNo}: ไม่ได้ระบุหน่วยนับ";

                continue;
            }
            if ($costRaw !== null && $costRaw !== '' && ! is_numeric($costRaw)) {
                $errors[] = "แถวที่ {$rowNo}: ต้นทุนไม่ถูกต้อง";

                continue;
            }
            if ($priceRaw !== null && $priceRaw !== '' && ! is_numeric($priceRaw)) {
                $errors[] = "แถวที่ {$rowNo}: ราคาขายไม่ถูกต้อง";

                continue;
            }
            if ($onlinePriceRaw !== null && $onlinePriceRaw !== '' && ! is_numeric($onlinePriceRaw)) {
                $errors[] = "แถวที่ {$rowNo}: ราคาออนไลน์ไม่ถูกต้อง";

                continue;
            }
            if ($reorderRaw !== null && $reorderRaw !== '' && ! is_numeric($reorderRaw)) {
                $errors[] = "แถวที่ {$rowNo}: จุดสั่งซื้อขั้นต่ำไม่ถูกต้อง";

                continue;
            }

            $existing = Product::withTrashed()->where('sku', $sku)->first();

            $valid[] = [
                'row' => $rowNo,
                'sku' => $sku,
                'name' => $name,
                'size' => $size !== '' ? $size : null,
                'category_name' => $categoryName,
                'unit_name' => $unitName,
                'cost' => is_numeric($costRaw) ? (float) $costRaw : 0,
                'price' => is_numeric($priceRaw) ? (float) $priceRaw : 0,
                'online_price' => is_numeric($onlinePriceRaw) ? (float) $onlinePriceRaw : null,
                'reorder_point' => is_numeric($reorderRaw) ? (int) $reorderRaw : 0,
                'action' => $existing ? 'update' : 'create',
            ];
        }

        // Duplicate SKUs within the file: keep the last occurrence, warn about the rest.
        $bySku = [];
        foreach ($valid as $row) {
            if (isset($bySku[$row['sku']])) {
                $errors[] = "SKU \"{$row['sku']}\" ซ้ำในไฟล์ (แถวที่ {$bySku[$row['sku']]['row']} และ {$row['row']}) — ใช้ค่าจากแถวที่ {$row['row']}";
            }
            $bySku[$row['sku']] = $row;
        }

        $this->importErrors = $errors;
        $this->importRows = array_values($bySku);
    }

    public function confirmImport(): void
    {
        abort_unless(auth()->user()->isOwner(), 403);

        if (empty($this->importRows)) {
            return;
        }

        foreach ($this->importRows as $row) {
            $category = $this->findOrCreateCategory($row['category_name']);
            $unit = Unit::firstOrCreate(['name' => $row['unit_name']]);

            $product = Product::withTrashed()->where('sku', $row['sku'])->first() ?? new Product(['sku' => $row['sku']]);

            $product->fill([
                'name' => $row['name'],
                'size' => $row['size'],
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'cost' => $row['cost'],
                'price' => $row['price'],
                'online_price' => $row['online_price'],
                'reorder_point' => $row['reorder_point'],
            ]);
            $product->save();

            if ($product->trashed()) {
                $product->restore();
            }
        }

        $this->closeImportModal();
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
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

        if ($this->confirmDeleteId) {
            $product = Product::find($this->confirmDeleteId);
            if ($product?->photo_path) {
                Storage::disk('public')->delete($product->photo_path);
            }
            $product?->delete();
        }

        $this->confirmDeleteId = null;
        $this->detailId = null;
    }

    public function toggleSelected(int $id): void
    {
        if (($k = array_search($id, $this->selectedIds, true)) !== false) {
            unset($this->selectedIds[$k]);
            $this->selectedIds = array_values($this->selectedIds);
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function selectAllOnPage(array $ids): void
    {
        $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $ids)));
    }

    public function deselectAllOnPage(array $ids): void
    {
        $this->selectedIds = array_values(array_diff($this->selectedIds, $ids));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function openBulkCategoryModal(): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $this->bulkCategoryId = '';
        $this->showBulkCategoryModal = true;
    }

    public function closeBulkCategoryModal(): void
    {
        $this->showBulkCategoryModal = false;
    }

    public function applyBulkCategory(): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $validated = \Illuminate\Support\Facades\Validator::make(
            ['category_id' => $this->bulkCategoryId],
            ['category_id' => 'required|exists:categories,id']
        )->validate();

        Product::whereIn('id', $this->selectedIds)->update(['category_id' => $validated['category_id']]);

        $this->showBulkCategoryModal = false;
        $this->clearSelection();
    }

    public function askBulkDelete(): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        $this->confirmBulkDelete = true;
    }

    public function cancelBulkDelete(): void
    {
        $this->confirmBulkDelete = false;
    }

    public function bulkDelete(): void
    {
        abort_unless(auth()->user()->can('edit_products'), 403);

        foreach (Product::whereIn('id', $this->selectedIds)->get() as $product) {
            if ($product->photo_path) {
                Storage::disk('public')->delete($product->photo_path);
            }
            $product->delete();
        }

        $this->confirmBulkDelete = false;
        $this->clearSelection();
    }

    protected function baseQuery()
    {
        $query = Product::query()
            ->with(['category', 'unit'])
            ->selectRaw('products.*, (price - cost) as profit')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter !== 'all', function ($q) {
                match ($this->statusFilter) {
                    'out' => $q->where('stock', 0),
                    'low' => $q->where('stock', '>', 0)->whereColumn('stock', '<=', 'reorder_point'),
                    'watch' => $q->whereColumn('stock', '>', 'reorder_point')->whereRaw('stock <= reorder_point * 1.5'),
                    'normal' => $q->whereRaw('stock > reorder_point * 1.5'),
                    'no_cost' => $q->where('cost', '<=', 0),
                    default => null,
                };
            });

        foreach ($this->columnFilters as $column => $values) {
            match ($column) {
                'name' => $query->whereIn('sku', $values),
                'profit' => $query->havingRaw('profit IN ('.implode(',', array_fill(0, count($values), '?')).')', $values),
                default => $query->whereIn($column, $values),
            };
        }

        return $query;
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()->can('view_reports') || auth()->user()->can('edit_products'), 403);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductsExport($this->baseQuery()->orderBy($this->sortField, $this->sortDir)),
            'สินค้าและราคา-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf()
    {
        abort_unless(auth()->user()->can('view_reports') || auth()->user()->can('edit_products'), 403);

        \App\Support\PdfFonts::registerThai();

        $products = $this->baseQuery()->orderBy($this->sortField, $this->sortDir)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.products-pdf', [
            'products' => $products,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'สินค้าและราคา-'.now()->format('Ymd-His').'.pdf'
        );
    }

    /**
     * If the just-added product still matches the current filters, bump the visible
     * page size so it's actually on-screen instead of buried past the load-more cutoff.
     */
    protected function ensureHighlightVisible(): void
    {
        if (! $this->highlightProductId) {
            return;
        }

        $ids = $this->baseQuery()->orderBy($this->sortField, $this->sortDir)->pluck('products.id')->values();
        $rank = $ids->search($this->highlightProductId);

        if ($rank === false) {
            $this->highlightProductId = null;

            return;
        }

        $needed = $rank + 1;
        if ($this->view === 'grid') {
            $this->gridPerPage = max($this->gridPerPage, $needed);
        } else {
            $this->tablePerPage = max($this->tablePerPage, $needed);
        }
    }

    public function render()
    {
        $this->ensureHighlightVisible();

        $products = $this->view === 'grid'
            ? $this->baseQuery()->orderBy($this->sortField, $this->sortDir)->paginate($this->gridPerPage, ['*'], 'page', 1)
            : $this->baseQuery()->orderBy($this->sortField, $this->sortDir)->paginate($this->tablePerPage, ['*'], 'page', 1);

        $categories = Category::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('livewire.products.index', [
            'products' => $products,
            'hasMoreRows' => $products->hasMorePages(),
            'pageIds' => $products->pluck('id')->all(),
            'categories' => $categories,
            'units' => $units,
            'categoryFilterOptions' => collect([['value' => 'all', 'label' => 'ทุกประเภท']])
                ->concat($categories->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name]))
                ->all(),
            'statusFilterOptions' => [
                ['value' => 'all', 'label' => 'ทุกสถานะ'],
                ['value' => 'normal', 'label' => 'ปกติ'],
                ['value' => 'watch', 'label' => 'เฝ้าระวัง'],
                ['value' => 'low', 'label' => 'ใกล้หมด'],
                ['value' => 'out', 'label' => 'หมดสต็อก'],
                ['value' => 'no_cost', 'label' => 'ยังไม่มีต้นทุน'],
            ],
            'categoryOptions' => $categories->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->values()->all(),
            'unitOptions' => $units->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->values()->all(),
            'sizeOptions' => Product::query()->whereNotNull('size')->where('size', '!=', '')
                ->distinct()->orderBy('size')->pluck('size')
                ->map(fn ($s) => ['value' => $s, 'label' => $s])->values()->all(),
            'highlightProductId' => $this->highlightProductId,
            'detailProduct' => $this->detailId ? Product::with('category', 'unit', 'variants')->find($this->detailId) : null,
            'deleteProduct' => $this->confirmDeleteId ? Product::find($this->confirmDeleteId) : null,
            'columnOptionsMap' => [
                'name' => $this->columnOptions('name'),
                'cost' => $this->columnOptions('cost'),
                'price' => $this->columnOptions('price'),
                'online_price' => $this->columnOptions('online_price'),
                'profit' => $this->columnOptions('profit'),
                'stock' => $this->columnOptions('stock'),
            ],
        ])->layout('components.layouts.app', ['title' => 'สินค้าและราคา', 'subtitle' => 'ประเภท · ราคาหน่วย · ราคาต่อหน่วย · ออนไลน์ พร้อมยอดคงเหลือ']);
    }
}
