@props([
    'field',
    'options' => [],
    'placeholder' => 'เลือก...',
    'live' => false,
    'creatable' => false,
    'createMethod' => null,
    'freeText' => false,
])

<div class="relative"
    x-data="{
        id: Math.random().toString(36).slice(2),
        value: $wire.entangle('{{ $field }}'){{ $live ? '.live' : '' }},
        open: false,
        search: '',
        posTop: 0,
        posLeft: 0,
        posWidth: 0,
        options: @js($options),
        get confirmedLabel() {
            const match = this.options.find(o => o.value === this.value);
            if (match) return match.label;
            return {{ $freeText ? 'this.value || \'\'' : "''" }};
        },
        get filtered() {
            if (this.search === '') return this.options;
            const s = this.search.toLowerCase();
            return this.options.filter(o => String(o.label).toLowerCase().includes(s));
        },
        get exactMatch() {
            if (this.search.trim() === '') return null;
            const s = this.search.trim().toLowerCase();
            return this.options.find(o => String(o.label).toLowerCase() === s) || null;
        },
        get canCreate() {
            return {{ $creatable ? 'true' : 'false' }} && this.search.trim() !== '' && !this.exactMatch;
        },
        reposition() {
            const rect = this.$refs.input.getBoundingClientRect();
            this.posTop = rect.bottom + 4;
            this.posLeft = rect.left;
            this.posWidth = rect.width;
        },
        openList() {
            this.search = this.confirmedLabel;
            this.reposition();
            this.open = true;
            window.dispatchEvent(new CustomEvent('combobox-open', { detail: this.id }));
            this.$nextTick(() => this.$refs.input.select());
        },
        select(opt) {
            this.value = opt.value;
            this.search = '';
            this.open = false;
        },
        async createNew() {
            const name = this.search.trim();
            if (name === '') return;
            const idStr = {{ $createMethod ? "String(await \$wire.call('{$createMethod}', name))" : 'name' }};
            const existing = this.options.find(o => o.value === idStr);
            if (existing) {
                existing.label = name;
            } else {
                this.options.push({ value: idStr, label: name });
            }
            this.value = idStr;
            this.search = name;
            this.open = false;
        },
        closeAndRevert() {
            this.search = this.confirmedLabel;
            this.open = false;
        },
    }"
    x-init="
        search = confirmedLabel;
        $watch('value', () => { if (!open) search = confirmedLabel; });
        document.addEventListener('click', (e) => {
            if (open && !$refs.input.contains(e.target) && !$refs.panel.contains(e.target)) {
                closeAndRevert();
            }
        }, true);
    "
    @combobox-open.window="if ($event.detail !== id) closeAndRevert()"
    @scroll.window.capture="if (open && ($event.target === document || $event.target === window)) closeAndRevert()">
    <input type="text" x-ref="input" x-model="search" @focus="openList()"
        @input="open = true; reposition();"
        placeholder="{{ $placeholder }}" autocomplete="off"
        class="w-full border border-border3 rounded-lg px-2.5 py-2 text-[13px] bg-surface focus:border-accent focus:ring-0 focus:outline-none">

    <div x-ref="panel" x-show="open" x-cloak
        :style="`top:${posTop}px; left:${posLeft}px; width:${posWidth}px;`"
        class="fixed bg-surface border border-border2 rounded-xl shadow-lg z-[95] max-h-[240px] overflow-y-auto">
        <template x-if="canCreate">
            <button type="button" @click="createNew()"
                class="w-full flex items-center gap-1.5 text-left px-3 py-2 text-[13px] text-accent font-medium hover:bg-accent-tint border-b border-hairline">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>
                <span>เพิ่ม "<span x-text="search.trim()"></span>" เป็นตัวเลือกใหม่</span>
            </button>
        </template>
        <template x-if="filtered.length === 0 && !canCreate">
            <div class="px-3 py-2.5 text-[12.5px] text-muted2 text-center">ไม่พบข้อมูล</div>
        </template>
        <template x-for="opt in filtered" :key="opt.value">
            <button type="button" @click="select(opt)"
                class="w-full text-left px-3 py-2 text-[13px] hover:bg-surface4"
                :class="opt.value === value ? 'text-accent font-medium' : ''"
                x-text="opt.label"></button>
        </template>
    </div>
</div>
