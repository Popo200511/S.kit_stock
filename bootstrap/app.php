<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// ถ้า user ที่ login ค้างอยู่แล้วหลงไปเปิด /login (เช่น bookmark เก่า, กด back หลัง logout)
// middleware 'guest' เริ่มต้นของ Laravel จะเด้งไป route('dashboard') เสมอ ไม่สนสิทธิ์ผู้ใช้
// แต่หน้า dashboard ต้องมีสิทธิ์ view_reports — พนักงานที่เห็นแค่ "สินค้า" จะโดนเด้งไปหน้าที่ตัวเอง
// เข้าไม่ได้แล้วเจอ 403 แทนที่จะไปหน้าจริงของตัวเอง แก้ให้ใช้ landingRoute() แบบเดียวกับ route "/"
RedirectIfAuthenticated::redirectUsing(
    fn ($request) => route($request->user()?->landingRoute() ?? 'login')
);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // เชื่อ proxy ทุกตัว (Railway และ PaaS อื่นๆ ทำ SSL termination ที่ edge แล้วส่ง request
        // เข้ามาเป็น HTTP ธรรมดา — ถ้าไม่เชื่อ proxy นี้ Laravel จะไม่รู้ว่า request จริงมาแบบ HTTPS
        // เลยสร้าง URL ของไฟล์ CSS/JS เป็น http:// ผิด ทำให้เบราว์เซอร์บล็อกเพราะ Mixed Content)
        $middleware->trustProxies(at: '*');

        // ตัดสิทธิ์ทันทีเมื่อผู้ใช้ถูก "ระงับการใช้งาน" แม้ session จะยัง login ค้างอยู่ก่อนหน้านี้ก็ตาม
        $middleware->appendToGroup('web', EnsureUserIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
