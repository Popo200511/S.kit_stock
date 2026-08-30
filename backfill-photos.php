<?php

// สคริปต์ครั้งเดียว: จับคู่ photo_path ให้สินค้าตาม "ชื่อสินค้า" (ไม่ใช้ SKU เพราะ SKU
// ในเครื่อง local กับที่ seeder สร้างไม่ตรงกันแล้ว — คนละชุดเลขกันคนละที่มา)
// รัน: php backfill-photos.php  (จากโฟลเดอร์ /app บน Railway)
// ลบไฟล์นี้ทิ้งได้หลังรันเสร็จ ไม่ใช่ส่วนหนึ่งของแอป
//
// หมายเหตุ: จับคู่ได้แค่ 27 จาก 80 สินค้าที่มีรูปในเครื่อง เพราะอีก 53 รายการเป็นสินค้า
// ที่ถูกเพิ่มเข้ามาทีหลังผ่านหน้าแอดมิน/นำเข้า Excel ไม่ได้อยู่ใน seeder ต้นฉบับ (94 รายการ)
// เลยไม่มีสินค้าตัวนั้นอยู่บน Railway ให้จับคู่ด้วยเลย

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$map = [
    'ปลาดุกเล็ก 9920 20kg' => 'products/DX1qb91hiIOgImtfZPHPhnSKZPQiTGIj6BlefIt9.jpg',
    'ปลาดุกกลาง 9921' => 'products/JqY5aoqZ5fm3TMjYmx23fV8JOpiq0kI64bNey9DC.jpg',
    'ปลาดุกใหญ่ 9922' => 'products/0dzr939pJlTgP9qQ0F1IyTBs2cMWn9NXzwfXbtTn.jpg',
    'ปลานิลเล็ก 9931' => 'products/fdQXTTYNIQ7q1xxsTellBNoJBh8aQgePm341N9hC.jpg',
    'ปลานิลกลาง 9932' => 'products/fftQhxlRsj5e3KTfoMGaNCWoTXAg1eeEYxG0ZYub.jpg',
    'ปลานิลใหญ่ 9933' => 'products/BiUlGWa5cm1yNotlq7Y0vruJyvVawKc4IZRlH1GL.jpg',
    'กบเล็ก 3762' => 'products/sXET2bqHTaPXcef7j9cUtqoO5mRDy6s8tcmMZpxU.jpg',
    'กบกลาง 3763' => 'products/FMDvAGpoICJR9GKl0aTNpQvin7R8pycc7j8DlWSf.jpg',
    'กบใหญ่ 3764' => 'products/6ZsS0g7ACNXKvPnm4DgBedM0btwFC1VvhI195EUb.jpg',
    'Catty Cat เลีย รสซีฟู้ด' => 'products/LGG6OHZwUX8Ch2uT0jve1uHunXgNysVsorObACvv.png',
    'Catty Cat เลีย รสหอยเชลล์' => 'products/VBoYgAsieBsuljfRhk8EW1eULLAeTL1wEdQJMl2z.png',
    'ซี.เอฟ 10' => 'products/M04FOZbVOkHBSdfclotgrAgNe6TnUyjCTzXXOl6i.jpg',
    'ซี.เอฟ 19' => 'products/2Oowjvcqbd0qOHWvceSRjait0g4kCJX9lxe78TYn.jpg',
    'ซี.เอฟ 20' => 'products/MuplE6iJk3Rc19o7PT157clpG904t30MQGcBkCir.jpg',
    'ไฮโกร 150S ผง' => 'products/ng2Erx3pSrJia40xhFN7Je2DkL37ZgcI7VEuFL13.jpg',
    'เซฟฟีด 7501L' => 'products/U1ESzozbP2jEhkhzBmqwx5xvVdXXPqFE9QdTmfBN.jpg',
    'เซฟฟีด 7502' => 'products/MuYf9vfJOUUT46oK1KiaqsapgjT84jXtdOounBwD.jpg',
    'เซฟฟีด 7503' => 'products/9MJK5AJLYHTeWAg7OAUpxFwzybCOM5VybdAo56nZ.jpg',
    'เซฟฟีด 7209 ไก่ไข่' => 'products/ZfpktKcdwdk7wKJHwCt6qRP9DyrQfl4h84nDkTMU.jpg',
    'เอราวัณ C5' => 'products/OZn7BiQnpuX6fdnv7b7zFPx3wgoBC5MLL5b1t1hD.jpg',
    'ไฮโปรไวท์ 510 ไก่แรกเกิด' => 'products/FkIDs4XYbOkLO0pm5gn4Ifwd2QGjnu5op1mgYHh8.jpg',
    'ไฮโปรไวท์ 524 ไก่ไข่' => 'products/38Sh8EUEXCs1VOYFjr4aYUdArGYSkFvWWhzRIgyR.jpg',
    '982 โคขุน' => 'products/kGh5LOSI2wNa7enAG7vjQvzCOilncKatSKYfYQz6.jpg',
    '005-21 โคพันธุ์' => 'products/nNccVxLhRKTa81cIYXRDV8vLiO3t5ZSVIuY7O9JM.jpg',
    '005-16 โคพันธุ์' => 'products/3ONj8gPoFYl4E0F9nbOe7DUvHX796zyd5aYu2Ujb.jpg',
    '544 เป็ดไข่' => 'products/eDLwbWObxMjIN1XUaYWa6FLqDxrnN5AdEkOwXn7P.jpg',
    'พิ้งค์ แป้งเห็บหมัด' => 'products/TmOlAh7qPe5ifWHLXNjpfjgQEfbtLDFD7P5gZ4Fm.jpg',
];

$updated = 0;
foreach ($map as $name => $path) {
    $updated += App\Models\Product::where('name', $name)->update(['photo_path' => $path]);
}

echo $updated." products updated\n";
