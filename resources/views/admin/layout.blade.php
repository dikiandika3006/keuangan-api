<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') · Keuangan Pribadi</title>
    <style>
        :root { --pri: #0B6E5F; --pri-c: #D6F0EA; --bg: #F6FAF8; --fg: #161D1B; --mut: #56625E; --line: #DCE5E2; --err: #BA1A1A; --warn: #8A5A00; --warn-c: #FFEFD1; }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--fg); margin: 0; font-size: 14px; }
        a { color: var(--pri); }
        header { background: #fff; border-bottom: 1px solid var(--line); padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; }
        header b { font-size: 16px; }
        main { max-width: 1100px; margin: 0 auto; padding: 20px; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 14px; }
        input, select { padding: 9px 11px; border: 1px solid #A9B6B2; border-radius: 9px; font-size: 14px; background: #fff; }
        button, .btn { padding: 8px 14px; border-radius: 999px; border: 1px solid transparent; font-size: 13px; font-weight: 600; cursor: pointer; background: var(--pri); color: #fff; text-decoration: none; display: inline-block; }
        .btn-ghost { background: #fff; color: var(--fg); border-color: #A9B6B2; }
        .btn-danger { background: #fff; color: var(--err); border-color: #E8B4B4; }
        .flash { background: var(--pri-c); color: #04382F; padding: 10px 14px; border-radius: 10px; margin-bottom: 14px; }
        .err { color: var(--err); font-size: 13px; margin-top: 8px; }
    </style>
</head>
<body>
@yield('body')
</body>
</html>
