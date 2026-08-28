<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCountLine extends Model
{
    use HasFactory;

    protected $fillable = ['stock_count_id', 'product_id', 'product_name', 'system_qty', 'real_qty'];

    protected function casts(): array
    {
        return [
            'system_qty' => 'integer',
            'real_qty' => 'integer',
        ];
    }

    public function stockCount()
    {
        return $this->belongsTo(StockCount::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected function diffQty(): Attribute
    {
        return Attribute::get(fn () => is_null($this->real_qty) ? null : $this->real_qty - $this->system_qty);
    }
}
