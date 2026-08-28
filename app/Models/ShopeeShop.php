<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopeeShop extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'shop_name', 'access_token', 'refresh_token',
        'token_expires_at', 'authorized_by', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
