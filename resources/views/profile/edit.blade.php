@extends('layouts.app')
@section('title', 'Edit profil')
@section('content')
<div class="dash-head"><div><div class="public-kicker">AKUN SAYA</div><h1>Edit profil</h1></div></div>
<p class="muted">Perbarui nama dan email yang digunakan untuk akun petugas.</p>
@if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
<form class="form-panel compact-form" method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('put')
    <label for="profile-name">Nama</label>
    <input id="profile-name" class="form-control" name="name" maxlength="120" required value="{{ old('name', $user->name) }}">
    @error('name')<p class="field-error">{{ $message }}</p>@enderror
    <label class="mt-3" for="profile-email">Email</label>
    <input id="profile-email" type="email" class="form-control" name="email" maxlength="255" required value="{{ old('email', $user->email) }}">
    @error('email')<p class="field-error">{{ $message }}</p>@enderror
    <button class="btn-primary-wide mt-4">Simpan perubahan</button>
</form>
@endsection