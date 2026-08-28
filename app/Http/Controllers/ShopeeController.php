<?php

namespace App\Http\Controllers;

use App\Models\ShopeeShop;
use App\Services\ShopeeClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShopeeController extends Controller
{
    public function connect(ShopeeClient $shopee): RedirectResponse
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        if (! $shopee->configured()) {
            return redirect()->route('online.index')
                ->with('error', 'ยังไม่ได้ตั้งค่า Shopee Partner ID/Key — ใส่ค่าใน .env (SHOPEE_PARTNER_ID, SHOPEE_PARTNER_KEY) ก่อนเชื่อมต่อ');
        }

        return redirect()->away($shopee->authorizeUrl());
    }

    public function callback(Request $request, ShopeeClient $shopee): RedirectResponse
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        $request->validate(['code' => 'required|string', 'shop_id' => 'required|string']);

        try {
            $token = $shopee->exchangeToken($request->string('code'), $request->string('shop_id'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('online.index')->with('error', 'เชื่อมต่อ Shopee ไม่สำเร็จ: '.$e->getMessage());
        }

        ShopeeShop::updateOrCreate(
            ['shop_id' => $request->string('shop_id')],
            [
                'access_token' => $token['access_token'] ?? null,
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($token['expire_in'] ?? 14400),
                'authorized_by' => auth()->id(),
            ]
        );

        return redirect()->route('online.index')->with('success', 'เชื่อมต่อร้าน Shopee สำเร็จ');
    }

    public function disconnect(): RedirectResponse
    {
        abort_unless(auth()->user()->can('online_sales'), 403);

        ShopeeShop::query()->delete();

        return redirect()->route('online.index')->with('success', 'ยกเลิกการเชื่อมต่อ Shopee แล้ว');
    }
}
