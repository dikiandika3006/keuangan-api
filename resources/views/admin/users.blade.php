@extends('admin.layout')
@section('title', 'Pengguna')
@section('body')
<style>
    .tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .tabs a { padding: 7px 14px; border-radius: 999px; text-decoration: none; color: var(--fg); border: 1px solid var(--line); background: #fff; }
    .tabs a.on { background: var(--pri); color: #fff; border-color: var(--pri); }
    .tabs small { opacity: .75; margin-left: 4px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 12px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }
    th { font-size: 12px; color: var(--mut); font-weight: 600; }
    tr:last-child td { border-bottom: 0; }
    .muted { color: var(--mut); font-size: 12px; }
    .badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .b-prem { background: var(--warn-c); color: var(--warn); }
    .b-free { background: #EEF2F1; color: var(--mut); }
    .b-req { background: var(--pri-c); color: #04382F; }
    .acts { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .acts form { display: flex; gap: 6px; margin: 0; }
    @media (max-width: 760px) {
        thead { display: none; }
        tr { display: block; border-bottom: 1px solid var(--line); padding: 8px 0; }
        td { display: block; border: 0; padding: 6px 14px; }
    }
</style>
@include('admin.header', ['active' => 'users'])
<main>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stats">
        <div class="card stat"><span>Pengguna</span><b>{{ $stats['users'] }}</b><small>+{{ $stats['new_month'] }} bulan ini</small></div>
        <div class="card stat"><span>Aktif 7 hari</span><b>{{ $stats['active7'] }}</b><small>{{ $stats['active30'] }} dalam 30 hari</small></div>
        <div class="card stat"><span>Premium aktif</span><b>{{ $counts['premium'] }}</b><small>{{ $counts['request'] }} pengajuan menunggu</small></div>
        <div class="card stat"><span>Data tersinkron</span><b>{{ number_format($stats['records'], 0, ',', '.') }}</b><small>baris</small></div>
        <a class="card stat" href="{{ route('admin.crashes') }}" style="text-decoration:none;color:inherit">
            <span>Crash 7 hari</span><b style="color:{{ $stats['crashes7'] ? 'var(--err)' : 'inherit' }}">{{ $stats['crashes7'] }}</b><small>lihat detail ›</small>
        </a>
        <div class="card stat chart">
            <span>Pendaftar 6 bulan</span>
            @php($max = max(1, $stats['months']->max('count')))
            <div class="bars">
                @foreach ($stats['months'] as $m)
                    <div title="{{ $m['count'] }}"><i style="height:{{ max(3, round($m['count'] / $max * 44)) }}px"></i><em>{{ $m['label'] }}</em></div>
                @endforeach
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px">
        <nav class="tabs">
            @foreach (['request' => 'Pengajuan', 'premium' => 'Premium aktif', 'all' => 'Semua'] as $k => $label)
                <a href="{{ route('admin.users', ['filter' => $k, 'q' => $q ?: null]) }}" class="{{ $filter === $k ? 'on' : '' }}">{{ $label }}<small>{{ $counts[$k] }}</small></a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('admin.users') }}" style="display:flex;gap:6px">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input name="q" value="{{ $q }}" placeholder="Cari nama, username, email">
            <button class="btn-ghost">Cari</button>
        </form>
    </div>

    <div class="card" style="overflow:hidden">
        <table>
            <thead><tr><th>Pengguna</th><th>Status</th><th>Pengajuan</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($users as $u)
                @php($prem = $u->isPremium())
                <tr>
                    <td>
                        <b>{{ $u->name }}</b><br>
                        <span class="muted">{{ '@'.$u->username }} · {{ $u->email }}</span><br>
                        <span class="muted">Daftar {{ $u->created_at?->timezone('Asia/Jakarta')->format('d M Y') }}</span>
                    </td>
                    <td>
                        @if ($prem)
                            <span class="badge b-prem">Premium</span><br>
                            <span class="muted">{{ $u->premium_until ? 's.d. '.$u->premium_until->timezone('Asia/Jakarta')->format('d M Y') : 'Selamanya' }}</span>
                        @else
                            <span class="badge b-free">Gratis</span>
                            @if ($u->plan === 'premium' && $u->premium_until)<br><span class="muted">Kedaluwarsa {{ $u->premium_until->timezone('Asia/Jakarta')->format('d M Y') }}</span>@endif
                        @endif
                    </td>
                    <td>
                        @if ($u->premium_requested_at && ! $prem)
                            <span class="badge b-req">Menunggu</span><br>
                            <span class="muted">{{ $u->premium_requested_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</span>
                            @if ($u->premium_request_note)<div style="margin-top:4px">“{{ $u->premium_request_note }}”</div>@endif
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="acts">
                            <form method="POST" action="{{ route('admin.approve', $u) }}">
                                @csrf
                                <select name="months">
                                    <option value="1">1 bulan</option>
                                    <option value="3">3 bulan</option>
                                    <option value="6">6 bulan</option>
                                    <option value="12" selected>12 bulan</option>
                                    <option value="0">Selamanya</option>
                                </select>
                                <button>{{ $prem ? 'Perpanjang' : 'Setujui' }}</button>
                            </form>
                            @if ($u->premium_requested_at && ! $prem)
                                <form method="POST" action="{{ route('admin.reject', $u) }}">@csrf<button class="btn-ghost">Tolak</button></form>
                            @endif
                            @if ($prem)
                                <form method="POST" action="{{ route('admin.revoke', $u) }}" onsubmit="return confirm('Kembalikan {{ $u->username }} ke paket Gratis?')">@csrf<button class="btn-danger">Cabut</button></form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;padding:32px" class="muted">
                    {{ $filter === 'request' ? 'Belum ada pengajuan Premium.' : 'Tidak ada pengguna.' }}
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())
        <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
            @if ($users->previousPageUrl())<a class="btn btn-ghost" href="{{ $users->previousPageUrl() }}">‹ Sebelumnya</a>@endif
            <span class="muted">Halaman {{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
            @if ($users->nextPageUrl())<a class="btn btn-ghost" href="{{ $users->nextPageUrl() }}">Berikutnya ›</a>@endif
        </div>
    @endif
</main>
@endsection
