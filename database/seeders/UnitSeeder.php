<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        // Distinct unit values used across PRODUCTS in stock-data.js
        $units = ['กระสอบ', 'ถุง', 'ซอง', 'ชุด', 'แพ็ค', 'ใบ', 'อัน', 'กระปุก', 'กล่อง', 'กระป๋อง'];

        foreach ($units as $name) {
            Unit::firstOrCreate(['name' => $name]);
        }
    }
}
