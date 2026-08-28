<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around Shopee Open Platform v2 (https://open.shopee.com).
 * Every call needs Partner ID/Key from config('services.shopee') — until
 * those are set (see .env), configured() returns false and callers should
 * show a "not connected" state instead of hitting the API.
 */
class ShopeeClient
{
    public function configured(): bool
    {
        return filled(config('services.shopee.partner_id')) && filled(config('services.shopee.partner_key'));
    }

    protected function sign(string $path, int $timestamp, string $accessToken = '', string $shopId = ''): string
    {
        $partnerId = config('services.shopee.partner_id');
        $partnerKey = config('services.shopee.partner_key');

        $base = "{$partnerId}{$path}{$timestamp}{$accessToken}{$shopId}";

        return hash_hmac('sha256', $base, $partnerKey);
    }

    /** URL that starts the "authorize this shop" flow in Shopee's UI. */
    public function authorizeUrl(): string
    {
        $path = '/api/v2/shop/auth_partner';
        $timestamp = now()->timestamp;
        $sign = $this->sign($path, $timestamp);

        return config('services.shopee.base_url').$path.'?'.http_build_query([
            'partner_id' => config('services.shopee.partner_id'),
            'timestamp' => $timestamp,
            'sign' => $sign,
            'redirect' => config('services.shopee.redirect_uri'),
        ]);
    }

    /** Exchange the `code`/`shop_id` from the OAuth callback for tokens. */
    public function exchangeToken(string $code, string $shopId): array
    {
        $path = '/api/v2/auth/token/get';
        $timestamp = now()->timestamp;
        $sign = $this->sign($path, $timestamp);

        $response = Http::post(config('services.shopee.base_url').$path.'?'.http_build_query([
            'partner_id' => config('services.shopee.partner_id'),
            'timestamp' => $timestamp,
            'sign' => $sign,
        ]), [
            'code' => $code,
            'shop_id' => (int) $shopId,
            'partner_id' => (int) config('services.shopee.partner_id'),
        ])->throw();

        return $response->json();
    }

    public function refreshToken(string $refreshToken, string $shopId): array
    {
        $path = '/api/v2/auth/access_token/get';
        $timestamp = now()->timestamp;
        $sign = $this->sign($path, $timestamp);

        $response = Http::post(config('services.shopee.base_url').$path.'?'.http_build_query([
            'partner_id' => config('services.shopee.partner_id'),
            'timestamp' => $timestamp,
            'sign' => $sign,
        ]), [
            'refresh_token' => $refreshToken,
            'shop_id' => (int) $shopId,
            'partner_id' => (int) config('services.shopee.partner_id'),
        ])->throw();

        return $response->json();
    }

    /** Order numbers updated within the given window — paged by Shopee's cursor. */
    public function getOrderList(string $accessToken, string $shopId, \DateTimeInterface $since, ?string $cursor = null): array
    {
        $path = '/api/v2/order/get_order_list';
        $timestamp = now()->timestamp;
        $sign = $this->sign($path, $timestamp, $accessToken, $shopId);

        $response = Http::get(config('services.shopee.base_url').$path, [
            'partner_id' => config('services.shopee.partner_id'),
            'timestamp' => $timestamp,
            'sign' => $sign,
            'shop_id' => $shopId,
            'access_token' => $accessToken,
            'time_range_field' => 'update_time',
            'time_from' => $since->getTimestamp(),
            'time_to' => now()->timestamp,
            'page_size' => 50,
            'cursor' => $cursor,
        ])->throw();

        return $response->json();
    }

    /** Line-item detail (item_id/model_id/qty) for a batch of order SNs. */
    public function getOrderDetail(string $accessToken, string $shopId, array $orderSns): array
    {
        $path = '/api/v2/order/get_order_detail';
        $timestamp = now()->timestamp;
        $sign = $this->sign($path, $timestamp, $accessToken, $shopId);

        $response = Http::get(config('services.shopee.base_url').$path, [
            'partner_id' => config('services.shopee.partner_id'),
            'timestamp' => $timestamp,
            'sign' => $sign,
            'shop_id' => $shopId,
            'access_token' => $accessToken,
            'order_sn_list' => implode(',', $orderSns),
            'response_optional_fields' => 'item_list,total_amount,order_status',
        ])->throw();

        return $response->json();
    }

    /** Push our current stock count for one listing back to Shopee. */
    public function updateStock(string $accessToken, string $shopId, string $itemId, ?string $modelId, int $stock): array
    {
        $path = '/api/v2/product/update_stock';
        $timestamp = now()->timestamp;
        $sign = $this->sign($path, $timestamp, $accessToken, $shopId);

        $stockList = $modelId
            ? ['model_list' => [['model_id' => (int) $modelId, 'seller_stock' => [['stock' => $stock]]]]]
            : ['seller_stock' => [['stock' => $stock]]];

        $response = Http::post(config('services.shopee.base_url').$path.'?'.http_build_query([
            'partner_id' => config('services.shopee.partner_id'),
            'timestamp' => $timestamp,
            'sign' => $sign,
            'shop_id' => $shopId,
            'access_token' => $accessToken,
        ]), ['item_id' => (int) $itemId, ...$stockList])->throw();

        return $response->json();
    }
}
