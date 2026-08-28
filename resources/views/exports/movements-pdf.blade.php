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
    .in { color: #0b7a55; font-weight: 600; }
    .out { color: #4c545d; font-weight: 600; }
</style>
</head>
<body>
    <h1>รับเข้า–เบิกออก</h1>
    <div class="sub">พิมพ์เมื่อ {{ $generatedAt->translatedFormat('d M Y H:i') }} · ทั้งหมด {{ $lines->count() }} รายการ</div>

    <table>
        <thead>
            <tr>
                <th>เลขที่</th>
                <th>วันที่</th>
                <th>เวลา</th>
                <th>รายการสินค้า</th>
                <th>หมวด</th>
                <th class="num">จำนวน</th>
                <th class="num">ราคาหน่วย</th>
                <th class="num">รวม</th>
                <th>คู่ค้า</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                @php $movement = $line->stockMovement; @endphp
                <tr>
                    <td>{{ $movement->doc_no }}</td>
                    <td>{{ $movement->date->format('d/m/Y') }}</td>
                    <td>{{ $movement->created_at->format('H:i') }}</td>
                    <td>{{ $line->product_name }}</td>
                    <td>{{ $line->category_name }}</td>
                    <td class="num {{ $movement->type === 'in' ? 'in' : 'out' }}">{{ $movement->type === 'in' ? '+' : '-' }}{{ $line->qty }} {{ $line->unit }}</td>
                    <td class="num">{{ number_format($line->unit_price, 2) }}</td>
                    <td class="num">{{ number_format($line->line_total, 2) }}</td>
                    <td>{{ $movement->party }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
