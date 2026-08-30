<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ตำแหน่ง "ผู้ทดสอบระบบ" ใช้แค่ตอนพัฒนา/ทดสอบ เอาออกจากระบบแล้ว — ผู้ใช้คนไหนที่ยัง
        // ติดตำแหน่งนี้อยู่ (ถ้ามี) ย้ายไปเป็น "พนักงานขาย" ก่อนบีบ enum ให้แคบลง กัน error
        // ตอน ALTER ถ้ายังมีแถวเหลือค่า 'tester' อยู่
        DB::statement("UPDATE users SET role = 'sales_staff' WHERE role = 'tester'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('owner', 'store_manager', 'warehouse_staff', 'sales_staff') NOT NULL DEFAULT 'sales_staff'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('owner', 'store_manager', 'warehouse_staff', 'sales_staff', 'tester') NOT NULL DEFAULT 'sales_staff'");
    }
};
