<?php

namespace App\Exports;

use App\Exports\Concerns\SanitizesFormulas;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OnlineOrdersExport implements FromQuery, WithHeadings, WithMapping
{
    use SanitizesFormulas;

    public function __construct(protected Builder $query) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['วันที่', 'เลขที่คำสั่งซื้อ', 'รายการ', 'สินค้าที่จับคู่', 'ช่องทาง', 'รายรับ', 'สถานะ'];
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'success' => 'สำเร็จ',
            'failed' => 'จัดส่งไม่สำเร็จ',
            'returned' => 'คืนสินค้า',
            default => $status,
        };
    }

    public function map($order): array
    {
        return [
            $order->date->format('d/m/Y'),
            $this->sanitize($order->order_no),
            $this->sanitize($order->item),
            $this->sanitize($order->product?->name),
            $this->sanitize($order->channel),
            (float) $order->revenue,
            $this->statusLabel($order->status),
        ];
    }
}
