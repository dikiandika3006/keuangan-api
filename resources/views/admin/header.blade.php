<style>
    header nav a { padding: 6px 12px; border-radius: 999px; text-decoration: none; color: var(--fg); }
    header nav a.on { background: var(--pri-c); color: #04382F; font-weight: 600; }
    .stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-bottom: 18px; }
    .stat { padding: 14px; display: flex; flex-direction: column; gap: 2px; }
    .stat span { color: var(--mut); font-size: 12px; }
    .stat b { font-size: 22px; }
    .stat small { color: var(--mut); font-size: 11px; }
    .chart { grid-column: span 2; }
    .bars { display: flex; align-items: flex-end; gap: 8px; height: 64px; margin-top: 4px; }
    .bars div { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .bars i { display: block; width: 100%; max-width: 26px; background: var(--pri); border-radius: 5px 5px 2px 2px; }
    .bars em { font-style: normal; font-size: 10px; color: var(--mut); margin-top: 3px; }
</style>
<header>
    <div style="display:flex;align-items:center;gap:14px">
        <b>Admin · Keuangan Pribadi</b>
        <nav style="display:flex;gap:4px">
            <a href="{{ route('admin.users') }}" class="{{ $active === 'users' ? 'on' : '' }}">Pengguna</a>
            <a href="{{ route('admin.crashes') }}" class="{{ $active === 'crashes' ? 'on' : '' }}">Crash</a>
        </nav>
    </div>
    <form method="POST" action="{{ route('admin.logout') }}" style="margin:0">@csrf<button class="btn-ghost">Keluar</button></form>
</header>
