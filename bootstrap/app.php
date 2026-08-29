<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
