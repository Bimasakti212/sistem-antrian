@extends('layouts.app')
@section('title', 'Kelola petugas')
@section('content')
<div class="dash-head"><div><div class="public-kicker">PENGATURAN AKUN</div><h1>Petugas</h1></div></div>
@if(session('success'))<p class="success-message">{{ session('success') }}</p>@endif
<form class="form-panel compact-form" method="post" action="{{ route('queues.staff.store') }}">@csrf<h2>Tambah akun petugas</h2><label class="mt-3" for="staff-name">Nama</label><input id="staff-name" class="form-control" name="name" maxlength="120" required><label class="mt-3" for="staff-email">Email</label><input id="staff-email" type="email" class="form-control" name="email" required><label class="mt-3" for="staff-password">Kata sandi sementara (minimal 12 karakter)</label><input id="staff-password" type="password" class="form-control" name="password" minlength="12" required><label class="mt-3" for="staff-password-confirmation">Ulangi kata sandi</label><input id="staff-password-confirmation" type="password" class="form-control" name="password_confirmation" required><button class="btn-primary-wide mt-4">Buat akun</button>@if($errors->any())<p class="field-error">{{ $errors->first() }}</p>@endif</form>
<div class="table-responsive table-shell mt-4"><table class="table align-middle"><thead><tr><th>Nama</th><th>Email</th><th>Peran</th></tr></thead><tbody>@foreach($staff as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role === 'admin' ? 'Admin' : 'Petugas' }}</td></tr>@endforeach</tbody></table></div><div class="pagination-wrap">{{ $staff->links() }}</div>
@endsection
