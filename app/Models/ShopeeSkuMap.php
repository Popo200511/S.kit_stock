<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopeeSkuMap extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'shopee_item_id', 'shopee_model_id', 'shopee_sku_name'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
