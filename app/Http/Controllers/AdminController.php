<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Dashboard admin sederhana: menyetujui pengajuan Premium dan mengubah paket pengguna. */
class AdminController extends Controller
{
    public function loginForm(Request $request): View|RedirectResponse
    {
        return $request->session()->get('is_admin') ? redirect()->route('admin.users') : view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['username' => ['required', 'string'], 'password' => ['required', 'string']]);
        $password = (string) config('admin.password');

        if ($password === '' || ! hash_equals((string) config('admin.username'), $data['username'])
            || ! hash_equals($password, $data['password'])) {
            return back()->withErrors(['username' => 'Username atau password admin salah.'])->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('is_admin', true);

        return redirect()->route('admin.users');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function users(Request $request): View
    {
        $filter = $request->query('filter', 'request');
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($w) => $w
                ->where('name', 'like', "%$q%")->orWhere('username', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->when($filter === 'request', fn ($w) => $w->whereNotNull('premium_requested_at')
                ->where(fn ($w) => $w->where('plan', '!=', 'premium')->orWhere('premium_until', '<=', now())))
            ->when($filter === 'premium', fn ($w) => $w->where('plan', 'premium')
                ->where(fn ($w) => $w->whereNull('premium_until')->orWhere('premium_until', '>', now())))
            ->orderByDesc('premium_requested_at')->orderByDesc('created_at')
            ->paginate(50)->withQueryString();

        $counts = [
            'request' => User::whereNotNull('premium_requested_at')
                ->where(fn ($w) => $w->where('plan', '!=', 'premium')->orWhere('premium_until', '<=', now()))->count(),
            'premium' => User::where('plan', 'premium')
                ->where(fn ($w) => $w->whereNull('premium_until')->orWhere('premium_until', '>', now()))->count(),
            'all' => User::count(),
        ];

        return view('admin.users', compact('users', 'filter', 'q', 'counts'));
    }

    /** Aktifkan/perpanjang Premium. months = 0 berarti selamanya. */
    public function approve(Request $request, User $user): RedirectResponse
    {
        $months = (int) $request->validate(['months' => ['required', 'integer', 'in:0,1,3,6,12']])['months'];
        $base = $user->isPremium() && $user->premium_until ? $user->premium_until : now();

        $user->forceFill([
            'plan' => 'premium',
            'premium_until' => $months === 0 ? null : $base->copy()->addMonths($months),
            'premium_requested_at' => null,
            'premium_request_note' => null,
        ])->save();

        return back()->with('status', "{$user->username}: Premium ".($months === 0 ? 'selamanya' : "+$months bulan").'.');
    }

    public function revoke(User $user): RedirectResponse
    {
        $user->forceFill(['plan' => 'free', 'premium_until' => null])->save();

        return back()->with('status', "{$user->username}: kembali ke Gratis.");
    }

    public function reject(User $user): RedirectResponse
    {
        $user->forceFill(['premium_requested_at' => null, 'premium_request_note' => null])->save();

        return back()->with('status', "Pengajuan {$user->username} ditolak.");
    }
}
