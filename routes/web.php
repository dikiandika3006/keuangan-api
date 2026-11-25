<?php

use App\Http\Controllers\AdminController;
use App\Http\Middleware\EnsureAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['app' => 'Keuangan Pribadi API', 'status' => 'ok']));

// Dashboard admin: persetujuan & status Premium.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'loginForm'])->name('login');
    Route::post('/login', [AdminController::class, 'login'])->middleware('throttle:5,1');
    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::get('/', [AdminController::class, 'users'])->name('users');
        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
        Route::post('/users/{user}/approve', [AdminController::class, 'approve'])->name('approve');
        Route::post('/users/{user}/revoke', [AdminController::class, 'revoke'])->name('revoke');
        Route::post('/users/{user}/reject', [AdminController::class, 'reject'])->name('reject');
        Route::get('/crashes', [AdminController::class, 'crashes'])->name('crashes');
        Route::post('/crashes/clear', [AdminController::class, 'clearCrashes'])->name('crashes.clear');
    });
});

// Halaman reset password dari tautan email "lupa password".
Route::get('/reset-password/{token}', fn (Request $request, string $token) => view('reset-password', [
    'token' => $token,
    'email' => (string) $request->query('email'),
]))->name('password.reset');

Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => ['required'],
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ], [
        'password.confirmed' => 'Konfirmasi password tidak sama.',
        'password.min' => 'Password minimal 8 karakter.',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {
            $user->forceFill(['password' => $password])->save();
            $user->tokens()->delete(); // keluarkan semua perangkat
        }
    );

    return $status === Password::PASSWORD_RESET
        ? view('reset-password', ['done' => true])
        : back()->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.'])->withInput();
})->name('password.update');
