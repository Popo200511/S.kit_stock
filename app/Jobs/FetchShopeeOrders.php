<?php

namespace App\Jobs;

use App\Models\OnlineOrder;
use App\Models\Product;
use App\Models\ShopeeShop;
use App\Models\ShopeeSkuMap;
use App\Services\ShopeeClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pulls new/updated Shopee orders, matches items to products via
 * shopee_sku_maps, records them as OnlineOrder rows, and decrements stock —
 * then pushes the resulting stock levels back to Shopee. Scheduled every 15
 * minutes (routes/console.php). No-ops entirely until a shop is connected
 * (see ShopeeController) since there's nothing to sync without one.
 */
class FetchShopeeOrders implements ShouldQueue
{
    use Queueable;

    public function handle(ShopeeClient $shopee): void
    {
        if (! $shopee->configured()) {
            return;
        }

        foreach (ShopeeShop::query()->whereNotNull('access_token')->get() as $shop) {
            $this->syncShop($shopee, $shop);
        }
    }

    protected function syncShop(ShopeeClient $shopee, ShopeeShop $shop): void
    {
        try {
            if ($shop->token_expires_at?->isPast()) {
                $refreshed = $shopee->refreshToken($shop->refresh_token, $shop->shop_id);
                $shop->update([
                    'access_token' => $refreshed['access_token'] ?? $shop->access_token,
                    'refresh_token' => $refreshed['refresh_token'] ?? $shop->refresh_token,
                    'token_expires_at' => now()->addSeconds($refreshed['expire_in'] ?? 14400),
                ]);
            }

            $since = $shop->last_synced_at ?? now()->subDay();
            $orderList = $shopee->getOrderList($shop->access_token, $shop->shop_id, $since);
            $orderSns = collect($orderList['response']['order_list'] ?? [])->pluck('order_sn')->all();

            if (empty($orderSns)) {
                $shop->update(['last_synced_at' => now()]);

                return;
            }

            $detail = $shopee->getOrderDetail($shop->access_token, $shop->shop_id, $orderSns);
            $skuMap = ShopeeSkuMap::with('product')->get()->keyBy(fn ($m) => $m->shopee_item_id.':'.$m->shopee_model_id);

            foreach ($detail['response']['order_list'] ?? [] as $order) {
                $this->importOrder($order, $skuMap);
            }

            $shop->update(['last_synced_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Shopee sync failed for shop '.$shop->shop_id, ['exception' => $e]);
        }
    }

    protected function importOrder(array $order, \Illuminate\Support\Collection $skuMap): void
    {
        $orderSn = $order['order_sn'] ?? null;
        $items = $order['item_list'] ?? [];
        if (! $orderSn || empty($items)) {
            return;
        }

        // online_orders has a unique (order_no, channel) key — one row per order,
        // not per line item — so multi-item orders must be aggregated into a
        // single row here (same shape as parseShopeeNativeExport()'s import path).
        if (OnlineOrder::where('order_no', $orderSn)->where('channel', 'Shopee')->exists()) {
            return;
        }

        $labels = [];
        $revenue = 0.0;
        $firstMap = null;

        foreach ($items as $item) {
            $key = ($item['item_id'] ?? '').':'.($item['model_id'] ?? '');
            $map = $skuMap->get($key);
            $qty = (int) ($item['model_quantity_purchased'] ?? 1);
            $itemName = $item['item_name'] ?? 'ไม่ทราบชื่อสินค้า';

            $labels[] = $qty > 1 ? "{$itemName} x{$qty}" : $itemName;
            $revenue += (float) ($item['model_discounted_price'] ?? 0) * $qty;
            $firstMap ??= $map;
        }

        DB::transaction(function () use ($order, $orderSn, $items, $labels, $revenue, $firstMap, $skuMap) {
            OnlineOrder::create([
                'date' => isset($order['create_time']) ? now()->createFromTimestamp($order['create_time'])->toDateString() : now()->toDateString(),
                'order_no' => $orderSn,
                'item' => implode(', ', $labels),
                'product_id' => $firstMap?->product_id,
                'channel' => 'Shopee',
                'revenue' => $revenue,
                'status' => in_array($order['order_status'] ?? null, ['CANCELLED', 'RETURN_REFUND']) ? 'returned' : 'success',
                'shopee_order_sn' => $orderSn,
                'source' => 'shopee_api',
            ]);

            foreach ($items as $item) {
                $key = ($item['item_id'] ?? '').':'.($item['model_id'] ?? '');
                $map = $skuMap->get($key);
                if (! $map?->product_id) {
                    continue;
                }

                $qty = (int) ($item['model_quantity_purchased'] ?? 1);
                $product = Product::whereKey($map->product_id)->lockForUpdate()->first();
                if (! $product) {
                    continue;
                }

                $newStock = max(0, $product->stock - $qty);
                if ($newStock !== $product->stock - $qty) {
                    Log::warning('Shopee sync: stock would go negative, clamped to 0', [
                        'product_id' => $product->id, 'order_sn' => $orderSn, 'qty' => $qty, 'stock_before' => $product->stock,
                    ]);
                }
                $product->update(['stock' => $newStock]);
            }
        });
    }

    /** Push our current stock for every mapped listing back to Shopee. */
    public function pushStockLevels(ShopeeClient $shopee): void
    {
        $shop = ShopeeShop::whereNotNull('access_token')->first();
        if (! $shop) {
            return;
        }

        foreach (ShopeeSkuMap::with('product')->get() as $map) {
            if (! $map->product) {
                continue;
            }

            try {
                $shopee->updateStock($shop->access_token, $shop->shop_id, $map->shopee_item_id, $map->shopee_model_id, $map->product->stock);
            } catch (\Throwable $e) {
                Log::error('Shopee stock push failed for item '.$map->shopee_item_id, ['exception' => $e]);
            }
        }
    }
}
