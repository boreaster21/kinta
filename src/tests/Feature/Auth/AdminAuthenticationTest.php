<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected string $correctPassword = 'password';

    protected function setUp(): void
    {
        parent::setUp();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make($this->correctPassword),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
    }

    #[Test]
    public function admin_login_fails_when_email_is_missing(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => $this->correctPassword,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'メールアドレス を入力してください。']);
        $this->assertGuest();
    }

    #[Test]
    public function admin_login_fails_when_password_is_missing(): void
    {
        $response = $this->post('/login', [
            'email' => $this->adminUser->email,
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワード を入力してください。']);
        $this->assertGuest();
    }

    #[Test]
    public function admin_login_fails_with_incorrect_credentials(): void
    {
        $responseIncorrectEmail = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => $this->correctPassword,
        ]);

        $responseIncorrectEmail->assertStatus(302);
        $responseIncorrectEmail->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);
        $this->assertGuest();

        $responseIncorrectPassword = $this->post('/login', [
            'email' => $this->adminUser->email,
            'password' => 'wrong-password',
        ]);

        $responseIncorrectPassword->assertStatus(302);
        $responseIncorrectPassword->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);
        $this->assertGuest();
    }
}