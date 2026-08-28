<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores which notification TYPES a user has muted (an "opt-out" list, keyed by
     * App\Enums\NotificationType values) — null/empty means every type is on, which is
     * the current behaviour for every existing user, so this ships with no visible change
     * until someone actually opens the notification settings and turns something off.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('permissions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
