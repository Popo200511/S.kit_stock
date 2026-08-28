<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesFormulas;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MovementLinesExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesFormulas;

    public function __construct(protected Builder $query) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['เลขที่', 'วันที่', 'เวลา', 'ประเภท', 'รายการสินค้า', 'หมวด', 'จำนวน', 'หน่วย', 'ราคาต่อหน่วย', 'รวม', 'คู่ค้า', 'ผู้ทำรายการ'];
    }

    public function map($line): array
    {
        $movement = $line->stockMovement;

        return [
            $movement->doc_no,
            $movement->date->format('d/m/Y'),
            $movement->created_at->format('H:i'),
            $movement->type === 'in' ? 'รับเข้า' : 'เบิกออก',
            $this->sanitize($line->product_name),
            $this->sanitize($line->category_name),
            $line->qty,
            $this->sanitize($line->unit),
            (float) $line->unit_price,
            (float) $line->line_total,
            $this->sanitize($movement->party),
            $this->sanitize($movement->user?->name),
        ];
    }
}
