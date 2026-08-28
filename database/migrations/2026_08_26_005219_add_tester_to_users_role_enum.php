<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('owner', 'store_manager', 'warehouse_staff', 'sales_staff', 'tester') NOT NULL DEFAULT 'sales_staff'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'sales_staff' WHERE role = 'tester'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('owner', 'store_manager', 'warehouse_staff', 'sales_staff') NOT NULL DEFAULT 'sales_staff'");
    }
};
