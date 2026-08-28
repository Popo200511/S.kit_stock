<div class="flex flex-col gap-3.5" x-data="{ toolsOpen: false }">

    {{-- Filter bar --}}
    <div class="flex flex-wrap gap-2 items-center sticky top-[60px] z-30 bg-bg py-2 -mx-1 px-1">
        <div class="flex-1 min-w-[190px] flex items-center gap-2 bg-surface border border-border2 rounded-[10px] px-3 py-2 shadow-sm focus-within:border-accent">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-muted3" stroke-width="1.9" stroke-linecap="round"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3"></path></svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="ค้นหาชื่อสินค้า หรือรหัส SKU"
                class="flex-1 min-w-0 text-[13.5px] border-0 p-0 focus:ring-0 focus:outline-none bg-transparent">
        </div>

        <div class="w-[170px]">
            <x-combobox field="categoryFilter" :options="$categoryFilterOptions" placeholder="เลือกประเภท" :live="true" />
        </div>

        <div class="w-[150px]">
            <x-combobox field="statusFilter" :options="$statusFilterOptions" placeholder="เลือกสถานะ" :live="true" />
        </div>

        <div class="flex gap-1 bg-chip p-[3px] rounded-[9px]">
            <button wire:click="setView('grid')" class="px-3.5 py-1.5 rounded-[7px] text-[12.5px] font-medium {{ $view === 'grid' ? 'bg-surface shadow-sm' : 'text-muted2' }}">การ์ด</button>
            <button wire:click="setView('table')" class="px-3.5 py-1.5 rounded-[7px] text-[12.5px] font-medium {{ $view === 'table' ? 'bg-surface shadow-sm' : 'text-muted2' }}">ตาราง</button>
        </div>

        <div class="relative">
            <button @click="toolsOpen = !toolsOpen" class="flex items-center gap-1.5 px-3.5 py-2.5 rounded-[10px] border border-border4 text-[12.5px] text-text2 hover:border-accent hover:text-accent">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v14M8 3L4 7M8 3l4 4M16 21V7M16 21l4-4M16 21l-4-4"></path></svg>นำเข้า/ส่งออก
            </button>
            <div x-show="toolsOpen" @click.outside="toolsOpen = false" x-cloak class="absolute top-11 right-0 w-[200px] bg-surface border border-border2 rounded-xl shadow-lg z-30 overflow-hidden">
                @if (auth()->user()->isOwner())
                    <button wire:click="openImportModal" class="w-full flex items-center gap-2 px-3.5 py-2.5 text-[12.5px] text-text2 border-b border-hairline hover:bg-surface4">
                        <span class="w-6 h-6 rounded-[7px] bg-chip text-text2 flex items-center justify-center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9M12 9l-4 4M12 9l4 4M4 3h16"></path></svg>
                        </span>นำเข้าจาก Excel
                    </button>
                @endif
                <button wire:click="exportExcel" class="w-full flex items-center gap-2 px-3.5 py-2.5 text-[12.5px] text-text2 border-b border-hairline hover:bg-surface4">
                    <span class="w-6 h-6 rounded-[7px] bg-accent-tint text-accent flex items-center justify-center text-[10px] font-semibold">XLS</span>ส่งออก Excel (.xlsx)
                </button>
                <button wire:click="exportPdf" class="w-full flex items-center gap-2 px-3.5 py-2.5 text-[12.5px] text-text2 hover:bg-surface4">
                    <span class="w-6 h-6 rounded-[7px] bg-danger-tint text-danger flex items-center justify-center text-[10px] font-semibold">PDF</span>ส่งออก PDF / พิมพ์
                </button>
            </div>
        </div>

        @if ($this->hasChipFilters())
            <button wire:click="clearFilters" class="flex items-center gap-1.5 px-3 py-2 rounded-[10px] border border-danger-border text-xs text-danger hover:bg-danger-tint2">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"></path></svg>ล้างตัวกรอง
            </button>
        @endif

        @can('edit_products')
            <button wire:click="openCreate" class="flex items-center gap-1.5 px-4 py-2.5 rounded-[10px] bg-accent text-white text-[12.5px] font-medium hover:bg-accent-hover">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>เพิ่มสินค้า
            </button>
        @endcan

        @can('edit_products')
            @php $allOnPageSelected = ! empty($pageIds) && empty(array_diff($pageIds, $selectedIds)); @endphp
            <label class="flex items-center gap-1.5 px-1 text-[12.5px] text-text2 cursor-pointer select-none">
                <input type="checkbox" {{ $allOnPageSelected ? 'checked' : '' }}
                    wire:click="{{ $allOnPageSelected ? 'deselectAllOnPage' : 'selectAllOnPage' }}({{ json_encode($pageIds) }})"
                    class="w-[15px] h-[15px] rounded-[5px] border-border4 text-accent focus:ring-accent focus:outline-none">
                เลือกทั้งหมดในหน้านี้
            </label>
        @endcan

        <span class="ml-auto text-[12.5px] text-muted tabular-nums whitespace-nowrap">{{ $products->total() }} รายการ</span>
    </div>

    @can('edit_products')
        @if (! empty($selectedIds))
            <div class="flex flex-wrap items-center gap-3 bg-accent-tint border border-accent-border2 rounded-[12px] px-4 py-2.5">
                <span class="text-[13px] font-medium text-accent-ink">เลือก {{ count($selectedIds) }} รายการ</span>
                <div class="flex items-center gap-2 ml-auto">
                    <button wire:click="openBulkCategoryModal" class="px-3.5 py-1.5 rounded-[9px] bg-surface border border-border4 text-[12.5px] font-medium text-text2 hover:border-accent hover:text-accent">เปลี่ยนประเภท</button>
                    <button wire:click="askBulkDelete" class="px-3.5 py-1.5 rounded-[9px] bg-surface border border-danger-border text-[12.5px] font-medium text-danger hover:bg-danger-tint2">ลบ</button>
                    <button wire:click="clearSelection" class="px-3 py-1.5 rounded-[9px] text-[12.5px] text-muted2 hover:text-text">ยกเลิก</button>
                </div>
            </div>
        @endif
    @endcan

    {{-- Grid view --}}
    @if ($view === 'grid')
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3.5">
            @foreach ($products as $product)
                @php
                    $status = $product->stock_status;
                    $isHighlighted = $highlightProductId && $product->id === $highlightProductId;
                @endphp
                <div wire:click="openDetail({{ $product->id }})" wire:key="grid-{{ $product->id }}"
                    @if ($isHighlighted) x-data x-init="setTimeout(() => $el.scrollIntoView({ behavior: 'instant', block: 'center' }), 300)" @endif
                    @class([
                        'group cursor-pointer bg-surface rounded-[15px] overflow-hidden shadow-sm hover:shadow-md flex flex-col border',
                        'border-border hover:border-accent-border2' => ! $isHighlighted,
                        'border-accent ring-2 ring-accent' => $isHighlighted,
                    ])>
                    <div class="relative h-[132px] bg-sunken border-b border-line flex items-center justify-center">
                        @if ($product->photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->photo_path) }}" class="w-full h-full object-cover" alt="{{ $product->name }}">
                        @else
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-muted3" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v14H4zM4 16l5-5 4 4 2-2 5 5M15 9h.01"></path></svg>
                        @endif
                        <span @class([
                            'absolute top-2 left-2 text-[11px] font-medium px-2.5 py-0.5 rounded-full',
                            'bg-accent-tint text-accent' => $status['tone'] === 'accent',
                            'bg-caution-tint text-caution' => $status['tone'] === 'caution',
                            'bg-warn-tint text-warn' => $status['tone'] === 'warn',
                            'bg-danger-tint text-danger' => $status['tone'] === 'danger',
                        ])>{{ $status['label'] }}</span>
                        @can('edit_products')
                            <button wire:click.stop="askDelete({{ $product->id }})" title="ลบสินค้านี้"
                                class="absolute top-2 right-2 w-[27px] h-[27px] rounded-lg bg-surface shadow flex items-center justify-center text-muted2 hover:bg-danger-tint hover:text-danger">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                            </button>
                            <label wire:click.stop @class([
                                'absolute bottom-2 left-2 w-[24px] h-[24px] rounded-lg bg-surface shadow flex items-center justify-center cursor-pointer transition-opacity',
                                'opacity-100' => in_array($product->id, $selectedIds),
                                'opacity-0 group-hover:opacity-100' => ! in_array($product->id, $selectedIds),
                            ])>
                                <input type="checkbox" wire:click="toggleSelected({{ $product->id }})" @checked(in_array($product->id, $selectedIds))
                                    class="w-[15px] h-[15px] rounded-[5px] border-border4 text-accent focus:ring-accent focus:outline-none">
                            </label>
                        @endcan
                    </div>
                    <div class="p-3.5 flex flex-col gap-2.5">
                        <div class="flex flex-col gap-0.5 leading-snug">
                            <span class="text-[13.5px] font-medium truncate">{{ $product->name }}</span>
                            <span class="text-[11.5px] text-muted2 tabular-nums">{{ $product->sku }} · {{ $product->size }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-2.5 border-t border-hairline pt-2.5">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[10.5px] text-muted2">ราคาต่อหน่วย</span>
                                <span class="text-base font-semibold tabular-nums tracking-tight">{{ number_format($product->price, 0) }} บาท</span>
                            </div>
                            <div class="flex flex-col gap-0.5 text-right">
                                <span class="text-[10.5px] text-muted2">คงเหลือ</span>
                                <span @class([
                                    'text-sm font-semibold tabular-nums',
                                    'text-accent' => $status['tone'] === 'accent',
                                    'text-caution' => $status['tone'] === 'caution',
                                    'text-warn' => $status['tone'] === 'warn',
                                    'text-danger' => $status['tone'] === 'danger',
                                ])>{{ $product->stock_display }} {{ $product->unit?->name }}</span>
                            </div>
                        </div>
                        <div class="flex gap-1.5 text-[11px] text-muted">
                            <span class="px-2 py-0.5 rounded-md bg-sunken tabular-nums">ออนไลน์ {{ $product->online_price !== null ? number_format($product->online_price, 0).' บาท' : '—' }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($products->isEmpty())
            <div class="bg-surface border border-border rounded-[15px] p-10 text-center text-sm text-muted2">ไม่พบสินค้าที่ตรงกับตัวกรอง</div>
        @endif
        @if ($hasMoreRows)
            <div wire:key="load-more-grid-{{ $gridPerPage }}" x-data x-init="
                const io = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting) { $wire.loadMore(); io.disconnect(); }
                }, { rootMargin: '300px' });
                io.observe($el);
            " class="py-3.5 text-center text-[12px] text-muted2">กำลังโหลดเพิ่มเติม...</div>
        @endif
    @endif

    {{-- Table view --}}
    @if ($view === 'table')
        <div class="table-scroll-container bg-surface border border-border rounded-[15px] shadow-sm overflow-auto max-h-[65vh]">
            <table class="w-full text-[12.5px]">
                <thead>
                    <tr class="sticky top-0 z-20 bg-surface2 border-b border-line text-[11px] font-semibold tracking-wide text-muted2">
                        @can('edit_products')
                            @php $allOnPageSelectedTbl = ! empty($pageIds) && empty(array_diff($pageIds, $selectedIds)); @endphp
                            <th class="w-9 px-4 py-2.5">
                                <input type="checkbox" {{ $allOnPageSelectedTbl ? 'checked' : '' }}
                                    wire:click="{{ $allOnPageSelectedTbl ? 'deselectAllOnPage' : 'selectAllOnPage' }}({{ json_encode($pageIds) }})"
                                    class="w-[15px] h-[15px] rounded-[5px] border-border4 text-accent focus:ring-accent focus:outline-none">
                            </th>
                        @endcan
                        <th class="text-left px-4 py-2.5 relative">
                            <span class="inline-flex items-center gap-1">
                                สินค้า
                            </span>
                            <x-excel-filter field="name" :options="$columnOptionsMap['name']" :selected="$columnFilters['name'] ?? null" sort-method="setSort" align="left" wire:key="filter-name-{{ $categoryFilter }}-{{ $statusFilter }}" />
                        </th>
                        @foreach ([['cost','ต้นทุน'],['price','ราคาขาย'],['online_price','ออนไลน์'],['profit','กำไร'],['stock','คงเหลือ']] as [$field, $label])
                            <th class="text-right px-4 py-2.5 relative">
                                <span class="inline-flex items-center gap-1">
                                    {{ $label }}
                                </span>
                                <x-excel-filter :field="$field" :options="$columnOptionsMap[$field]" :selected="$columnFilters[$field] ?? null" sort-method="setSort" align="right" wire:key="filter-{{ $field }}-{{ $categoryFilter }}-{{ $statusFilter }}" />
                            </th>
                        @endforeach
                        @can('edit_products')
                            <th class="w-11 px-4 py-2.5"></th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php
                            $status = $product->stock_status;
                            $isHighlighted = $highlightProductId && $product->id === $highlightProductId;
                        @endphp
                        <tr wire:click="openDetail({{ $product->id }})" wire:key="row-{{ $product->id }}"
                            @if ($isHighlighted) x-data x-init="setTimeout(() => $el.scrollIntoView({ behavior: 'instant', block: 'center' }), 300)" @endif
                            @class(['border-b border-hairline2 hover:bg-surface2 cursor-pointer transition-colors', 'bg-accent-tint' => $isHighlighted])>
                            @can('edit_products')
                                <td class="px-4 py-2.5" wire:click.stop>
                                    <input type="checkbox" wire:click="toggleSelected({{ $product->id }})" @checked(in_array($product->id, $selectedIds))
                                        class="w-[15px] h-[15px] rounded-[5px] border-border4 text-accent focus:ring-accent focus:outline-none">
                                </td>
                            @endcan
                            <td class="px-4 py-2.5">
                                <div class="flex flex-col leading-snug">
                                    <span class="font-medium">{{ $product->name }}</span>
                                    <span class="text-[11.5px] text-muted2 tabular-nums">{{ $product->sku }} · {{ $product->category?->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-right text-text4 tabular-nums">{{ number_format($product->cost, 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-medium tabular-nums">{{ number_format($product->price, 2) }}</td>
                            <td class="px-4 py-2.5 text-right text-text4 tabular-nums">{{ $product->online_price !== null ? number_format($product->online_price, 2) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-accent font-medium tabular-nums">{{ number_format($product->price - $product->cost, 2) }}</td>
                            <td @class([
                                'px-4 py-2.5 text-right font-semibold tabular-nums',
                                'text-accent' => $status['tone'] === 'accent',
                                'text-caution' => $status['tone'] === 'caution',
                                'text-warn' => $status['tone'] === 'warn',
                                'text-danger' => $status['tone'] === 'danger',
                            ])>{{ $product->stock_display }}</td>
                            @can('edit_products')
                                <td class="px-4 py-2.5 text-center">
                                    <button wire:click.stop="askDelete({{ $product->id }})" title="ลบสินค้านี้"
                                        class="w-[27px] h-[27px] rounded-lg flex items-center justify-center text-muted2 hover:bg-danger-tint hover:text-danger">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                                    </button>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-9 text-muted2">ไม่พบรายการที่ตรงกับตัวกรอง</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($hasMoreRows)
                <div wire:key="load-more-{{ $tablePerPage }}" x-data x-init="
                    const io = new IntersectionObserver((entries) => {
                        if (entries[0].isIntersecting) { $wire.loadMore(); io.disconnect(); }
                    }, { root: $el.closest('.table-scroll-container'), rootMargin: '200px' });
                    io.observe($el);
                " class="py-3.5 text-center text-[12px] text-muted2">กำลังโหลดเพิ่มเติม...</div>
            @endif
        </div>
    @endif

    {{-- Create/Edit modal --}}
    @if ($showForm)
        <div wire:click="closeForm" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[520px] max-h-[92vh] overflow-y-auto bg-surface rounded-2xl shadow-2xl flex flex-col">
                <div class="sticky top-0 z-10 bg-surface rounded-t-2xl flex items-start justify-between gap-3 px-5 pt-5 pb-3 border-b border-hairline2">
                    <span class="text-[17px] font-semibold tracking-tight">{{ $editingId ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' }}</span>
                    <button wire:click="closeForm" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>

                <div class="flex flex-col gap-4 px-5 pb-5">

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">ชื่อสินค้า</label>
                    <input type="text" wire:model="form.name" placeholder="เช่น ไฮโปรไวท์ 510 ไก่แรกเกิด" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                    @error('name') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">รหัส SKU</label>
                        <input type="text" wire:model="form.sku" placeholder="เช่น HP-510" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                        @error('sku') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">ขนาด / บรรจุ</label>
                        <x-combobox field="form.size" :options="$sizeOptions" placeholder="เช่น กระสอบ 30 กก." :free-text="true" :creatable="true" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">ประเภท</label>
                        <x-combobox field="form.category_id" :options="$categoryOptions" placeholder="เลือกหรือพิมพ์ชื่อประเภทใหม่" :creatable="true" create-method="addCategory" />
                        @error('category_id') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">หน่วยนับ</label>
                        <x-combobox field="form.unit_id" :options="$unitOptions" placeholder="เลือกหรือพิมพ์หน่วยใหม่" :creatable="true" create-method="addUnit" />
                        @error('unit_id') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">รายละเอียดสินค้า</label>
                    <textarea wire:model="form.description" rows="3" placeholder="รายละเอียด ส่วนผสม วิธีใช้ ฯลฯ — จะโชว์ในหน้าร้านออนไลน์ด้วย"
                        class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none resize-y"></textarea>
                    @error('description') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="border border-line rounded-[13px] p-3.5 bg-surface2 flex flex-col gap-2.5">
                    <span class="text-xs font-semibold tracking-wide text-muted2">ราคา (บาท)</span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11.5px] text-muted">ต้นทุน</label>
                            <input type="number" step="0.01" wire:model.live="form.cost" class="border border-border3 rounded-lg px-2.5 py-2 text-[13px] bg-surface tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11.5px] text-muted">กำไรสอบ</label>
                            <input type="number" step="0.01" wire:model.live="profitCheck" title="ต้นทุน + กำไรสอบ = ราคาขาย (คำนวณให้อัตโนมัติ)" class="border border-border3 rounded-lg px-2.5 py-2 text-[13px] bg-surface tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11.5px] text-muted">ราคาขาย</label>
                            <input type="number" step="0.01" wire:model="form.price" class="border border-border3 rounded-lg px-2.5 py-2 text-[13px] bg-surface tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[11.5px] text-muted">ราคาออนไลน์</label>
                            <input type="number" step="0.01" wire:model="form.online_price" class="border border-border3 rounded-lg px-2.5 py-2 text-[13px] bg-surface tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                        </div>
                    </div>
                    @error('cost') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    @error('price') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">จำนวนคงเหลือ</label>
                        <input type="number" step="0.01" wire:model="form.stock" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[12.5px] font-medium text-text2">จุดสั่งซื้อขั้นต่ำ</label>
                        <input type="number" step="0.01" wire:model="form.reorder_point" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                    </div>
                </div>

                {{-- Sellable sizes / variants --}}
                <div class="border border-line rounded-[13px] p-3.5 bg-surface2 flex flex-col gap-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold tracking-wide text-muted2">ขนาดที่ขาย (ถ้ามี)</span>
                        <button type="button" wire:click="addVariantRow" class="text-[12px] font-medium text-accent hover:underline">+ เพิ่มขนาด</button>
                    </div>
                    @if (empty($variantRows))
                        <span class="text-[12px] text-muted3">ไม่มีขนาดย่อย — ขายเป็น "{{ $form['size'] ?: 'หน่วยเดียว' }}" ตามปกติ</span>
                    @else
                        <div class="flex flex-col gap-2">
                            @foreach ($variantRows as $i => $row)
                                <div wire:key="variant-row-{{ $i }}"
                                    class="flex flex-col gap-1.5 pb-2 border-b border-hairline2 last:border-0 last:pb-0
                                        sm:grid sm:grid-cols-[1.3fr_0.7fr_0.8fr_0.8fr_auto] sm:items-center sm:border-0 sm:pb-0">
                                    <input type="text" wire:model="variantRows.{{ $i }}.label" placeholder="เช่น กระสอบ 20 กก." class="min-w-0 border border-border3 rounded-lg px-2.5 py-2 text-[12.5px] bg-surface focus:border-accent focus:ring-0 focus:outline-none">
                                    <div class="grid grid-cols-3 gap-1.5 sm:contents">
                                        <input type="number" step="0.001" wire:model="variantRows.{{ $i }}.unit_qty" placeholder="กก./หน่วย" title="กี่หน่วยฐานต่อขนาดนี้" class="min-w-0 border border-border3 rounded-lg px-2 py-2 text-[12.5px] bg-surface tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                                        <input type="number" step="0.01" wire:model="variantRows.{{ $i }}.price" placeholder="ราคา" class="min-w-0 border border-border3 rounded-lg px-2 py-2 text-[12.5px] bg-surface tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                                        <input type="number" step="0.01" wire:model="variantRows.{{ $i }}.online_price" placeholder="ราคาออนไลน์" class="min-w-0 border border-border3 rounded-lg px-2 py-2 text-[12.5px] bg-surface tabular-nums focus:border-accent focus:ring-0 focus:outline-none">
                                    </div>
                                    <button type="button" wire:click="removeVariantRow({{ $i }})" class="self-end sm:self-auto w-8 h-8 rounded-lg flex items-center justify-center text-muted hover:bg-danger-tint hover:text-danger">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <span class="text-[11px] text-muted3">ตัวคูณ = 1 หน่วยขนาดนี้เท่ากับกี่หน่วยฐาน (เช่น กระสอบ 20 กก. → 20)</span>
                        @error('variants.*.label') <span class="text-xs text-danger">กรุณากรอกชื่อขนาดให้ครบ</span> @enderror
                        @error('variants.*.unit_qty') <span class="text-xs text-danger">กรุณากรอกตัวคูณให้ถูกต้อง (มากกว่า 0)</span> @enderror
                        @error('variants.*.price') <span class="text-xs text-danger">กรุณากรอกราคาให้ครบ</span> @enderror
                    @endif
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">รูปสินค้า</label>
                    <input type="file" wire:model="photo" accept="image/*"
                        class="w-full text-[12.5px] text-text3 border border-border3 rounded-lg bg-surface pl-1 pr-3 py-1 cursor-pointer focus:outline-none focus:border-accent
                        file:mr-3 file:rounded-lg file:border-0 file:bg-chip file:text-text2 file:px-3.5 file:py-2 file:text-[12.5px] file:font-medium file:cursor-pointer hover:file:bg-chip2">
                    @error('photo') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="w-20 h-20 object-cover rounded-lg border border-border">
                    @elseif ($existingPhotoPath)
                        <div class="relative w-20 h-20">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($existingPhotoPath) }}" class="w-full h-full object-cover rounded-lg border border-border">
                            <button type="button" wire:click="removePhoto" title="ลบรูปนี้"
                                class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-surface border border-border4 shadow flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                        </div>
                    @endif
                </div>

                <div class="flex gap-2.5">
                    <button wire:click="save" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">{{ $editingId ? 'บันทึกการแก้ไข' : 'เพิ่มสินค้า' }}</button>
                    <button wire:click="closeForm" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Detail drawer --}}
    @if ($detailProduct)
        @php $status = $detailProduct->stock_status; @endphp
        <div wire:click="closeDetail" class="fixed inset-0 bg-black/35 z-[80] flex justify-end">
            <div wire:click.stop class="w-full sm:w-[420px] max-w-full h-full bg-surface shadow-2xl overflow-y-auto flex flex-col">
                <div class="relative h-[190px] bg-sunken shrink-0 flex items-center justify-center">
                    @if ($detailProduct->photo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($detailProduct->photo_path) }}" class="w-full h-full object-cover" alt="{{ $detailProduct->name }}">
                    @else
                        <div class="flex flex-col items-center gap-2 text-muted3 text-[12.5px]">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v14H4zM4 16l5-5 4 4 2-2 5 5M15 9h.01"></path></svg>
                            ยังไม่มีรูปสินค้า
                        </div>
                    @endif
                    <button wire:click="closeDetail" class="fixed top-3 right-3 z-10 w-[30px] h-[30px] rounded-[9px] bg-white/90 shadow flex items-center justify-center text-danger">✕</button>
                </div>

                <div class="p-5 flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-[11.5px] text-muted2 tabular-nums">{{ $detailProduct->sku }} · {{ $detailProduct->category?->name }}</span>
                        <span class="text-[19px] font-semibold tracking-tight leading-snug">{{ $detailProduct->name }}</span>
                        <span class="text-[12.5px] text-muted">{{ $detailProduct->size }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="border border-border rounded-xl p-3 flex flex-col gap-1">
                            <span class="text-[11px] text-muted2">ราคาขาย</span>
                            <span class="text-base font-semibold tabular-nums tracking-tight">{{ number_format($detailProduct->price, 2) }} บาท</span>
                        </div>
                        <div class="border border-border rounded-xl p-3 flex flex-col gap-1">
                            <span class="text-[11px] text-muted2">ราคาออนไลน์</span>
                            <span class="text-base font-semibold tabular-nums tracking-tight">{{ $detailProduct->online_price !== null ? number_format($detailProduct->online_price, 2).' บาท' : '—' }}</span>
                        </div>
                    </div>

                    <div class="border border-border rounded-xl overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-b border-hairline2 text-[13px]">
                            <span class="text-muted">ต้นทุน</span><span class="font-medium tabular-nums">{{ number_format($detailProduct->cost, 2) }} บาท</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-b border-hairline2 text-[13px]">
                            <span class="text-muted">กำไรต่อหน่วย</span><span class="font-medium tabular-nums text-accent">{{ number_format($detailProduct->price - $detailProduct->cost, 2) }} บาท</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-b border-hairline2 text-[13px]">
                            <span class="text-muted">คงเหลือ</span>
                            <span @class(['font-medium tabular-nums', 'text-accent' => $status['tone']==='accent', 'text-caution' => $status['tone']==='caution', 'text-warn' => $status['tone']==='warn', 'text-danger' => $status['tone']==='danger'])>{{ $detailProduct->stock_display }} {{ $detailProduct->unit?->name }} · {{ $status['label'] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 text-[13px]">
                            <span class="text-muted">จุดสั่งซื้อขั้นต่ำ</span><span class="font-medium tabular-nums">{{ $detailProduct->reorder_point_display }}</span>
                        </div>
                    </div>

                    @if ($detailProduct->variants->isNotEmpty())
                        <div class="border border-border rounded-xl overflow-hidden">
                            <div class="px-3.5 py-2 border-b border-hairline2 text-[11px] font-semibold tracking-wide text-muted2">ขนาดที่ขาย</div>
                            @foreach ($detailProduct->variants as $variant)
                                <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 text-[13px] {{ ! $loop->last ? 'border-b border-hairline2' : '' }}">
                                    <span class="text-text2">{{ $variant->label }}</span>
                                    <span class="font-medium tabular-nums">{{ number_format((float) $variant->price, 2) }} บาท</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($detailProduct->description)
                        <div class="flex flex-col gap-1.5">
                            <span class="text-[11px] font-semibold tracking-wide text-muted2">รายละเอียดสินค้า</span>
                            <p class="text-[13px] text-text2 leading-relaxed whitespace-pre-line">{{ $detailProduct->description }}</p>
                        </div>
                    @endif

                    <div class="flex gap-2.5">
                        @can('edit_products')
                            <button wire:click="openEdit({{ $detailProduct->id }})" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:border-accent hover:text-accent">แก้ไข</button>
                            <button wire:click="askDelete({{ $detailProduct->id }})" title="ลบสินค้านี้" class="w-11 shrink-0 rounded-[10px] border border-border4 text-muted flex items-center justify-center hover:border-danger hover:bg-danger-tint hover:text-danger">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirm --}}
    @if ($deleteProduct)
        <div wire:click="cancelDelete" class="fixed inset-0 bg-black/45 z-[92] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ลบ &ldquo;{{ $deleteProduct->name }}&rdquo;?</span>
                        <span class="text-[12.5px] text-muted">ข้อมูลสินค้าและประวัติราคาจะถูกซ่อนจากระบบ</span>
                        <span class="text-[12.5px] text-warn">การกระทำนี้ไม่สามารถย้อนกลับได้ทันที</span>
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="cancelDelete" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ยกเลิก</button>
                    <button wire:click="delete" class="flex-1 py-2.5 rounded-[10px] bg-danger text-white text-[13px] font-medium hover:bg-danger-ink2">ลบสินค้า</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk category change --}}
    @if ($showBulkCategoryModal)
        <div wire:click="closeBulkCategoryModal" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex flex-col gap-1">
                    <span class="text-[16px] font-semibold">เปลี่ยนประเภทสินค้า</span>
                    <span class="text-[12.5px] text-muted2">เปลี่ยนประเภทของ {{ count($selectedIds) }} รายการที่เลือกพร้อมกัน</span>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">ประเภทใหม่</label>
                    <x-combobox field="bulkCategoryId" :options="$categoryOptions" placeholder="เลือกประเภท" />
                    @error('category_id') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="applyBulkCategory" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">บันทึก</button>
                    <button wire:click="closeBulkCategoryModal" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk delete confirm --}}
    @if ($confirmBulkDelete)
        <div wire:click="cancelBulkDelete" class="fixed inset-0 bg-black/45 z-[92] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ลบ {{ count($selectedIds) }} รายการที่เลือก?</span>
                        <span class="text-[12.5px] text-muted">ข้อมูลสินค้าและประวัติราคาจะถูกซ่อนจากระบบ</span>
                        <span class="text-[12.5px] text-warn">การกระทำนี้ไม่สามารถย้อนกลับได้ทันที</span>
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="cancelBulkDelete" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ยกเลิก</button>
                    <button wire:click="bulkDelete" class="flex-1 py-2.5 rounded-[10px] bg-danger text-white text-[13px] font-medium hover:bg-danger-ink2">ลบทั้งหมด</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Import from Excel --}}
    @if ($showImportModal)
        <div wire:click="closeImportModal" class="fixed inset-0 bg-black/40 z-[85] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[760px] max-h-[88vh] bg-surface rounded-2xl shadow-2xl flex flex-col overflow-hidden">
                <div class="p-5 border-b border-line flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-0.5 leading-relaxed">
                        <span class="text-[16px] font-semibold">นำเข้าสินค้าจาก Excel</span>
                        <span class="text-[12.5px] text-muted2">อัปโหลดไฟล์ .xlsx ตามรูปแบบ Template — ถ้ารหัส SKU มีอยู่แล้วจะอัปเดตข้อมูลทับ ถ้าไม่มีจะสร้างสินค้าใหม่ (ไม่แตะยอดคงเหลือ)</span>
                    </div>
                    <button wire:click="closeImportModal" class="text-danger hover:opacity-70 text-[19px] leading-none shrink-0">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto p-5 flex flex-col gap-4">

                    <div>
                        <input type="file" wire:model="importFile" accept=".xlsx,.xls"
                            class="w-full text-[12.5px] text-text3 border border-border3 rounded-lg bg-surface pl-1 pr-3 py-1 cursor-pointer focus:outline-none focus:border-accent
                            file:mr-3 file:rounded-lg file:border-0 file:bg-chip file:text-text2 file:px-3.5 file:py-2 file:text-[12.5px] file:font-medium file:cursor-pointer hover:file:bg-chip2">
                        <div wire:loading wire:target="importFile" class="text-[12px] text-muted2 mt-1.5">กำลังอ่านไฟล์...</div>
                    </div>

                    @if ($importFileError)
                        <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3.5 py-2.5">{{ $importFileError }}</span>
                    @endif

                    @if ($importRows !== null)
                        @php
                            $createCount = collect($importRows)->where('action', 'create')->count();
                            $updateCount = collect($importRows)->where('action', 'update')->count();
                        @endphp
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center gap-2 bg-accent-tint text-accent-ink rounded-lg px-3.5 py-2.5 text-[12.5px] font-medium">
                                พบ {{ count($importRows) }} รายการที่ถูกต้อง — สร้างใหม่ {{ $createCount }} รายการ, อัปเดต {{ $updateCount }} รายการ
                            </div>

                            @if (! empty($importErrors))
                                <div class="bg-danger-tint rounded-lg px-3.5 py-2.5 flex flex-col gap-1">
                                    <span class="text-[12.5px] font-medium text-danger">ข้าม/เตือน {{ count($importErrors) }} รายการ:</span>
                                    <ul class="text-[11.5px] text-danger-ink2 list-disc list-inside flex flex-col gap-0.5 max-h-[100px] overflow-y-auto">
                                        @foreach ($importErrors as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (count($importRows) > 0)
                                <div class="border border-border rounded-[10px] overflow-auto max-h-[280px]">
                                    <table class="w-full text-[12px] whitespace-nowrap">
                                        <thead>
                                            <tr class="sticky top-0 z-10 bg-surface2 border-b border-line text-[10.5px] font-semibold tracking-wide text-muted2">
                                                <th class="text-left px-3 py-2">SKU</th>
                                                <th class="text-left px-3 py-2">ชื่อสินค้า</th>
                                                <th class="text-left px-3 py-2">ขนาด</th>
                                                <th class="text-left px-3 py-2">ประเภท</th>
                                                <th class="text-left px-3 py-2">หน่วย</th>
                                                <th class="text-right px-3 py-2">ต้นทุน</th>
                                                <th class="text-right px-3 py-2">ราคาขาย</th>
                                                <th class="text-right px-3 py-2">ออนไลน์</th>
                                                <th class="text-right px-3 py-2">สั่งซื้อขั้นต่ำ</th>
                                                <th class="text-center px-3 py-2">สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-hairline2">
                                            @foreach ($importRows as $row)
                                                <tr class="hover:bg-surface2">
                                                    <td class="px-3 py-2 font-medium tabular-nums">{{ $row['sku'] }}</td>
                                                    <td class="px-3 py-2 max-w-[170px] truncate">{{ $row['name'] }}</td>
                                                    <td class="px-3 py-2 text-muted2">{{ $row['size'] ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-muted2">{{ $row['category_name'] }}</td>
                                                    <td class="px-3 py-2 text-muted2">{{ $row['unit_name'] }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row['cost'], 2) }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row['price'], 2) }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums">{{ $row['online_price'] !== null ? number_format($row['online_price'], 2) : '—' }}</td>
                                                    <td class="px-3 py-2 text-right tabular-nums">{{ $row['reorder_point'] }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        <span @class([
                                                            'text-[10.5px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap',
                                                            'bg-accent-tint text-accent' => $row['action'] === 'create',
                                                            'bg-warn-tint text-warn' => $row['action'] === 'update',
                                                        ])>{{ $row['action'] === 'create' ? 'สร้างใหม่' : 'อัปเดต' }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex gap-2.5 p-5 pt-0">
                    @if ($importRows !== null && count($importRows) > 0)
                        <button wire:click="confirmImport" wire:loading.attr="disabled" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover disabled:opacity-50">ยืนยันนำเข้า {{ count($importRows) }} รายการ</button>
                    @endif
                    <button wire:click="closeImportModal" class="px-5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
            </div>
        </div>
    @endif
</div>
