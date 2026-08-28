<div class="flex flex-col gap-3.5">

    @if ($listError)
        <div class="px-4 py-2.5 rounded-[10px] bg-danger-tint text-danger text-[13px]">{{ $listError }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-surface border border-border rounded-[14px] p-4 shadow-sm flex flex-col gap-1">
            <span class="text-xs text-muted font-medium">ผู้ใช้ทั้งหมด</span>
            <span class="text-[22px] font-semibold tracking-tight tabular-nums">{{ $stats['total'] }} คน</span>
        </div>
        <div class="bg-surface border border-border rounded-[14px] p-4 shadow-sm flex flex-col gap-1">
            <span class="text-xs text-muted font-medium">สิทธิ์เต็ม</span>
            <span class="text-[22px] font-semibold tracking-tight tabular-nums">{{ $stats['owners'] }} คน</span>
            <span class="text-[11.5px] text-muted2">เจ้าของร้าน เข้าถึงได้ทุกเมนู</span>
        </div>
        <div class="bg-surface border border-border rounded-[14px] p-4 shadow-sm flex flex-col gap-1">
            <span class="text-xs text-muted font-medium">ระงับการใช้งาน</span>
            <span class="text-[22px] font-semibold tracking-tight tabular-nums">{{ $stats['suspended'] }} คน</span>
            <span class="text-[11.5px] text-muted2">เข้าสู่ระบบไม่ได้ชั่วคราว</span>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap gap-2.5 items-center">
        <div class="flex-1 min-w-[180px] flex items-center gap-2 bg-surface border border-border2 rounded-[10px] px-3 py-2 shadow-sm focus-within:border-accent">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-muted3" stroke-width="1.9" stroke-linecap="round"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3"></path></svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="ค้นหาชื่อผู้ใช้ หรืออีเมล" class="flex-1 min-w-0 text-[13px] border-0 p-0 focus:ring-0 focus:outline-none bg-transparent">
        </div>
        <div class="flex flex-wrap gap-1 bg-chip p-[3px] rounded-[9px]">
            <button wire:click="setRoleFilter('all')" class="px-3 py-1.5 rounded-[7px] text-[12.5px] font-medium {{ $roleFilter === 'all' ? 'bg-surface shadow-sm' : 'text-muted2' }}">ทั้งหมด</button>
            @foreach ($roles as $role)
                <button wire:click="setRoleFilter('{{ $role->value }}')" class="px-3 py-1.5 rounded-[7px] text-[12.5px] font-medium whitespace-nowrap {{ $roleFilter === $role->value ? 'bg-surface shadow-sm' : 'text-muted2' }}">{{ $role->label() }}</button>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-1 bg-chip p-[3px] rounded-[9px]">
            <button wire:click="setStatusFilter('all')" class="px-3 py-1.5 rounded-[7px] text-[12.5px] font-medium {{ $statusFilter === 'all' ? 'bg-surface shadow-sm' : 'text-muted2' }}">ทุกสถานะ</button>
            <button wire:click="setStatusFilter('active')" class="px-3 py-1.5 rounded-[7px] text-[12.5px] font-medium whitespace-nowrap {{ $statusFilter === 'active' ? 'bg-surface shadow-sm' : 'text-muted2' }}">ใช้งานอยู่</button>
            <button wire:click="setStatusFilter('suspended')" class="px-3 py-1.5 rounded-[7px] text-[12.5px] font-medium whitespace-nowrap {{ $statusFilter === 'suspended' ? 'bg-surface shadow-sm' : 'text-muted2' }}">ระงับการใช้งาน</button>
        </div>
        @can('manage_users')
            <button wire:click="openCreate" class="flex items-center gap-1.5 px-4 py-2.5 rounded-[10px] bg-accent text-white text-[13px] font-medium hover:bg-accent-hover">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>เพิ่มผู้ใช้
            </button>
        @endcan
    </div>

    {{-- User cards --}}
    @forelse ($users as $u)
        <div class="bg-surface border border-border rounded-[15px] p-4.5 shadow-sm flex flex-col gap-3.5">
            <div class="flex flex-wrap gap-3.5 items-center">
                <span class="w-[39px] h-[39px] shrink-0 rounded-full bg-accent-tint text-accent flex items-center justify-center text-sm font-semibold">
                    {{ \Illuminate\Support\Str::of($u->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('') }}
                </span>
                <div class="flex-1 min-w-[150px] flex flex-col leading-snug">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-semibold">{{ $u->name }}</span>
                        <span @class(['text-[10.5px] font-medium px-2 py-0.5 rounded-full', 'bg-accent-tint text-accent' => $u->active, 'bg-warn-tint text-warn' => ! $u->active])>{{ $u->active ? 'ใช้งานอยู่' : 'ระงับการใช้งาน' }}</span>
                    </div>
                    <span class="text-xs text-muted2 truncate">{{ $u->email }} · เข้าใช้ล่าสุด {{ $u->last_login_at?->diffForHumans() ?? 'ยังไม่เคยเข้าใช้' }}</span>
                </div>
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-accent-tint text-accent whitespace-nowrap">{{ $u->role->label() }}</span>
                @can('manage_users')
                    <div class="flex gap-1.5">
                        <button wire:click="openEdit({{ $u->id }})" title="แก้ไขผู้ใช้" class="w-[30px] h-[30px] rounded-lg border border-border4 text-text4 flex items-center justify-center hover:border-accent hover:text-accent">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>
                        </button>
                        <button wire:click="toggleActive({{ $u->id }})" title="{{ $u->active ? 'ระงับการใช้งาน' : 'เปิดใช้งาน' }}" class="w-[30px] h-[30px] rounded-lg border border-border4 text-danger flex items-center justify-center hover:border-danger hover:bg-danger-tint">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                @if ($u->active)
                                    <path d="M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4"></path>
                                @else
                                    <path d="M5 11h14v10H5zM8 11V7a4 4 0 0 1 7.75-3.9"></path>
                                @endif
                            </svg>
                        </button>
                        <button wire:click="askDelete({{ $u->id }})" title="{{ $u->isOwner() ? 'ลบเจ้าของร้านไม่ได้' : 'ลบผู้ใช้' }}"
                            @class(['w-[30px] h-[30px] rounded-lg border border-border4 flex items-center justify-center', 'text-muted3 opacity-40 cursor-not-allowed' => $u->isOwner(), 'text-text4 hover:border-danger hover:text-danger' => ! $u->isOwner()])
                            @disabled($u->isOwner())>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"></path></svg>
                        </button>
                    </div>
                @endcan
            </div>
            <div class="flex flex-wrap gap-1.5 border-t border-line pt-3.5">
                @foreach ($permissions as $perm)
                    @php $on = in_array($perm->value, $u->permissions ?? [], true); @endphp
                    @can('manage_users')
                        <button wire:click="toggleQuickPerm({{ $u->id }}, '{{ $perm->value }}')"
                            @class(['flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg border', 'border-accent-border bg-accent-tint text-accent-ink' => $on, 'border-border4 bg-surface text-muted' => ! $on])>
                            <span class="text-[10px]">{{ $on ? '✓' : '' }}</span>{{ $perm->label() }}
                        </button>
                    @else
                        <span @class(['flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg border', 'border-accent-border bg-accent-tint text-accent-ink' => $on, 'border-border4 bg-surface text-muted' => ! $on])>
                            <span class="text-[10px]">{{ $on ? '✓' : '' }}</span>{{ $perm->label() }}
                        </span>
                    @endcan
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-surface border border-border rounded-[15px] p-10 text-center text-sm text-muted2">ไม่พบผู้ใช้ที่ตรงกับเงื่อนไข</div>
    @endforelse

    <span class="text-xs text-muted2">แตะที่สิทธิ์เพื่อเปิด/ปิดการเข้าถึงของผู้ใช้แต่ละคน · เจ้าของร้านลบไม่ได้</span>

    {{-- Create/Edit modal --}}
    @if ($showForm)
        <div wire:click="closeForm" class="fixed inset-0 bg-black/40 z-[88] flex items-center justify-center p-3.5">
            <div wire:click.stop class="w-full max-w-[452px] max-h-[92vh] overflow-y-auto bg-surface rounded-2xl shadow-2xl flex flex-col">
                <div class="sticky top-0 z-10 bg-surface rounded-t-2xl flex items-start justify-between gap-3 px-5 pt-5 pb-3 border-b border-hairline2">
                    <span class="text-[17px] font-semibold tracking-tight">{{ $editingId ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่' }}</span>
                    <button wire:click="closeForm" class="w-[29px] h-[29px] rounded-lg flex items-center justify-center text-danger hover:bg-danger-tint">✕</button>
                </div>

                <div class="flex flex-col gap-4 px-5 pb-5">

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">ชื่อ-นามสกุล</label>
                    <input type="text" wire:model="form.name" placeholder="เช่น ปรียา ศรีทอง" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">อีเมล</label>
                    <input type="email" wire:model="form.email" placeholder="name@rungrueang.co.th" class="border border-border3 rounded-[10px] px-3 py-2.5 text-[13.5px] focus:border-accent focus:ring-0 focus:outline-none">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">{{ $editingId ? 'รหัสผ่านใหม่ (ถ้าต้องการเปลี่ยน)' : 'รหัสผ่าน' }}</label>
                    <div class="flex items-center gap-2 border border-border3 rounded-[10px] pl-3 pr-2.5 focus-within:border-accent" x-data="{ show: @entangle('showPassword') }">
                        <input :type="show ? 'text' : 'password'" wire:model="form.password" placeholder="อย่างน้อย 4 ตัวอักษร" class="flex-1 min-w-0 py-2.5 text-[13.5px] border-0 focus:ring-0 focus:outline-none bg-transparent">
                        <button type="button" wire:click="$toggle('showPassword')" class="w-[26px] h-[26px] rounded-md flex items-center justify-center text-muted2 hover:bg-hairline">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    <span class="text-[11.5px] text-muted2">{{ $editingId ? 'กรอกเฉพาะเมื่อต้องการตั้งรหัสผ่านใหม่ให้ผู้ใช้นี้' : 'แจ้งรหัสนี้ให้ผู้ใช้เพื่อเข้าสู่ระบบครั้งแรก' }}</span>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[12.5px] font-medium text-text2">ตำแหน่ง</label>
                    <div class="grid grid-cols-2 gap-1.5 bg-chip p-[3px] rounded-[9px]">
                        @foreach ($roles as $role)
                            <button type="button" wire:click="setFormRole('{{ $role->value }}')" class="text-center py-2 rounded-[7px] text-[12.5px] font-medium {{ $form['role'] === $role->value ? 'bg-surface shadow-sm' : 'text-muted2' }}">{{ $role->label() }}</button>
                        @endforeach
                    </div>
                    <span class="text-[11.5px] text-muted2 leading-relaxed">{{ \App\Enums\UserRole::from($form['role'])->hint() }}</span>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-[12.5px] font-medium text-text2">สิทธิ์การใช้งาน</label>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($permissions as $perm)
                            @php $on = in_array($perm->value, $form['perms'], true); @endphp
                            <button type="button" wire:click="togglePerm('{{ $perm->value }}')"
                                @class(['flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg border', 'border-accent-border bg-accent-tint text-accent-ink' => $on, 'border-border4 bg-surface text-muted' => ! $on])>
                                <span class="w-[13px] h-[13px] rounded border flex items-center justify-center text-[9px] font-bold text-white {{ $on ? 'bg-accent border-accent' : 'border-border4' }}">{{ $on ? '✓' : '' }}</span>{{ $perm->label() }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if ($formError)
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3 py-2.5">{{ $formError }}</span>
                @endif

                <div class="flex gap-2.5">
                    <button wire:click="save" class="flex-1 py-2.5 rounded-[10px] bg-accent text-white text-[13.5px] font-medium hover:bg-accent-hover">{{ $editingId ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้' }}</button>
                    <button wire:click="closeForm" class="px-4.5 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-hairline">ยกเลิก</button>
                </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete confirm --}}
    @if ($deleteUser)
        <div wire:click="cancelDelete" class="fixed inset-0 bg-black/45 z-[95] flex items-center justify-center p-4">
            <div wire:click.stop class="w-full max-w-[380px] bg-surface rounded-2xl shadow-2xl p-5 flex flex-col gap-4">
                <div class="flex gap-3 items-start">
                    <span class="w-[34px] h-[34px] shrink-0 rounded-[10px] bg-danger-tint text-danger flex items-center justify-center">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"></path></svg>
                    </span>
                    <div class="flex flex-col gap-1 leading-relaxed">
                        <span class="text-[15px] font-semibold">ลบผู้ใช้ &ldquo;{{ $deleteUser->name }}&rdquo;?</span>
                        <span class="text-[12.5px] text-muted">บัญชีนี้จะเข้าสู่ระบบไม่ได้อีกต่อไป</span>
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <button wire:click="cancelDelete" class="flex-1 py-2.5 rounded-[10px] border border-border4 text-text2 text-[13px] font-medium hover:bg-sunken">ยกเลิก</button>
                    <button wire:click="delete" class="flex-1 py-2.5 rounded-[10px] bg-danger text-white text-[13px] font-medium hover:bg-danger-ink2">ลบผู้ใช้</button>
                </div>
            </div>
        </div>
    @endif
</div>
