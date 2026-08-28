<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('size')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->decimal('online_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->string('photo_path')->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['category_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
