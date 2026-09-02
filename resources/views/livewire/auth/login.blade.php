<div class="min-h-screen grid grid-cols-1 lg:grid-cols-[1.02fr_1fr]" style="background: linear-gradient(160deg, #fdf7f8 0%, #fbeef1 100%);">

    {{-- Hero panel --}}
    <div data-aos="fade-right" class="hidden lg:flex relative flex-col overflow-hidden bg-[#efe6e2] min-h-screen bg-cover bg-center" style="background-image: url('{{ asset('images/login-hero-dog.png') }}');">
        <span class="absolute inset-0 bg-gradient-to-b from-black/45 via-black/10 to-black/5"></span>

        <div class="relative p-10 flex items-center gap-4">
            <span class="relative w-24 h-24 shrink-0 rounded-full overflow-hidden bg-white shadow-lg flex items-center justify-center">
                <img src="{{ asset('images/logo.png') }}" alt="ส.กิจการค้า" class="w-full h-full object-cover">
            </span>
            <div class="flex flex-col gap-1 min-w-0">
                <span class="text-3xl font-bold tracking-tight text-white leading-tight" style="text-shadow:0 2px 12px rgba(24,18,16,.34)">ส.กิจการค้า</span>
                <span class="text-[15px] text-white/95 leading-snug" style="text-shadow:0 1px 8px rgba(24,18,16,.34)">ระบบจัดการสต็อกสินค้าอาหารสัตว์</span>
                <span class="text-[10.5px] tracking-[.17em] text-white/70">PET FOOD STOCK MANAGEMENT SYSTEM</span>
            </div>
        </div>

        <div class="relative mt-auto p-10 flex flex-col gap-1">
            <span class="text-xl font-semibold text-white leading-relaxed -rotate-2" style="text-shadow:0 2px 12px rgba(24,18,16,.4)">ใส่ใจทุกคำ</span>
            <span class="text-xl font-semibold text-white leading-relaxed -rotate-2 ml-6" style="text-shadow:0 2px 12px rgba(24,18,16,.4)">เพื่อเขาที่คุณรัก</span>
            <span class="w-[150px] h-0.5 bg-[#e0798d] mt-2 ml-6 -rotate-2"></span>
        </div>
    </div>

    {{-- Login card --}}
    <div class="flex flex-col items-center justify-center gap-5 p-6 sm:p-8">
        <div data-aos="fade-left" data-aos-delay="100" class="w-full max-w-[398px] bg-white rounded-[20px] shadow-xl p-6 sm:p-8 flex flex-col gap-4">
            <div class="flex flex-col items-center gap-2.5">
                <span class="w-16 h-16 rounded-full overflow-hidden bg-white shadow flex items-center justify-center">
                    <img src="{{ asset('images/logo.png') }}" alt="ส.กิจการค้า" class="w-full h-full object-cover">
                </span>
                <span class="text-2xl font-bold tracking-tight text-[#101a2e]">ยินดีต้อนรับ</span>
                <span class="text-sm text-[#5b6472]">เข้าสู่ระบบ <b class="text-login-accent">ส.กิจการค้า</b></span>
            </div>

            <form wire:submit="submit" class="flex flex-col gap-4">
                <div class="flex items-center gap-2.5 border border-[#dfe4ec] rounded-[11px] px-3.5 focus-within:border-login-accent">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#9aa3b0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2M12 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8"></path></svg>
                    <input type="text" wire:model="email" placeholder="ชื่อผู้ใช้ / อีเมล" autocomplete="username"
                        class="flex-1 min-w-0 py-3.5 text-sm text-[#101a2e] border-0 focus:ring-0 focus:outline-none bg-transparent">
                </div>

                <div class="flex items-center gap-2.5 border border-[#dfe4ec] rounded-[11px] pl-3.5 pr-2.5 focus-within:border-login-accent">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#9aa3b0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4"></path></svg>
                    <input :type="showPassword ? 'text' : 'password'" x-data="{ showPassword: @entangle('showPassword') }"
                        wire:model="password" placeholder="รหัสผ่าน" autocomplete="current-password"
                        class="flex-1 min-w-0 py-3.5 text-sm border-0 focus:ring-0 focus:outline-none bg-transparent">
                    <button type="button" wire:click="$toggle('showPassword')" title="แสดง/ซ่อนรหัสผ่าน"
                        class="w-[26px] h-[26px] shrink-0 rounded-md flex items-center justify-center text-[#9aa3b0] hover:bg-[#f1f3f8] hover:text-[#5b6472]">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            @if ($showPassword)
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-5 0-9.27-3.11-11-8 .81-2.27 2.24-4.24 4.06-5.69M9.9 4.24A9.12 9.12 0 0 1 12 4c5 0 9.27 3.11 11 8a13.16 13.16 0 0 1-1.67 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24M1 1l22 22"></path>
                            @else
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
                            @endif
                        </svg>
                    </button>
                </div>

                <label class="flex items-center gap-2 text-[13px] text-[#3d4756] cursor-pointer">
                    <input type="checkbox" wire:model="remember" class="w-[17px] h-[17px] rounded-[5px] border-[#dfe4ec] text-login-accent focus:ring-login-accent focus:outline-none">
                    จดจำฉัน
                </label>

                @if ($error)
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3 py-2.5 leading-relaxed">{{ $error }}</span>
                @endif
                @if (request()->query('suspended'))
                    <span class="text-[12.5px] text-danger bg-danger-tint rounded-lg px-3 py-2.5 leading-relaxed">บัญชีนี้ถูกระงับการใช้งาน ติดต่อเจ้าของร้าน</span>
                @endif

                <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                    class="flex items-center justify-center gap-2.5 py-3.5 rounded-[11px] bg-login-accent text-white text-[15px] font-semibold hover:bg-login-accent-hover disabled:opacity-70 disabled:cursor-not-allowed">
                    <svg wire:loading.remove wire:target="submit" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4"></path></svg>
                    <svg wire:loading wire:target="submit" class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 3a9 9 0 1 0 9 9" opacity="0.9"></path></svg>
                    <span wire:loading.remove wire:target="submit">เข้าสู่ระบบ</span>
                    <span wire:loading wire:target="submit">กำลังเข้าสู่ระบบ...</span>
                </button>
            </form>

            <div class="flex flex-col items-center gap-1.5 border-t border-[#eef0f5] pt-3.5">
                <span class="text-[11.5px] text-[#9aa3b0]">© {{ now()->year + 543 }} ส.กิจการค้า · สงวนลิขสิทธิ์ทุกประการ</span>
                <span class="text-[11.5px] text-[#b3bac5]">เวอร์ชัน 1.0.0</span>
            </div>
        </div>
    </div>
</div>
