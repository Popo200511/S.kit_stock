<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            // The extra columns the user highlighted yellow in Shopee's own export file
            // (payment method, fees, discounts, tracking no., etc.) — stored as one JSON
            // blob keyed by the original Thai column header rather than ~40 rigid columns,
            // since they're only ever shown in a detail panel, not filtered/sorted on.
            $table->json('shopee_raw_data')->nullable()->after('shopee_order_sn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropColumn('shopee_raw_data');
        });
    }
};
