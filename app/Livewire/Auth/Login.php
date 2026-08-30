<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = true;

    public bool $showPassword = false;

    public ?string $error = null;

    public function submit()
    {
        $email = trim($this->email);
        $password = $this->password;

        if ($email === '') {
            $this->error = 'กรอกอีเมลพนักงาน';

            return;
        }

        if ($password === '') {
            $this->error = 'กรอกรหัสผ่าน';

            return;
        }

        // Keyed by email+IP (not just email) so one bad actor can't lock out
        // a real employee from a different network by spamming their address.
        $throttleKey = Str::lower($email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->error = "ลองผิดหลายครั้งเกินไป กรุณารออีก {$seconds} วินาที";

            return;
        }

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        if (! $user) {
            RateLimiter::hit($throttleKey, 60);
            $this->error = 'ไม่พบบัญชีนี้ในระบบ ตรวจสอบอีเมลอีกครั้ง';

            return;
        }

        if (! $user->active) {
            $this->error = 'บัญชีนี้ถูกระงับการใช้งาน ติดต่อเจ้าของร้าน';

            return;
        }

        if (! Auth::attempt(['email' => $user->email, 'password' => $password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);
            $this->error = 'รหัสผ่านไม่ถูกต้อง';

            return;
        }

        RateLimiter::clear($throttleKey);
        request()->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route($user->landingRoute());
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
