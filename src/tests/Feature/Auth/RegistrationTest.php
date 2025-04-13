<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();
        Role::factory()->create(['name' => 'admin']);
        $this->userRole = Role::factory()->create(['name' => 'user']);
    }

    private function validUserData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }


    #[Test]
    public function registration_fails_when_name_is_missing(): void
    {
        $userData = $this->validUserData(['name' => '']);
        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください。']);
    }

    #[Test]
    public function registration_fails_when_email_is_missing(): void
    {
        $userData = $this->validUserData(['email' => '']);
        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください。']);
    }

    #[Test]
    public function registration_fails_when_password_is_too_short(): void
    {
        $userData = $this->validUserData([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください。']);
    }

    #[Test]
    public function registration_fails_when_password_confirmation_does_not_match(): void
    {
        $userData = $this->validUserData(['password_confirmation' => 'different']);
        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません。']);
    }

    #[Test]
    public function registration_fails_when_password_is_missing(): void
    {
        $userData = $this->validUserData([
            'password' => '',
            'password_confirmation' => '',
        ]);
        $response = $this->post('/register', $userData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください。']);
    }

    #[Test]
    public function user_can_register_with_valid_data(): void
    {
        $userData = $this->validUserData();
        $response = $this->post('/register', $userData);
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name'],
            'role_id' => $this->userRole->id,
        ]);

        $response->assertRedirect('/email/verify');
        $this->assertAuthenticated();
    }
}