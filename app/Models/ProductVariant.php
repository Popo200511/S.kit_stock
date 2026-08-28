<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'label',
        'unit_qty',
        'price',
        'online_price',
        'wholesale_price',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'unit_qty' => 'decimal:3',
        'price' => 'decimal:2',
        'online_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
