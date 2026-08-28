<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopee_sku_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('shopee_item_id');
            $table->string('shopee_model_id')->nullable();
            $table->string('shopee_sku_name')->nullable();
            $table->timestamps();

            $table->unique(['shopee_item_id', 'shopee_model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_sku_maps');
    }
};
