<?php

// สคริปต์ครั้งเดียว: ลบผู้ใช้ทั้งหมดออกจากฐานข้อมูล ยกเว้นเจ้าของ (suhansa@gmail.com)
// รัน: php remove-users.php  (จากโฟลเดอร์ /app บน Railway)
// ลบไฟล์นี้ทิ้งได้หลังรันเสร็จ ไม่ใช่ส่วนหนึ่งของแอป
//
// หมายเหตุ: ผู้ใช้บางคนอาจมีประวัติ stock movement / นับสต๊อก ผูกอยู่ (คอลัมน์ user_id
// เป็น restrictOnDelete) ถ้าลบไม่ได้เพราะเหตุนี้ สคริปต์จะรายงานชื่อ+จำนวนประวัติที่ติดไว้
// ให้เห็นชัดเจน แล้วข้ามไป ไม่ลบประวัติ stock ทิ้งเองโดยไม่ถาม

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$keepEmail = 'suhansa@gmail.com';

$owner = App\Models\User::where('email', $keepEmail)->first();
if (!$owner) {
    echo "ERROR: ไม่พบผู้ใช้ {$keepEmail} ในฐานข้อมูล — ยกเลิกการลบทั้งหมดเพื่อความปลอดภัย\n";
    exit(1);
}

echo "จะเก็บไว้เฉพาะ: {$owner->name} ({$owner->email})\n\n";

$others = App\Models\User::where('id', '!=', $owner->id)->get();

$deleted = 0;
$blocked = 0;

foreach ($others as $user) {
    $movementCount = $user->stockMovements()->count();
    $countCount = $user->stockCounts()->count();

    try {
        $user->delete();
        $deleted++;
        echo "ลบแล้ว: {$user->name} ({$user->email})\n";
    } catch (\Illuminate\Database\QueryException $e) {
        $blocked++;
        echo "ลบไม่ได้: {$user->name} ({$user->email}) — ";
        echo "มีประวัติ stock movement {$movementCount} รายการ, นับสต๊อก {$countCount} รายการ ผูกอยู่\n";
    }
}

echo "\nลบสำเร็จ: {$deleted} คน\n";
echo "ลบไม่ได้ (มีประวัติผูกอยู่): {$blocked} คน\n";
echo "เหลือผู้ใช้ในระบบ: ".App\Models\User::count()." คน\n";
echo "DONE\n";
