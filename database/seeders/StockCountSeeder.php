<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockCountSeeder extends Seeder
{
    /**
     * Ported from COUNTS in stock-data.js. Historical, already-closed sessions —
     * lines are inserted as-is without touching products.stock (see StockMovementSeeder).
     */
    public function run(): void
    {
        $sessions = [
            [
                'count_no' => 'CC-2569-012', 'date' => '2026-07-23', 'user' => 'thanakorn@rungrueang.co.th',
                'category' => 'อาหารแมว', 'scope_label' => 'คลังหน้าร้าน · หมวดอาหารแมว',
                'lines' => [['BC-MK20', 28, 27], ['BC-GK20', 17, 17], ['CN-MK10', 33, 31], ['CT-SL1', 74, 74]],
            ],
            [
                'count_no' => 'CC-2569-011', 'date' => '2026-07-16', 'user' => 'preeya@rungrueang.co.th',
                'category' => null, 'scope_label' => 'คลังหลัง · อาหารปลา-กบ',
                'lines' => [['9920', 64, 64], ['9921', 38, 36], ['3762', 18, 18], ['3764', 2, 0]],
            ],
            [
                'count_no' => 'CC-2569-010', 'date' => '2026-07-09', 'user' => 'suhansa@gmail.com',
                'category' => null, 'scope_label' => 'ทั้งร้าน · ตรวจรายเดือน',
                'lines' => [['SF-7209', 57, 55], ['ER-C5', 48, 48], ['PT-CF5', 70, 66], ['BD-BF20', 24, 24], ['SH-GM12', 44, 45]],
            ],
        ];

        $productIds = Product::pluck('id', 'sku');
        $productNames = Product::pluck('name', 'sku');
        $userIds = User::pluck('id', 'email');
        $categoryIds = Category::pluck('id', 'name');

        foreach ($sessions as $session) {
            $count = StockCount::updateOrCreate(
                ['count_no' => $session['count_no']],
                [
                    'date' => $session['date'],
                    'category_id' => $session['category'] ? $categoryIds[$session['category']] : null,
                    'scope_label' => $session['scope_label'],
                    'status' => 'completed',
                    'user_id' => $userIds[$session['user']],
                ]
            );

            $count->lines()->delete();

            foreach ($session['lines'] as [$sku, $sys, $real]) {
                $count->lines()->create([
                    'product_id' => $productIds[$sku],
                    'product_name' => $productNames[$sku],
                    'system_qty' => $sys,
                    'real_qty' => $real,
                ]);
            }
        }

        // count_no ข้างบนถูกใส่ตรงๆ ไม่ผ่าน StockService::nextCountNo() ซึ่งออกเลขจากตาราง
        // doc_sequences — ถ้าไม่ sync ตัวนับให้ทันเลขที่สูงสุดของแต่ละปี พอมีคนสร้างรอบนับ
        // สต๊อกใหม่ผ่านหน้าเว็บครั้งแรก จะได้เลขซ้ำกับที่ seed ไว้แล้วทันที
        $maxByYear = [];
        foreach ($sessions as $session) {
            if (preg_match('/^CC-(\d+)-(\d+)$/', $session['count_no'], $m) === 1) {
                $year = $m[1];
                $number = (int) $m[2];
                $maxByYear[$year] = max($maxByYear[$year] ?? 0, $number);
            }
        }

        foreach ($maxByYear as $year => $max) {
            $key = "stock_count_{$year}";
            $current = DB::table('doc_sequences')->where('key', $key)->value('last_number') ?? 0;

            DB::table('doc_sequences')->updateOrInsert(
                ['key' => $key],
                ['last_number' => max($current, $max), 'updated_at' => now()]
            );
        }
    }
}
