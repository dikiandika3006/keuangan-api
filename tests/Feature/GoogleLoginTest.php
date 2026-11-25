<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogle(array $over = []): void
    {
        Http::fake(['oauth2.googleapis.com/*' => Http::response(array_merge([
            'aud' => 'web-client.apps.googleusercontent.com', 'iss' => 'https://accounts.google.com',
            'exp' => (string) (time() + 3600), 'email_verified' => 'true', 'sub' => '1122334455',
            'email' => 'Budi.Santoso@gmail.com', 'name' => 'Budi Santoso',
        ], $over))]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_ids' => ['web-client.apps.googleusercontent.com']]);
    }

    public function test_register_then_login_with_google(): void
    {
        $this->fakeGoogle();
        $r = $this->postJson('/api/login/google', ['id_token' => 'x'])->assertCreated()->json();
        $this->assertSame('budi.santoso', $r['user']['username']);
        $this->assertSame('Budi Santoso', $r['user']['name']);
        $this->assertSame('1122334455', User::first()->google_id);

        // Masuk lagi: akun yang sama, bukan akun baru.
        $this->postJson('/api/login/google', ['id_token' => 'x'])->assertOk()->assertJsonPath('user.id', $r['user']['id']);
        $this->assertSame(1, User::count());
    }

    public function test_links_existing_email_account(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Budi', 'username' => 'budi', 'email' => 'budi.santoso@gmail.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertCreated();
        $this->fakeGoogle();
        $this->postJson('/api/login/google', ['id_token' => 'x'])->assertOk()->assertJsonPath('user.username', 'budi');
        $this->assertSame(1, User::count());
        // Password lama tetap berlaku.
        $this->postJson('/api/login', ['login' => 'budi', 'password' => 'rahasia123'])->assertOk();
    }

    public function test_rejects_wrong_audience_or_unverified(): void
    {
        $this->fakeGoogle(['aud' => 'aplikasi-lain']);
        $this->postJson('/api/login/google', ['id_token' => 'x'])->assertUnprocessable();
        $this->fakeGoogle(['email_verified' => 'false']);
        $this->postJson('/api/login/google', ['id_token' => 'x'])->assertUnprocessable();
        $this->assertSame(0, User::count());
    }

    public function test_username_collision_gets_suffix(): void
    {
        User::create(['name' => 'X', 'username' => 'budi.santoso', 'email' => 'lain@example.com', 'password' => 'rahasia123']);
        $this->fakeGoogle();
        $u = $this->postJson('/api/login/google', ['id_token' => 'x'])->assertCreated()->json('user.username');
        $this->assertMatchesRegularExpression('/^budi\.santoso\d+$/', $u);
    }
}
