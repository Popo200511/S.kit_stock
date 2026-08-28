@props([
    'field',
    'options' => [],
    'selected' => null,
    'applyMethod' => 'applyColumnFilter',
    'clearMethod' => 'clearColumnFilter',
    'clearAllMethod' => 'clearFilters',
    'sortMethod' => null,
    'align' => 'left',
])

@php
    $isActive = $selected !== null;
    $initialSelected = $selected ?? collect($options)->pluck('value')->all();
@endphp

<div {{ $attributes->class('relative inline-block') }}
    x-data="{
        id: Math.random().toString(36).slice(2),
        open: false,
        posTop: 0,
        posLeft: 0,
        search: '',
        sortDir: null,
        options: @js($options),
        selected: @js($initialSelected),
        get sortedOptions() {
            let list = [...this.options];
            if (this.sortDir === 'asc') list.sort((a, b) => String(a.label).localeCompare(String(b.label), 'th', { numeric: true, sensitivity: 'base' }));
            if (this.sortDir === 'desc') list.sort((a, b) => String(b.label).localeCompare(String(a.label), 'th', { numeric: true, sensitivity: 'base' }));
            return list;
        },
        get visibleOptions() {
            if (this.search === '') return this.sortedOptions;
            const s = this.search.toLowerCase();
            return this.sortedOptions.filter(o => String(o.label).toLowerCase().includes(s));
        },
        isChecked(v) { return this.selected.includes(v); },
        toggleValue(v) { this.selected = this.isChecked(v) ? this.selected.filter(x => x !== v) : [...this.selected, v]; },
        selectAllVisible() { const vals = this.visibleOptions.map(o => o.value); this.selected = [...new Set([...this.selected, ...vals])]; },
        deselectAllVisible() { const vals = this.visibleOptions.map(o => o.value); this.selected = this.selected.filter(v => !vals.includes(v)); },
        reposition() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const panelWidth = 250;
            const estHeight = 420;
            let left = {{ $align === 'right' ? 'true' : 'false' }} ? (rect.right - panelWidth) : rect.left;
            left = Math.max(8, Math.min(left, window.innerWidth - panelWidth - 8));
            let top = rect.bottom + 6;
            if (top + estHeight > window.innerHeight) {
                top = Math.max(8, rect.top - estHeight - 6);
            }
            this.posLeft = left;
            this.posTop = top;
        },
        openPopover() {
            this.selected = @js($initialSelected);
            this.search = '';
            this.sortDir = null;
            this.reposition();
            this.open = true;
            window.dispatchEvent(new CustomEvent('excel-filter-open', { detail: this.id }));
        },
        applySort(dir) {
            this.sortDir = dir;
            {{ $sortMethod ? "\$wire.call('{$sortMethod}', '{$field}', dir);" : '' }}
        },
        applyFilter() { $wire.call('{{ $applyMethod }}', '{{ $field }}', this.selected); this.open = false; },
        clearThis() { this.selected = this.options.map(o => o.value); $wire.call('{{ $clearMethod }}', '{{ $field }}'); this.open = false; },
        clearAll() { this.selected = this.options.map(o => o.value); $wire.call('{{ $clearAllMethod }}'); this.open = false; },
        cancelPopover() { this.open = false; },
    }"
    @excel-filter-open.window="if ($event.detail !== id) open = false"
    @scroll.window.capture="if (open && ($event.target === document || $event.target === window)) open = false">
    <button type="button" x-ref="trigger" @click.stop="open ? cancelPopover() : openPopover()" title="กรองคอลัมน์นี้"
        class="inline-flex ml-1 {{ $isActive ? 'text-accent' : 'text-muted3' }} hover:text-accent">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.9"><path d="M3 5h18l-7 8v6l-4-2v-4z"></path></svg>
    </button>

    <div x-ref="panel" x-show="open" x-cloak @click.outside="open = false" @click.stop
        :style="`top:${posTop}px; left:${posLeft}px;`"
        class="fixed w-[250px] bg-surface border border-border2 rounded-xl shadow-lg z-40 text-left normal-case font-normal overflow-hidden">
        <div class="flex flex-col">
            <button type="button" @click="clearThis()" class="flex items-center gap-2 px-3.5 py-2.5 text-[12.5px] text-text2 hover:bg-surface4 border-b border-hairline">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h18l-7 9v6l-4-2v-4z"></path><path d="M4 20L20 4" stroke-width="1.4"></path></svg>
                ล้างตัวกรองคอลัมน์นี้
            </button>
            <button type="button" @click="clearAll()" class="flex items-center gap-2 px-3.5 py-2.5 text-[12.5px] text-text2 hover:bg-surface4 border-b border-hairline2">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"></path></svg>
                ล้างตัวกรองทั้งหมด
            </button>
        </div>

        <div class="grid grid-cols-2 gap-1.5 p-2.5">
            <button type="button" @click="selectAllVisible()" class="py-1.5 rounded-lg bg-accent-tint text-accent text-[11.5px] font-medium hover:opacity-80">เลือกทั้งหมด</button>
            <button type="button" @click="deselectAllVisible()" class="py-1.5 rounded-lg bg-danger-tint text-danger text-[11.5px] font-medium hover:opacity-80">ไม่เลือกเลย</button>
            <button type="button" @click="applySort('asc')" class="py-1.5 rounded-lg bg-chip text-text3 text-[11.5px] font-medium hover:opacity-80">เรียง ก→ฮ</button>
            <button type="button" @click="applySort('desc')" class="py-1.5 rounded-lg bg-chip text-text3 text-[11.5px] font-medium hover:opacity-80">เรียง ฮ→ก</button>
        </div>

        <div class="px-2.5 pb-2">
            <div class="flex items-center gap-1.5 bg-sunken border border-border3 rounded-lg px-2.5 py-1.5 focus-within:border-accent">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-muted3 shrink-0" stroke-width="2" stroke-linecap="round"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3"></path></svg>
                <input type="text" x-model="search" placeholder="ค้นหา" class="flex-1 min-w-0 text-[12px] border-0 p-0 focus:ring-0 focus:outline-none bg-transparent">
            </div>
        </div>

        <div class="max-h-[190px] overflow-y-auto border-t border-hairline">
            <template x-if="visibleOptions.length === 0">
                <div class="px-3.5 py-3 text-[12px] text-muted2 text-center">ไม่พบข้อมูล</div>
            </template>
            <template x-for="opt in visibleOptions" :key="opt.value">
                <label class="flex items-center gap-2 px-3.5 py-1.5 text-[12.5px] hover:bg-surface4" :class="isChecked(opt.value) ? '' : 'text-muted2'">
                    <input type="checkbox" :checked="isChecked(opt.value)" @change="toggleValue(opt.value)" class="rounded border-border3 text-accent focus:ring-0 focus:outline-none">
                    <span class="truncate" x-text="opt.label"></span>
                </label>
            </template>
        </div>

        <div class="flex gap-2 p-2.5 border-t border-line">
            <button type="button" @click="applyFilter()" :disabled="selected.length === 0" title="ต้องเลือกอย่างน้อย 1 รายการ" class="flex-1 py-2 rounded-lg bg-accent text-white text-[12.5px] font-medium hover:bg-accent-hover disabled:opacity-40 disabled:pointer-events-none">ตกลง</button>
            <button type="button" @click="cancelPopover()" class="flex-1 py-2 rounded-lg border border-border4 text-text2 text-[12.5px] font-medium hover:bg-hairline">ยกเลิก</button>
        </div>
    </div>
</div>
