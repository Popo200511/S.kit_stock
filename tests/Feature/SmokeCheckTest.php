<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

// สคริปต์ตรวจสุขภาพระบบครั้งเดียว (ไม่ใช่ regression test ถาวรเหมือน LoginTest) — ลบทิ้งได้
// หลังใช้ ใช้ pattern เดียวกับ LoginTest.php (ต่อ mysql จริง เพราะ RefreshDatabase เล่น migration
// MySQL-only ไม่ได้บน sqlite ที่ phpunit.xml ตั้งเป็นค่าเริ่มต้น)
class SmokeCheckTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        foreach (['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'stock_feed_db'] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        parent::setUp();
    }

    public function test_storefront_show_page_renders_for_a_real_product(): void
    {
        $product = Product::where('active', true)->first();

        $this->get(route('shop.product', $product))->assertOk();
    }

    public function test_shop_product_detail_api_does_not_leak_sensitive_fields(): void
    {
        $product = Product::where('active', true)->first();

        $response = $this->get('/shop/api/products/'.$product->id)->assertOk();

        $data = $response->json('data');

        $this->assertArrayNotHasKey('cost', $data);
        $this->assertArrayNotHasKey('sku', $data);
        $this->assertArrayNotHasKey('stock', $data);
        $this->assertArrayHasKey('price', $data);
        $this->assertArrayHasKey('in_stock', $data);
    }

    public function test_shop_product_detail_api_404s_for_inactive_product(): void
    {
        $product = Product::where('active', true)->first();
        $product->update(['active' => false]);

        $this->get('/shop/api/products/'.$product->id)->assertNotFound();
    }

    public function test_root_redirects_guest_to_shop_and_staff_to_landing(): void
    {
        $this->get('/')->assertRedirect(route('shop.index'));

        // User::create() ตรงๆ ไม่ใช้ factory() — .env ตั้ง APP_FAKER_LOCALE=th_TH ซึ่งพัง
        // แบบสุ่มบนเครื่องนี้เพราะไม่มี PHP extension intl (เจอครั้งแรกตอนเขียน LoginTest.php)
        $user = User::create(['name' => 'Smoke Test', 'email' => 'smoke-test@example.com', 'password' => bcrypt('x'), 'active' => true]);
        $this->actingAs($user)->get('/')->assertRedirect(route($user->landingRoute()));
    }
}
