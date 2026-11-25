<?php

namespace App\Http\Controllers;

use App\Models\CrashReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

        return view('admin.users', compact('users', 'filter', 'q', 'counts') + ['stats' => $this->stats()]);
    }

    /** Ringkasan untuk kartu statistik di atas daftar pengguna. */
    private function stats(): array
    {
        $active = fn (int $days) => DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)->where('last_used_at', '>=', now()->subDays($days))
            ->distinct()->count('tokenable_id');

        // Pendaftar per bulan, 6 bulan terakhir (dihitung di PHP agar sama di MySQL & SQLite).
        $from = now()->startOfMonth()->subMonths(5);
        $byMonth = User::where('created_at', '>=', $from)->pluck('created_at')
            ->groupBy(fn ($d) => $d->format('Y-m'))->map->count();
        $months = collect(range(0, 5))->map(function ($i) use ($from, $byMonth) {
            $m = $from->copy()->addMonths($i);
            return ['label' => $m->locale('id')->translatedFormat('M'), 'count' => $byMonth[$m->format('Y-m')] ?? 0];
        });

        return [
            'users' => User::count(),
            'new_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            'active7' => $active(7),
            'active30' => $active(30),
            'records' => DB::table('sync_records')->whereNull('deleted_at')->count(),
            'crashes7' => CrashReport::where('created_at', '>=', now()->subDays(7))->count(),
            'months' => $months,
        ];
    }

    public function crashes(): View
    {
        $crashes = CrashReport::with('user:id,username')->latest()->paginate(30);
        // Kelompokkan pesan yang sama agar terlihat crash mana yang paling sering.
        $top = CrashReport::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('message, app_version, count(*) as total, max(created_at) as last_at')
            ->groupBy('message', 'app_version')->orderByDesc('total')->limit(10)->get();

        return view('admin.crashes', compact('crashes', 'top'));
    }

    public function clearCrashes(): RedirectResponse
    {
        CrashReport::query()->delete();

        return back()->with('status', 'Semua laporan crash dihapus.');
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
