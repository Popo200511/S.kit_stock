<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY stock DECIMAL(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE products MODIFY reorder_point DECIMAL(10,2) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY stock INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE products MODIFY reorder_point INT NOT NULL DEFAULT 0');
    }
};
