<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = ['doc_no', 'type', 'date', 'party', 'note', 'total', 'user_id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function lines()
    {
        return $this->hasMany(StockMovementLine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
