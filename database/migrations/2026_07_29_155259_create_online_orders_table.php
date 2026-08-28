<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_orders', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('order_no');
            $table->string('item');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('Shopee');
            $table->decimal('revenue', 12, 2)->default(0);
            $table->enum('status', ['success', 'failed', 'returned'])->default('success');
            $table->string('note')->nullable();
            $table->string('shopee_order_sn')->nullable();
            $table->enum('source', ['manual', 'shopee_api'])->default('manual');
            $table->timestamps();

            $table->unique(['order_no', 'channel']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_orders');
    }
};
