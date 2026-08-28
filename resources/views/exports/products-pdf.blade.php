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
    .accent { color: #0b7a55; font-weight: 600; }
</style>
</head>
<body>
    <h1>สินค้าและราคา</h1>
    <div class="sub">พิมพ์เมื่อ {{ $generatedAt->translatedFormat('d M Y H:i') }} · ทั้งหมด {{ $products->count() }} รายการ</div>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>ชื่อสินค้า</th>
                <th>ประเภท</th>
                <th class="num">ต้นทุน</th>
                <th class="num">ราคาขาย</th>
                <th class="num">ออนไลน์</th>
                <th class="num">กำไร</th>
                <th class="num">คงเหลือ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $p)
                <tr>
                    <td>{{ $p->sku }}</td>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->category?->name }}</td>
                    <td class="num">{{ number_format($p->cost, 2) }}</td>
                    <td class="num">{{ number_format($p->price, 2) }}</td>
                    <td class="num">{{ $p->online_price !== null ? number_format($p->online_price, 2) : '—' }}</td>
                    <td class="num accent">{{ number_format($p->price - $p->cost, 2) }}</td>
                    <td class="num">{{ $p->stock_display }} {{ $p->unit?->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
