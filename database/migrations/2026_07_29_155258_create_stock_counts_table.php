<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_no')->unique();
            $table->date('date');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope_label');
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
