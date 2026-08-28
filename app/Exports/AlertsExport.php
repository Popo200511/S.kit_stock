<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesFormulas;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AlertsExport implements FromCollection, WithHeadings, WithMapping
{
    use SanitizesFormulas;

    /** @param  \Closure(Product): int  $suggestQtyFor */
    public function __construct(protected Collection $products, protected \Closure $suggestQtyFor) {}

    public function collection(): Collection
    {
        return $this->products;
    }

    public function headings(): array
    {
        return ['SKU', 'ชื่อสินค้า', 'ประเภท', 'คงเหลือ', 'จุดสั่งซื้อขั้นต่ำ', 'แนะนำสั่ง', 'หน่วย', 'ต้นทุน/หน่วย', 'มูลค่าที่ควรสั่ง'];
    }

    public function map($product): array
    {
        $suggestQty = ($this->suggestQtyFor)($product);

        return [
            $this->sanitize($product->sku),
            $this->sanitize($product->name),
            $this->sanitize($product->category?->name),
            $product->stock,
            $product->reorder_point,
            $suggestQty,
            $this->sanitize($product->unit?->name),
            (float) $product->cost,
            $suggestQty * (float) $product->cost,
        ];
    }
}
