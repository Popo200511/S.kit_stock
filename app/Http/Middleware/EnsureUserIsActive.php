<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * ตัดสิทธิ์ผู้ใช้ที่ถูก "ระงับการใช้งาน" ทันที บนทุก request — ไม่ใช่แค่ตอน login
     * (Login.php เช็ก active ตอน login อยู่แล้ว แต่ถ้าคนนั้น login ค้างอยู่ก่อนถูกระงับ
     * โดยไม่มี middleware นี้ เขาจะยังใช้งานต่อไปได้เรื่อยๆ จนกว่าจะออกจากระบบเอง)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // ใช้ query param แทน session flash — ตอนถูกเรียกจาก wire:click (POST /livewire/update),
            // Livewire's fetch() ไล่ตาม redirect เองภายในก่อน แล้วค่อยสั่ง window.location จริงอีกที
            // ทำให้เกิด request แทรกกลางที่ "กิน" ค่า flash ทิ้งก่อนหน้าที่หน้า login จะ render จริง
            return redirect()->route('login', ['suspended' => 1]);
        }

        return $next($request);
    }
}
