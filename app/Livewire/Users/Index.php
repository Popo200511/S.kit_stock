<?php

namespace App\Livewire\Users;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public string $search = '';

    public string $roleFilter = 'all';

    /** 'all' | 'active' | 'suspended' */
    public string $statusFilter = 'all';

    // Form
    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = ['name' => '', 'email' => '', 'password' => '', 'role' => 'sales_staff', 'perms' => []];

    public bool $showPassword = false;

    public ?string $formError = null;

    // Delete confirm
    public ?int $confirmDeleteId = null;

    // Guard-rail feedback (self/owner protections) — a plain property, not
    // session flash: Livewire's AJAX response only re-renders this component,
    // not the outer layout, so a flash read there would never show up.
    public ?string $listError = null;

    public function setRoleFilter(string $role): void
    {
        $this->roleFilter = $role;
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $this->editingId = null;
        $this->form = [
            'name' => '', 'email' => '', 'password' => '',
            'role' => UserRole::SalesStaff->value,
            'perms' => UserRole::SalesStaff->defaultPermissions(),
        ];
        $this->formError = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $user->role->value,
            'perms' => $user->permissions ?? [],
        ];
        $this->formError = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function setFormRole(string $role): void
    {
        $this->form['role'] = $role;
        $this->form['perms'] = UserRole::from($role)->defaultPermissions();
    }

    public function togglePerm(string $perm): void
    {
        $this->form['perms'] = in_array($perm, $this->form['perms'], true)
            ? array_values(array_diff($this->form['perms'], [$perm]))
            : [...$this->form['perms'], $perm];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.($this->editingId ?? 'NULL').',id',
            'role' => 'required|in:'.implode(',', array_map(fn ($r) => $r->value, UserRole::cases())),
        ];

        $validator = Validator::make($this->form, $rules);
        if ($validator->fails()) {
            $this->formError = $validator->errors()->first();

            return;
        }

        if (! $this->editingId && $this->form['password'] === '') {
            $this->formError = 'กรอกรหัสผ่านสำหรับผู้ใช้ใหม่';

            return;
        }

        if ($this->form['password'] !== '' && strlen($this->form['password']) < 4) {
            $this->formError = 'รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร';

            return;
        }

        if (empty($this->form['perms'])) {
            $this->formError = 'เลือกสิทธิ์อย่างน้อย 1 รายการ';

            return;
        }

        // Mirrors the protection delete() already has: whoever holds manage_users
        // could otherwise demote the owner's role or strip their manage_users
        // permission here, locking out the real owner (or handing themselves
        // permanent control by editing the owner's account instead of their own).
        if ($this->editingId) {
            $target = User::findOrFail($this->editingId);
            if ($target->isOwner() && $this->form['role'] !== UserRole::Owner->value) {
                $this->formError = 'เปลี่ยนตำแหน่งเจ้าของร้านไม่ได้';

                return;
            }
            if ($target->isOwner() && ! in_array(Permission::ManageUsers->value, $this->form['perms'], true)) {
                $this->formError = 'ถอดสิทธิ์จัดการผู้ใช้ออกจากเจ้าของร้านไม่ได้';

                return;
            }
        }

        $data = [
            'name' => trim($this->form['name']),
            'email' => trim($this->form['email']),
            'role' => $this->form['role'],
            'permissions' => $this->form['perms'],
        ];

        if ($this->form['password'] !== '') {
            $data['password'] = Hash::make($this->form['password']);
        }

        if ($this->editingId) {
            User::findOrFail($this->editingId)->update($data);
        } else {
            $data['active'] = true;
            User::create($data);
        }

        $this->showForm = false;
    }

    public function toggleQuickPerm(int $userId, string $perm): void
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $user = User::findOrFail($userId);
        $perms = $user->permissions ?? [];
        $removing = in_array($perm, $perms, true);

        if ($removing && $user->isOwner() && $perm === Permission::ManageUsers->value) {
            $this->listError = 'ถอดสิทธิ์จัดการผู้ใช้ออกจากเจ้าของร้านไม่ได้';

            return;
        }

        $this->listError = null;
        $user->permissions = $removing
            ? array_values(array_diff($perms, [$perm]))
            : [...$perms, $perm];

        $user->save();
    }

    public function toggleActive(int $userId): void
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        if ($userId === auth()->id()) {
            $this->listError = 'ระงับบัญชีของตัวเองไม่ได้';

            return;
        }

        $this->listError = null;
        $user = User::findOrFail($userId);
        $user->update(['active' => ! $user->active]);
    }

    public function askDelete(int $id): void
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->can('manage_users'), 403);

        $user = User::find($this->confirmDeleteId);
        $this->listError = null;

        if ($user) {
            if ($user->id === auth()->id()) {
                $this->listError = 'ลบบัญชีของตัวเองไม่ได้';
            } elseif ($user->isOwner()) {
                $this->listError = 'ลบเจ้าของร้านไม่ได้';
            } else {
                try {
                    $user->delete();
                } catch (\Illuminate\Database\QueryException $e) {
                    // FK violation (SQLSTATE 23000) — this user has historical
                    // records (documents, stock counts, etc.) referencing them,
                    // so a hard delete isn't possible without losing that trail.
                    // "ระงับการใช้งาน" achieves the same goal (blocks login)
                    // without breaking referential integrity.
                    if ($e->getCode() === '23000') {
                        $this->listError = 'ลบไม่ได้ เพราะผู้ใช้นี้มีประวัติการทำรายการอยู่ในระบบ ใช้ "ระงับการใช้งาน" แทนได้';
                    } else {
                        throw $e;
                    }
                }
            }
        }

        $this->confirmDeleteId = null;
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->roleFilter !== 'all', fn ($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('active', $this->statusFilter === 'active'))
            ->orderBy('name')
            ->get();

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'permissions' => Permission::cases(),
            'stats' => [
                'total' => User::count(),
                'owners' => User::where('role', UserRole::Owner)->count(),
                'suspended' => User::where('active', false)->count(),
            ],
            'deleteUser' => $this->confirmDeleteId ? User::find($this->confirmDeleteId) : null,
        ])->layout('components.layouts.app', ['title' => 'ผู้ใช้และสิทธิ์', 'subtitle' => 'เจ้าของและพนักงาน กำหนดสิทธิ์รายคน']);
    }
}
