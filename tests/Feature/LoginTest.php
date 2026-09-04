<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    // phpunit.xml points the default connection at an in-memory sqlite DB (fast,
    // isolated) but this project has a MySQL-only migration (`ALTER TABLE ...
    // MODIFY`, see widen_stock_columns_on_products_table) that RefreshDatabase
    // can't replay on sqlite. Rather than touch an already-applied migration,
    // this test runs against the real local `mysql` connection (already
    // migrated) and rolls back everything via a transaction instead.
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        // Must happen BEFORE parent::setUp() boots the app — DatabaseTransactions'
        // setUp opens the transaction as part of that boot, using whatever
        // connection config/database.php resolves at that point.
        foreach (['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'stock_feed_db'] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        parent::setUp();
    }

    public function test_login_page_loads_for_guests(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_logged_in_users_are_redirected_away_from_login(): void
    {
        $user = User::create(['name' => 'Login Tester', 'email' => 'logged-in-user@example.com', 'password' => bcrypt('x'), 'active' => true]);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route($user->landingRoute()));
    }

    public function test_empty_email_shows_error(): void
    {
        Livewire::test(Login::class)
            ->call('submit')
            ->assertSet('error', 'กรอกอีเมลพนักงาน');

        $this->assertGuest();
    }

    public function test_empty_password_shows_error(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'someone@example.com')
            ->call('submit')
            ->assertSet('error', 'กรอกรหัสผ่าน');

        $this->assertGuest();
    }

    public function test_unknown_email_shows_error(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'nobody@example.com')
            ->set('password', 'whatever')
            ->call('submit')
            ->assertSet('error', 'ไม่พบบัญชีนี้ในระบบ ตรวจสอบอีเมลอีกครั้ง');

        $this->assertGuest();
    }

    public function test_wrong_password_shows_error_and_does_not_log_in(): void
    {
        User::create([
            'name' => 'Login Tester',
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
            'active' => true,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'user@example.com')
            ->set('password', 'wrong-password')
            ->call('submit')
            ->assertSet('error', 'รหัสผ่านไม่ถูกต้อง');

        $this->assertGuest();
    }

    public function test_suspended_account_cannot_log_in_even_with_correct_password(): void
    {
        User::create([
            'name' => 'Login Tester',
            'email' => 'suspended@example.com',
            'password' => bcrypt('correct-password'),
            'active' => false,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'suspended@example.com')
            ->set('password', 'correct-password')
            ->call('submit')
            ->assertSet('error', 'บัญชีนี้ถูกระงับการใช้งาน ติดต่อเจ้าของร้าน');

        $this->assertGuest();
    }

    public function test_suspended_query_flag_shows_banner_on_login_page(): void
    {
        $this->get(route('login', ['suspended' => 1]))
            ->assertSee('บัญชีนี้ถูกระงับการใช้งาน ติดต่อเจ้าของร้าน');
    }

    public function test_successful_login_redirects_to_landing_route_and_updates_last_login(): void
    {
        $user = User::create([
            'name' => 'Login Tester',
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
            'active' => true,
            'last_login_at' => null,
        ]);

        // Livewire's ->call()/->set() dispatch bypasses the 'web' middleware group
        // (StartSession included) entirely, so the session needs to be started
        // directly on the container for session()->regenerate() to have a store.
        $this->startSession();

        Livewire::test(Login::class)
            ->set('email', 'user@example.com')
            ->set('password', 'correct-password')
            ->call('submit')
            ->assertRedirect(route($user->landingRoute()));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_email_is_matched_case_insensitively_and_trimmed(): void
    {
        $user = User::create([
            'name' => 'Login Tester',
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
            'active' => true,
        ]);

        $this->startSession();

        Livewire::test(Login::class)
            ->set('email', '  USER@EXAMPLE.COM  ')
            ->set('password', 'correct-password')
            ->call('submit');

        $this->assertAuthenticatedAs($user);
    }

    public function test_rate_limiter_blocks_after_five_failed_attempts_even_with_correct_password_after(): void
    {
        RateLimiter::clear('user@example.com|127.0.0.1');

        User::create([
            'name' => 'Login Tester',
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
            'active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'user@example.com')
                ->set('password', 'wrong-password')
                ->call('submit');
        }

        Livewire::test(Login::class)
            ->set('email', 'user@example.com')
            ->set('password', 'correct-password')
            ->call('submit')
            ->assertSet('error', fn ($error) => str_contains($error, 'ลองผิดหลายครั้งเกินไป'));

        $this->assertGuest();
    }
}
