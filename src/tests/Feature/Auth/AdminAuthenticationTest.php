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

    protected User $adminUser; // Changed property name for clarity
    protected string $correctPassword = 'password';

    protected function setUp(): void
    {
        parent::setUp();
        // 'admin' ロールが存在しなければ作成する
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        // 'user' ロールも念のため存在確認（他のテストとの依存回避）
        Role::firstOrCreate(['name' => 'user']);

        // テスト用の管理者ユーザーを作成
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com', // Use a different email for admin
            'password' => Hash::make($this->correctPassword),
            'role_id' => $adminRole->id, // Assign admin role ID
            'email_verified_at' => now(), // Assume admin also needs verification
        ]);
    }

    #[Test]
    public function admin_login_fails_when_email_is_missing(): void
    {
        // Test logic is identical to the general user test
        $response = $this->post('/login', [
            'email' => '',
            'password' => $this->correctPassword,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'メールアドレス を入力してください。']); // Use confirmed message
        $this->assertGuest();
    }

    #[Test]
    public function admin_login_fails_when_password_is_missing(): void
    {
        // Test logic is identical, but uses adminUser's email
        $response = $this->post('/login', [
            'email' => $this->adminUser->email, // Use admin's email
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワード を入力してください。']); // Use confirmed message
        $this->assertGuest();
    }

    #[Test]
    public function admin_login_fails_with_incorrect_credentials(): void
    {
        // Test logic is identical

        // 誤ったメールアドレスで試す
        $responseIncorrectEmail = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => $this->correctPassword,
        ]);

        $responseIncorrectEmail->assertStatus(302);
        $responseIncorrectEmail->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']); // Use confirmed message
        $this->assertGuest();

        // 誤ったパスワードで試す (uses adminUser's email)
        $responseIncorrectPassword = $this->post('/login', [
            'email' => $this->adminUser->email, // Use admin's email
            'password' => 'wrong-password',
        ]);

        $responseIncorrectPassword->assertStatus(302);
        $responseIncorrectPassword->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']); // Use confirmed message
        $this->assertGuest();
    }
}