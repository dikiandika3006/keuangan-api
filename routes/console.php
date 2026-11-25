<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

// Atur paket premium sebelum Google Play Billing tersedia.
// Contoh: php artisan user:premium diki --months=12   |   php artisan user:premium diki --revoke
Artisan::command('user:premium {username} {--months=0 : 0 = selamanya} {--revoke}', function (string $username) {
    $user = User::where('username', strtolower($username))->firstOrFail();
    if ($this->option('revoke')) {
        $user->forceFill(['plan' => 'free', 'premium_until' => null])->save();
        $this->info("{$user->username} kembali ke paket gratis.");

        return;
    }
    $months = (int) $this->option('months');
    $user->forceFill(['plan' => 'premium', 'premium_until' => $months > 0 ? now()->addMonths($months) : null])->save();
    $this->info("{$user->username} sekarang premium".($months > 0 ? " sampai {$user->premium_until}" : ' selamanya').'.');
})->purpose('Atur paket premium pengguna');
