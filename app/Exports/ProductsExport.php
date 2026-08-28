<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesFormulas;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesFormulas;

    public function __construct(protected Builder $query) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['SKU', 'ชื่อสินค้า', 'ขนาด / บรรจุ', 'ประเภท', 'หน่วย', 'ต้นทุน', 'ราคาขาย', 'ราคาออนไลน์', 'กำไร', 'คงเหลือ', 'จุดสั่งซื้อขั้นต่ำ'];
    }

    public function map($product): array
    {
        return [
            $this->sanitize($product->sku),
            $this->sanitize($product->name),
            $this->sanitize($product->size),
            $this->sanitize($product->category?->name),
            $this->sanitize($product->unit?->name),
            (float) $product->cost,
            (float) $product->price,
            (float) $product->online_price,
            (float) $product->price - (float) $product->cost,
            $product->stock,
            $product->reorder_point,
        ];
    }
}
