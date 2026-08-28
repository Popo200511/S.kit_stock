<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovementLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_movement_id', 'product_id', 'product_variant_id', 'product_name', 'category_name',
        'unit', 'qty', 'unit_qty', 'unit_price', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'unit_qty' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function stockMovement()
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
