<?php

// สคริปต์ครั้งเดียว: แก้ตัวนับเลขที่เอกสาร (doc_sequences) ให้ตรงกับเอกสารที่มีอยู่จริง
// สาเหตุที่บันทึกเอกสารใหม่แล้วขึ้น error "Duplicate entry 'IN-0001'": ข้อมูลตัวอย่างที่ seed
// ไว้ใส่เลขที่เอกสาร (IN-0001, OUT-0001 ฯลฯ) ตรงๆ โดยไม่ผ่านตัวนับเลข ทำให้ตัวนับไม่รู้ว่า
// เลขพวกนี้ถูกใช้ไปแล้ว พอสร้างเอกสารใหม่ครั้งแรกเลยออกเลข IN-0001 ซ้ำของเดิม
// รัน: php fix-doc-sequences.php  (จากโฟลเดอร์ /app บน Railway)
// ลบไฟล์นี้ทิ้งได้หลังรันเสร็จ ไม่ใช่ส่วนหนึ่งของแอป

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function bump(string $key, int $atLeast): void
{
    $current = DB::table('doc_sequences')->where('key', $key)->value('last_number') ?? 0;
    $new = max($current, $atLeast);

    DB::table('doc_sequences')->updateOrInsert(
        ['key' => $key],
        ['last_number' => $new, 'updated_at' => now(), 'created_at' => now()]
    );

    echo "{$key}: {$current} -> {$new}\n";
}

foreach (['in' => 'IN', 'out' => 'OUT'] as $type => $prefix) {
    $max = DB::table('stock_movements')->where('type', $type)->pluck('doc_no')
        ->map(fn ($no) => (int) preg_replace('/\D/', '', (string) $no))->max() ?? 0;
    bump("movement_{$type}", $max);
}

$buddhistYear = now()->year + 543;
$countPrefix = "CC-{$buddhistYear}-";
$maxCount = DB::table('stock_counts')->where('count_no', 'like', $countPrefix.'%')->pluck('count_no')
    ->map(fn ($no) => (int) substr((string) $no, strlen($countPrefix)))->max() ?? 0;
bump("stock_count_{$buddhistYear}", $maxCount);

echo "DONE\n";
