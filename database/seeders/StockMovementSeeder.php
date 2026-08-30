<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementSeeder extends Seeder
{
    /**
     * Ported from MOVES in stock-data.js. These are historical documents whose
     * effect is already baked into the `stock` values ProductSeeder just set,
     * so lines are inserted directly here (not via StockService) to avoid
     * double-adjusting product stock.
     */
    public function run(): void
    {
        $docs = [
            [
                'doc_no' => 'IN-0003', 'type' => 'in', 'date' => '2026-07-24',
                'party' => 'บจก. เซฟฟีด (ตัวแทนภาคอีสาน)', 'user' => 'suhansa@gmail.com',
                'lines' => [['SF-7209', 40, 435], ['SF-7501', 20, 531]],
            ],
            [
                'doc_no' => 'OUT-0005', 'type' => 'out', 'date' => '2026-07-24',
                'party' => 'ฟาร์มปลาดุก บ้านหนองแวง', 'user' => 'preeya@rungrueang.co.th',
                'lines' => [['9920', 20, 530], ['9921', 10, 510]],
            ],
            [
                'doc_no' => 'OUT-0004', 'type' => 'out', 'date' => '2026-07-23',
                'party' => 'ลูกค้าหน้าร้าน – คุณวิไล', 'user' => 'preeya@rungrueang.co.th',
                'lines' => [['BC-MK20', 3, 720], ['PT-CF5', 4, 60]],
            ],
            [
                'doc_no' => 'IN-0002', 'type' => 'in', 'date' => '2026-07-22',
                'party' => 'บจก. ไฮโกร อินเตอร์เนชั่นแนล', 'user' => 'thanakorn@rungrueang.co.th',
                'lines' => [['HG-550', 24, 283], ['HG-156', 12, 721]],
            ],
            [
                'doc_no' => 'OUT-0003', 'type' => 'out', 'date' => '2026-07-21',
                'party' => 'ฟาร์มหมู หมู่ 7 ท่าพระ', 'user' => 'thanakorn@rungrueang.co.th',
                'lines' => [['SF-7509', 15, 430]],
            ],
            [
                'doc_no' => 'OUT-0002', 'type' => 'out', 'date' => '2026-07-20',
                'party' => 'ลูกค้าหน้าร้าน – คุณสุรชัย', 'user' => 'preeya@rungrueang.co.th',
                'lines' => [['WK-AD', 24, 23], ['CT-LSL', 5, 35]],
            ],
            [
                'doc_no' => 'IN-0001', 'type' => 'in', 'date' => '2026-07-18',
                'party' => 'ตัวแทนจำหน่าย ทรายแมว Platinum', 'user' => 'suhansa@gmail.com',
                'lines' => [['PT-CF5', 60, 42], ['PT-CF10', 30, 78]],
            ],
            [
                'doc_no' => 'OUT-0001', 'type' => 'out', 'date' => '2026-07-17',
                'party' => 'ฟาร์มกบ บ้านโนนม่วง', 'user' => 'thanakorn@rungrueang.co.th',
                'lines' => [['3762', 8, 775], ['3763', 6, 720]],
            ],
        ];

        $products = Product::with('category', 'unit')->get()->keyBy('sku');
        $userIds = User::pluck('id', 'email');

        foreach ($docs as $doc) {
            $movement = StockMovement::updateOrCreate(
                ['doc_no' => $doc['doc_no']],
                [
                    'type' => $doc['type'],
                    'date' => $doc['date'],
                    'party' => $doc['party'],
                    'user_id' => $userIds[$doc['user']],
                    'total' => 0,
                ]
            );

            $movement->lines()->delete();
            $total = 0;

            foreach ($doc['lines'] as [$sku, $qty, $price]) {
                $product = $products[$sku];
                $lineTotal = $qty * $price;
                $total += $lineTotal;

                $movement->lines()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category_name' => $product->category?->name,
                    'unit' => $product->unit?->name ?? '',
                    'qty' => $qty,
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                ]);
            }

            $movement->update(['total' => $total]);
        }

        // เอกสารข้างบนถูกใส่ doc_no ตรงๆ ไม่ผ่าน StockService::nextDocNo() ซึ่งออกเลขจาก
        // ตาราง doc_sequences — ถ้าไม่ sync ตัวนับให้ทันเลขที่สูงสุดตรงนี้ พอมีคนสร้างเอกสาร
        // ใหม่ผ่านหน้าเว็บครั้งแรก จะได้เลขซ้ำ (เช่น IN-0001) ที่ seed ไว้แล้วทันที
        $this->bumpSequence('movement_in', $this->maxDocNumber($docs, 'in'));
        $this->bumpSequence('movement_out', $this->maxDocNumber($docs, 'out'));
    }

    private function maxDocNumber(array $docs, string $type): int
    {
        return collect($docs)
            ->where('type', $type)
            ->map(fn ($doc) => (int) preg_replace('/\D/', '', $doc['doc_no']))
            ->max() ?? 0;
    }

    private function bumpSequence(string $key, int $atLeast): void
    {
        $current = DB::table('doc_sequences')->where('key', $key)->value('last_number') ?? 0;

        DB::table('doc_sequences')->updateOrInsert(
            ['key' => $key],
            ['last_number' => max($current, $atLeast), 'updated_at' => now()]
        );
    }
}
