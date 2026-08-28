<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'Sarabun', sans-serif; color: #101418; font-size: 11px; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    .sub { color: #6b7480; font-size: 10.5px; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 9.5px; letter-spacing: .04em; color: #6b7480; text-transform: uppercase; border-bottom: 1px solid #e8eaee; padding: 6px 8px; }
    td { font-size: 10.5px; padding: 6px 8px; border-bottom: 1px solid #f2f4f6; }
    .num { text-align: right; }
    .status-success { color: #0b7a55; }
    .status-failed, .status-returned { color: #b3271b; }
</style>
</head>
<body>
    <h1>ขายออนไลน์ (Shopee)</h1>
    <div class="sub">พิมพ์เมื่อ {{ $generatedAt->translatedFormat('d M Y H:i') }} · ทั้งหมด {{ $orders->count() }} รายการ</div>

    <table>
        <thead>
            <tr>
                <th>วันที่</th>
                <th>เลขที่คำสั่งซื้อ</th>
                <th>รายการ</th>
                <th>ช่องทาง</th>
                <th class="num">รายรับ</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $o)
                <tr>
                    <td>{{ $o->date->format('d/m/Y') }}</td>
                    <td>{{ $o->order_no }}</td>
                    <td>{{ $o->item }}</td>
                    <td>{{ $o->channel }}</td>
                    <td class="num">{{ number_format($o->revenue, 2) }}</td>
                    <td class="status-{{ $o->status }}">{{ ['success' => 'สำเร็จ', 'failed' => 'จัดส่งไม่สำเร็จ', 'returned' => 'คืนสินค้า'][$o->status] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
