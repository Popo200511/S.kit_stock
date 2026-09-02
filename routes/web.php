<?php

use App\Http\Controllers\ShopeeController;
use App\Livewire\Alerts;
use App\Livewire\Auth\Login;
use App\Livewire\Categories;
use App\Livewire\ComingSoon;
use App\Livewire\Dashboard;
use App\Livewire\Movements;
use App\Livewire\Online;
use App\Livewire\Products;
use App\Livewire\Reports;
use App\Livewire\StockCount;
use App\Livewire\Storefront;
use App\Livewire\Users;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// แมปหน้าใน Nav (เมนูด้านข้าง) ไปยัง Livewire component จริง — เพิ่มเข้ามาทีละหน้า
// ตามที่แต่ละ milestone เสร็จ หน้าไหนยังไม่มีในนี้จะตกไปใช้ ComingSoon แทน
$pageComponents = [
    'dashboard' => Dashboard\Index::class,
    'products.index' => Products\Index::class,
    'categories.index' => Categories\Index::class,
    'movements.index' => Movements\Index::class,
    'reports.index' => Reports\Index::class,
    'alerts.index' => Alerts\Index::class,
    'stock-count.index' => StockCount\Index::class,
    'online.index' => Online\Index::class,
    'users.index' => Users\Index::class,
];

// หน้าแรกสุด "/" ไม่มีเนื้อหาของตัวเอง แค่เด้งต่อ: login แล้วไปหน้า landing
// ตามสิทธิ์ของ user ถ้ายังไม่ login ก็เด้งไปหน้า login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->landingRoute())
        : redirect()->route('login');
});

// หน้า login ครอบด้วย middleware 'guest' คือเข้าได้เฉพาะคนที่ยังไม่ login
// (ถ้า login อยู่แล้วแล้วพยายามเข้า /login จะถูกเด้งออกไปที่อื่นแทน)
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// หน้าร้านสาธารณะสำหรับลูกค้า — ตั้งใจไม่ใส่ middleware auth เพราะเปิดดู
// สินค้า + ทักไลน์/โทรสั่งซื้อเท่านั้น ไม่มีตะกร้า ไม่มีการเขียนข้อมูลใดๆ ในนี้
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/', Storefront\Index::class)->name('index');
    Route::get('/product/{product}', Storefront\Show::class)->name('product');
    Route::get('/about', Storefront\About::class)->name('about');
    Route::get('/contact', Storefront\Contact::class)->name('contact');
});

// เสิร์ฟไฟล์จาก public disk (รูปสินค้า ฯลฯ) ผ่าน route ตรงๆ แทนการพึ่ง symlink
// public/storage -> storage/app/public เพียงอย่างเดียว — `php artisan serve` (ตัวที่ใช้รันจริง
// บน Railway ตาม railway.json) เสิร์ฟไฟล์ผ่าน symlink แบบนี้ไม่ได้ดีนัก ทำให้ทุกรูปขึ้น
// 403 Forbidden แม้ไฟล์กับ symlink จะถูกต้องครบก็ตาม — อ่านไฟล์ผ่าน Storage facade แทน
// ตัดปัญหานี้ทิ้งไปเลย
Route::get('/storage/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return response()->file(Storage::disk('public')->path($path));
})->where('path', '.*')->name('public-storage');

// sitemap.xml — ให้ Google เจอหน้าสินค้าทุกชิ้นในหน้าร้านได้ครบ ไม่ต้องรอให้มีคนลิงก์
// มาเจอเอง อยู่ที่ root ตามธรรมเนียม ไม่ใช่ /shop/sitemap.xml
Route::get('/sitemap.xml', function () {
    $products = \App\Models\Product::query()
        ->where('active', true)
        ->select(['id', 'updated_at'])
        ->orderBy('id')
        ->get();

    return response()
        ->view('sitemap', ['products' => $products])
        ->header('Content-Type', 'text/xml');
});

// ออกจากระบบ — ใช้ POST ไม่ใช่ GET เพราะเป็นการเปลี่ยนสถานะ (ไม่ควรกดออกจาก
// ระบบได้แค่เพราะเปิดลิงก์) ล้าง session และสร้าง CSRF token ใหม่ก่อนเด้งไป login
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// เชื่อมต่อ/ยกเลิกเชื่อมต่อ Shopee — ต้อง login และมีสิทธิ์ online_sales เท่านั้น
// connect = เริ่ม OAuth, callback = Shopee เด้งกลับมาหลังผู้ใช้กดอนุญาต, disconnect = ยกเลิก
Route::middleware(['auth', 'can:online_sales'])->prefix('shopee')->name('shopee.')->group(function () {
    Route::get('/connect', [ShopeeController::class, 'connect'])->name('connect');
    Route::get('/callback', [ShopeeController::class, 'callback'])->name('callback');
    Route::post('/disconnect', [ShopeeController::class, 'disconnect'])->name('disconnect');
});

// สร้าง route ให้ทุกหน้าในเมนูด้านข้างอัตโนมัติจากค่า config เดียวกับที่ใช้
// render เมนู (Nav::pages()) กันไม่ให้ชื่อ route/path/สิทธิ์เพี้ยนไปจากเมนูจริง
// ทุกหน้าอยู่ใต้ middleware 'auth' คือต้อง login ก่อนถึงจะเข้าได้
Route::middleware('auth')->group(function () use ($pageComponents) {
    foreach (Nav::pages() as $page) {
        // ตัด ".index" ท้ายชื่อ route ออก เพื่อเอามาเป็น URL path เช่น "products.index" -> "products"
        $path = str($page['route'])->before('.index')->value();
        // หา component จริงจากตาราง $pageComponents ถ้ายังไม่มีให้ใช้หน้า ComingSoon แทนไปก่อน
        $component = $pageComponents[$page['route']] ?? ComingSoon::class;

        $route = Route::get($path, $component)->name($page['route']);

        // ถ้าหน้านี้กำหนดสิทธิ์ที่ต้องมีไว้ ก็ใส่ middleware can: เพิ่ม กันไม่ให้
        // user ที่ไม่มีสิทธิ์เข้าหน้านั้นได้แม้จะพิมพ์ URL ตรงๆ
        if ($page['permission'] !== null) {
            $route->middleware('can:'.$page['permission']->value);
        }
    }
});
