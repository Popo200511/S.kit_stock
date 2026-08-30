<?php

// สคริปต์ครั้งเดียว: ลบผู้ใช้ "ธนากร ใจดี" และ "ปรียา ศรีทอง" ออกจากฐานข้อมูล
// พร้อมลบประวัติ stock movement / นับสต๊อก ที่ผูกกับผู้ใช้ทั้งสองคนทิ้งไปด้วย
// (รายการย่อยในแต่ละใบ เช่น stock_movement_lines / stock_count_lines จะถูกลบตามอัตโนมัติ
// เพราะตั้งค่า cascade ไว้ในฐานข้อมูลอยู่แล้ว)
// รัน: php remove-two-users.php  (จากโฟลเดอร์ /app บน Railway)
// ลบไฟล์นี้ทิ้งได้หลังรันเสร็จ ไม่ใช่ส่วนหนึ่งของแอป

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$targets = ['ธนากร ใจดี', 'ปรียา ศรีทอง'];

foreach ($targets as $name) {
    $user = App\Models\User::where('name', $name)->first();

    if (!$user) {
        echo "ไม่พบผู้ใช้ชื่อ \"{$name}\" ในฐานข้อมูล — ข้าม\n";
        continue;
    }

    $movements = $user->stockMovements()->count();
    $counts = $user->stockCounts()->count();

    $user->stockMovements()->delete();
    $user->stockCounts()->delete();

    $user->delete();

    echo "ลบแล้ว: {$user->name} ({$user->email}) — ลบประวัติ stock movement {$movements} รายการ, นับสต๊อก {$counts} รายการ ไปด้วย\n";
}

echo "\nเหลือผู้ใช้ในระบบ: ".App\Models\User::count()." คน\n";
echo "DONE\n";
