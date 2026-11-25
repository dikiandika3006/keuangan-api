<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password · Keuangan Pribadi</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #F6FAF8; color: #161D1B; margin: 0; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .card { background: #fff; border: 1px solid #C4D1CD; border-radius: 16px; padding: 24px; width: min(380px, 90vw); }
        h1 { font-size: 22px; margin: 0 0 8px; }
        p { color: #56625E; font-size: 14px; }
        label { display: block; font-size: 13px; margin: 12px 0 4px; color: #56625E; }
        input { width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid #7A8783; border-radius: 10px; font-size: 16px; }
        button { margin-top: 16px; width: 100%; padding: 14px; background: #0B6E5F; color: #fff; border: 0; border-radius: 999px; font-size: 15px; font-weight: 600; }
        .err { color: #BA1A1A; font-size: 13px; margin-top: 8px; }
    </style>
</head>
<body>
<div class="card">
    @if (!empty($done))
        <h1>Password diperbarui</h1>
        <p>Silakan kembali ke aplikasi Keuangan Pribadi dan masuk dengan password baru.</p>
    @else
        <h1>Buat password baru</h1>
        <p>Untuk akun {{ old('email', $email) }}</p>
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ old('email', $email) }}">
            <label for="password">Password baru (min. 8 karakter)</label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
            <label for="password_confirmation">Ulangi password baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
            @foreach ($errors->all() as $e)
                <div class="err">{{ $e }}</div>
            @endforeach
            <button type="submit">Simpan password</button>
        </form>
    @endif
</div>
</body>
</html>
