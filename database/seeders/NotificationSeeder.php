<?php

namespace Database\Seeders;

use App\Models\OnlineOrder;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockDetected;
use App\Notifications\OnlineOrderIssue;
use App\Notifications\StockCountCompleted;
use App\Notifications\StockMovementCreated;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A handful of realistic notifications tied to already-seeded records, so the
 * notification bell isn't empty on first login — mirrors what StockService
 * and the online-order form would have generated had those actions really
 * just happened.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::find(1);
        $storeManager = User::find(2);

        if (! $owner) {
            return;
        }

        $movement = StockMovement::with('lines')->find(1);
        $order = OnlineOrder::find(60);
        $lowStockProduct = Product::find(9);
        $count = StockCount::find(1);

        $rows = [];

        if ($movement && $storeManager) {
            $rows[] = $this->buildRow($owner, new StockMovementCreated($movement, $storeManager), now()->subMinutes(15), null);
        }

        if ($lowStockProduct) {
            $rows[] = $this->buildRow($owner, new LowStockDetected($lowStockProduct), now()->subHours(13), null);
        }

        if ($order) {
            $rows[] = $this->buildRow($owner, new OnlineOrderIssue($order), now()->subHours(16), now()->subHours(10));
        }

        if ($count && $storeManager) {
            $rows[] = $this->buildRow($owner, new StockCountCompleted($count, $storeManager), now()->subDay()->subHours(2), now()->subDay());
        }

        if ($rows !== []) {
            DB::table('notifications')->insert($rows);
        }
    }

    protected function buildRow(User $recipient, $notification, $createdAt, $readAt): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => get_class($notification),
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'data' => json_encode($notification->toArray($recipient)),
            'read_at' => $readAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
