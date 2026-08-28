<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a matched, successful online order actually move stock: qty is how many units
     * were sold (defaults to 1 for existing rows and hand-typed orders), product_variant_id
     * optionally pins the exact "ขนาดที่ขาย" sold (so base-unit conversion is correct),
     * and stock_movement_id links to the auto-created "out" document backing the deduction
     * — nulled if that document is ever deleted directly, so the order just reads as unsynced.
     */
    public function up(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->unsignedInteger('qty')->default(1)->after('revenue');
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->after('shopee_raw_data')->constrained('stock_movements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropConstrainedForeignId('stock_movement_id');
            $table->dropColumn('qty');
        });
    }
};
