<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Ported from CATS in stock-data.js
        $categories = [
            'อาหารปลาดุก', 'อาหารปลานิล', 'อาหารกบ', 'อาหารหมู', 'อาหารไก่ + จิ้งหรีด',
            'อาหารไก่ชน', 'โค', 'เป็ด', 'ข้าวเปลือก', 'อาหารแมว', 'อาหารแมวเปียก',
            'แมวเลีย', 'ทรายแมว', 'อาหารหมา', 'นมแพะ', 'นมสัตว์', 'อาหารปลา',
            'อาหารนก', 'อาหารกระต่าย', 'ถังน้ำ', 'ถังใส่อาหาร', 'แป้ง',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
