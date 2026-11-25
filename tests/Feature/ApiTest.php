<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $over = []): array
    {
        return $this->postJson('/api/register', array_merge([
            'name' => 'Diki Andika', 'username' => 'Diki', 'email' => 'Diki@Example.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ], $over))->assertCreated()->json();
    }

    public function test_register_login_with_username_or_email_and_profile(): void
    {
        $r = $this->register();
        $this->assertSame('diki', $r['user']['username']);
        $this->assertSame('free', $r['user']['plan']);

        $this->postJson('/api/register', [
            'name' => 'X', 'username' => 'diki', 'email' => 'lain@example.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertUnprocessable()->assertJsonValidationErrors('username');

        $this->postJson('/api/login', ['login' => 'diki', 'password' => 'rahasia123'])->assertOk()->assertJsonStructure(['token', 'user']);
        $this->postJson('/api/login', ['login' => 'diki@example.com', 'password' => 'rahasia123'])->assertOk();
        $this->postJson('/api/login', ['login' => 'diki', 'password' => 'salah'])->assertUnprocessable();

        $this->withToken($r['token'])->getJson('/api/me')->assertOk()->assertJsonPath('user.name', 'Diki Andika');
        $this->withToken($r['token'])->putJson('/api/me', ['name' => 'Diki', 'username' => 'diki_a', 'email' => 'diki@example.com'])
            ->assertOk()->assertJsonPath('user.username', 'diki_a');
    }

    public function test_sync_push_pull_and_last_write_wins(): void
    {
        $a = $this->register()['token'];
        $b = $this->postJson('/api/register', [
            'name' => 'Budi', 'username' => 'budi', 'email' => 'budi@example.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->json('token');

        $acc = ['entity' => 'account', 'id' => 'uuid-1', 'updated_at' => 1000, 'deleted_at' => null, 'data' => ['name' => 'BCA']];
        $res = $this->withToken($a)->postJson('/api/sync', ['cursor' => 0, 'changes' => [$acc]])->assertOk()->json();
        $this->assertSame(1, $res['accepted']);
        $this->assertCount(1, $res['changes']);
        $cursor = $res['cursor'];

        // perubahan lama ditolak, yang baru diterima
        $old = array_merge($acc, ['updated_at' => 500, 'data' => ['name' => 'Lama']]);
        $this->withToken($a)->postJson('/api/sync', ['cursor' => $cursor, 'changes' => [$old]])->assertJsonPath('accepted', 0);
        $new = array_merge($acc, ['updated_at' => 2000, 'data' => ['name' => 'BCA Baru']]);
        $res = $this->withToken($a)->postJson('/api/sync', ['cursor' => $cursor, 'changes' => [$new]])->json();
        $this->assertSame('BCA Baru', $res['changes'][0]['data']['name']);

        // pengguna lain tidak melihat data ini
        $this->app['auth']->forgetGuards();
        $this->withToken($b)->postJson('/api/sync', ['cursor' => 0, 'changes' => []])->assertOk()->assertJsonCount(0, 'changes');
    }

    public function test_forgot_and_reset_password(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $this->register();
        $this->postJson('/api/forgot-password', ['email' => 'diki@example.com'])->assertOk();
        $user = User::where('username', 'diki')->first();
        $token = null;
        \Illuminate\Support\Facades\Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class,
            function ($n) use (&$token, $user) {
                $token = $n->token;
                $this->assertStringContainsString('/reset-password/', $n->toMail($user)->actionUrl);

                return true;
            });
        $this->get("/reset-password/{$token}?email=diki@example.com")->assertOk()->assertSee('Buat password baru');
        $this->post('/reset-password', [
            'token' => $token, 'email' => 'diki@example.com', 'password' => 'barubaru1', 'password_confirmation' => 'barubaru1',
        ])->assertOk()->assertSee('Password diperbarui');
        $this->postJson('/api/login', ['login' => 'diki', 'password' => 'barubaru1'])->assertOk();
    }

    public function test_premium_command(): void
    {
        $this->register();
        $this->artisan('user:premium', ['username' => 'diki'])->assertSuccessful();
        $this->assertTrue(User::where('username', 'diki')->first()->isPremium());
    }
}
