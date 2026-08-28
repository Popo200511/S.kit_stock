<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doc_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        // Seed each sequence from whatever the highest number currently in use is, so
        // numbering picks up exactly where it left off instead of resetting to 1 and
        // immediately colliding with existing (non-deleted) documents.
        if (Schema::hasTable('stock_movements')) {
            foreach (['in' => 'IN', 'out' => 'OUT'] as $type => $prefix) {
                $max = DB::table('stock_movements')->where('type', $type)->pluck('doc_no')
                    ->map(fn ($no) => (int) substr((string) $no, strlen($prefix) + 1))->max() ?? 0;
                DB::table('doc_sequences')->insert(['key' => "movement_{$type}", 'last_number' => $max, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        if (Schema::hasTable('stock_counts')) {
            $buddhistYear = now()->year + 543;
            $prefix = "CC-{$buddhistYear}-";
            $max = DB::table('stock_counts')->where('count_no', 'like', $prefix.'%')->pluck('count_no')
                ->map(fn ($no) => (int) substr((string) $no, strlen($prefix)))->max() ?? 0;
            DB::table('doc_sequences')->insert(['key' => "stock_count_{$buddhistYear}", 'last_number' => $max, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doc_sequences');
    }
};
