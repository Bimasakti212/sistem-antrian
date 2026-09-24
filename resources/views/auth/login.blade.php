@extends('layouts.app')
@section('title', 'Masuk petugas')
@section('content')
<section class="login-wrap"><div class="public-kicker">AKSES PETUGAS</div><h1>Masuk ke sistem</h1><p class="muted">Gunakan akun petugas yang telah terdaftar.</p><form method="post" action="{{ route('login.store') }}" class="form-panel">@csrf<label>Email</label><input name="email" type="email" class="form-control" required value="{{ old('email') }}"><label class="mt-3">Kata sandi</label><input name="password" type="password" class="form-control" required>@error('email')<p class="field-error">{{ $message }}</p>@enderror<button class="btn-primary-wide mt-4">Masuk</button></form><a class="text-link" href="{{ route('queues.public') }}">Kembali ke antrean warga</a></section>
@endsection
