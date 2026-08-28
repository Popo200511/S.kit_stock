<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 'order_no', 'item', 'product_id', 'product_variant_id', 'channel',
        'revenue', 'qty', 'status', 'note', 'shopee_order_sn', 'source', 'shopee_raw_data',
        'stock_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'revenue' => 'decimal:2',
            'shopee_raw_data' => 'array',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function stockMovement()
    {
        return $this->belongsTo(StockMovement::class);
    }
}
