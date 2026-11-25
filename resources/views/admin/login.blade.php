@extends('admin.layout')
@section('title', 'Masuk admin')
@section('body')
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center">
    <form method="POST" action="{{ route('admin.login') }}" class="card" style="padding:24px;width:min(360px,90vw)">
        @csrf
        <h1 style="font-size:20px;margin:0 0 4px">Admin Keuangan Pribadi</h1>
        <p style="color:var(--mut);margin:0 0 16px">Masuk untuk mengelola paket pengguna.</p>
        <label style="display:block;font-size:13px;color:var(--mut);margin-bottom:4px">Username</label>
        <input name="username" value="{{ old('username') }}" required autofocus style="width:100%">
        <label style="display:block;font-size:13px;color:var(--mut);margin:12px 0 4px">Password</label>
        <input type="password" name="password" required style="width:100%">
        @foreach ($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
        <button type="submit" style="width:100%;margin-top:16px;padding:12px">Masuk</button>
    </form>
</div>
@endsection
