<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'size', 'description', 'category_id', 'unit_id',
        'cost', 'price', 'wholesale_price', 'online_price',
        'stock', 'reorder_point', 'suggested_reorder_qty', 'photo_path', 'active',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'online_price' => 'decimal:2',
            'stock' => 'decimal:2',
            'reorder_point' => 'decimal:2',
            'suggested_reorder_qty' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockMovementLines()
    {
        return $this->hasMany(StockMovementLine::class);
    }

    public function stockCountLines()
    {
        return $this->hasMany(StockCountLine::class);
    }

    public function onlineOrders()
    {
        return $this->hasMany(OnlineOrder::class);
    }

    public function shopeeSkuMaps()
    {
        return $this->hasMany(ShopeeSkuMap::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    protected function hasVariants(): Attribute
    {
        return Attribute::get(fn () => $this->variants()->exists());
    }

    /**
     * Trims trailing zeros off the decimal-cast stock/reorder_point strings
     * so whole numbers show as "20" instead of "20.00", while fractional
     * base-unit quantities (e.g. from a variant sale) still show as "17.5".
     */
    protected function stockDisplay(): Attribute
    {
        return Attribute::get(fn () => rtrim(rtrim(number_format((float) $this->stock, 2, '.', ''), '0'), '.'));
    }

    protected function reorderPointDisplay(): Attribute
    {
        return Attribute::get(fn () => rtrim(rtrim(number_format((float) $this->reorder_point, 2, '.', ''), '0'), '.'));
    }

    /**
     * Stock status tier, ported verbatim from statusOf() in support.js (line 2379-2384):
     * out of stock -> low (<= reorder point) -> watch (<= reorder point * 1.5) -> normal.
     * Uses <= 0 rather than === 0 because the decimal cast returns a string.
     */
    protected function stockStatus(): Attribute
    {
        return Attribute::get(function () {
            if ((float) $this->stock <= 0) {
                return ['label' => 'หมดสต็อก', 'tone' => 'danger'];
            }
            if ($this->stock <= $this->reorder_point) {
                return ['label' => 'ใกล้หมด', 'tone' => 'warn'];
            }
            if ($this->stock <= $this->reorder_point * 1.5) {
                return ['label' => 'เฝ้าระวัง', 'tone' => 'caution'];
            }

            return ['label' => 'ปกติ', 'tone' => 'accent'];
        });
    }
}
