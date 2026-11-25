<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.username' => 'admin', 'admin.password' => 'rahasia-admin']);
    }

    public function test_request_approve_reject_revoke(): void
    {
        $r = $this->postJson('/api/register', [
            'name' => 'Diki', 'username' => 'diki', 'email' => 'diki@example.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertCreated()->json();
        $user = User::firstWhere('username', 'diki');

        $this->withToken($r['token'])->postJson('/api/me/premium-request', ['note' => 'Sudah transfer'])
            ->assertOk()->assertJsonPath('user.premium_requested', true)->assertJsonPath('user.plan', 'free');

        // Belum login admin
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['username' => 'admin', 'password' => 'salah'])->assertSessionHasErrors('username');
        $this->post('/admin/login', ['username' => 'admin', 'password' => 'rahasia-admin'])->assertRedirect('/admin');

        $this->get('/admin')->assertOk()->assertSee('diki@example.com')->assertSee('Sudah transfer');

        $this->post("/admin/users/{$user->id}/approve", ['months' => 12])->assertRedirect();
        $user->refresh();
        $this->assertTrue($user->isPremium());
        $this->assertNull($user->premium_requested_at);
        $this->assertTrue($user->premium_until->isAfter(now()->addMonths(11)));
        $this->app['auth']->forgetGuards(); // guard menyimpan user dari request sebelumnya dalam satu tes
        $this->withToken($r['token'])->getJson('/api/me')
            ->assertJsonPath('user.plan', 'premium')->assertJsonPath('user.premium_requested', false);

        $this->post("/admin/users/{$user->id}/revoke")->assertRedirect();
        $this->assertFalse($user->refresh()->isPremium());

        $this->withToken($r['token'])->postJson('/api/me/premium-request')->assertOk();
        $this->post("/admin/users/{$user->id}/reject")->assertRedirect();
        $this->assertNull($user->refresh()->premium_requested_at);

        $this->post('/admin/users/'.$user->id.'/approve', ['months' => 0]);
        $this->assertNull($user->refresh()->premium_until);
        $this->assertTrue($user->isPremium());
    }

    public function test_crash_report_and_admin_pages(): void
    {
        $this->postJson('/api/crash', ['reports' => [[
            'message' => 'java.lang.IllegalStateException: boom', 'stack' => "at a.b(C.kt:1)\nat d.e(F.kt:2)",
            'app_version' => '1.4', 'device' => 'Xiaomi Redmi', 'android' => '14', 'occurred_at' => 1795521633789,
        ]]])->assertOk()->assertJsonPath('saved', 1);
        $this->postJson('/api/crash', ['reports' => []])->assertUnprocessable();

        $this->post('/admin/login', ['username' => 'admin', 'password' => 'rahasia-admin']);
        $this->get('/admin')->assertOk()->assertSee('Crash 7 hari')->assertSee('Pendaftar 6 bulan');
        $this->get('/admin/crashes')->assertOk()->assertSee('boom')->assertSee('Xiaomi Redmi');
        $this->post('/admin/crashes/clear')->assertRedirect();
        $this->assertSame(0, \App\Models\CrashReport::count());
    }

    public function test_sync_accepts_goal_and_iou(): void
    {
        $token = $this->postJson('/api/register', [
            'name' => 'A', 'username' => 'andi', 'email' => 'a@example.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->json('token');
        $this->withToken($token)->postJson('/api/sync', ['cursor' => 0, 'changes' => [
            ['entity' => 'goal', 'id' => 'g1', 'updated_at' => 1, 'deleted_at' => null, 'data' => ['id' => 'g1', 'name' => 'Darurat']],
            ['entity' => 'iou', 'id' => 'i1', 'updated_at' => 1, 'deleted_at' => null, 'data' => ['id' => 'i1', 'person' => 'Budi']],
        ]])->assertOk();
    }

    public function test_admin_disabled_without_password(): void
    {
        config(['admin.password' => '']);
        $this->post('/admin/login', ['username' => 'admin', 'password' => ''])->assertSessionHasErrors();
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
