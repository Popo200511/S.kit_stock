<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Support\Nav;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'active',
        'avatar_path',
        'notification_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'permissions' => 'array',
            'notification_preferences' => 'array',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockCounts()
    {
        return $this->hasMany(StockCount::class);
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $key = $permission instanceof Permission ? $permission->value : $permission;

        return in_array($key, $this->permissions ?? [], true);
    }

    /** Opt-out preference — null/empty means every notification type is on (the default). */
    public function mutesNotificationType(NotificationType $type): bool
    {
        return in_array($type->value, $this->notification_preferences ?? [], true);
    }

    /**
     * First page (route name) this user is allowed to land on, walking
     * Nav::pages() in order — ports landingPage() from support.js.
     */
    public function landingRoute(): string
    {
        foreach (Nav::pages() as $page) {
            if ($page['permission'] === null || $this->hasPermission($page['permission'])) {
                return $page['route'];
            }
        }

        return 'products.index';
    }
}
