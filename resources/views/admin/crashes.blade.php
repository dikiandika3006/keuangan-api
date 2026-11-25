@extends('admin.layout')
@section('title', 'Crash')
@section('body')
@include('admin.header', ['active' => 'crashes'])
<style>
    pre { white-space: pre-wrap; word-break: break-word; font-size: 11px; background: #F0F5F3; padding: 10px; border-radius: 8px; max-height: 360px; overflow: auto; }
    .row { padding: 12px 14px; border-bottom: 1px solid var(--line); }
    .row:last-child { border-bottom: 0; }
    .muted { color: var(--mut); font-size: 12px; }
    summary { cursor: pointer; }
</style>
<main>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <h3 style="margin:0 0 8px">Paling sering (30 hari)</h3>
    <div class="card" style="margin-bottom:18px">
        @forelse ($top as $t)
            <div class="row"><b>{{ $t->total }}×</b> {{ $t->message }}
                <div class="muted">v{{ $t->app_version ?? '?' }} · terakhir {{ \Illuminate\Support\Carbon::parse($t->last_at)->timezone('Asia/Jakarta')->format('d M H:i') }}</div></div>
        @empty
            <div class="row muted">Tidak ada crash dalam 30 hari terakhir. 🎉</div>
        @endforelse
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <h3 style="margin:0">Semua laporan</h3>
        @if ($crashes->total())
            <form method="POST" action="{{ route('admin.crashes.clear') }}" onsubmit="return confirm('Hapus semua laporan crash?')" style="margin:0">@csrf<button class="btn-danger">Hapus semua</button></form>
        @endif
    </div>
    <div class="card">
        @forelse ($crashes as $c)
            <details class="row">
                <summary><b>{{ $c->message }}</b>
                    <div class="muted">{{ ($c->occurred_at ?? $c->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} · v{{ $c->app_version ?? '?' }} · {{ $c->device ?? '-' }} · Android {{ $c->android ?? '-' }} · {{ $c->user ? '@'.$c->user->username : 'belum login' }}</div>
                </summary>
                <pre>{{ $c->stack }}</pre>
            </details>
        @empty
            <div class="row muted">Belum ada laporan crash.</div>
        @endforelse
    </div>
    @if ($crashes->hasPages())
        <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
            @if ($crashes->previousPageUrl())<a class="btn btn-ghost" href="{{ $crashes->previousPageUrl() }}">‹ Sebelumnya</a>@endif
            <span class="muted">Halaman {{ $crashes->currentPage() }} / {{ $crashes->lastPage() }}</span>
            @if ($crashes->nextPageUrl())<a class="btn btn-ghost" href="{{ $crashes->nextPageUrl() }}">Berikutnya ›</a>@endif
        </div>
    @endif
</main>
@endsection
