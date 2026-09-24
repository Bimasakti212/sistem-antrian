@extends('layouts.app')
@section('title', 'Ambil nomor antrean')
@section('content')
<section class="public-wrap"><div class="public-kicker">PELAYANAN KECAMATAN</div><h1>Ambil nomor antrean</h1><p class="muted">Isi data berikut untuk mendapatkan nomor pelayanan hari ini.</p>
<form method="post" action="{{ route('queues.public.store') }}" class="form-panel">@csrf
<label for="name">Nama lengkap</label><input id="name" name="name" class="form-control" maxlength="120" required value="{{ old('name') }}" placeholder="Masukkan nama Anda">
<label for="service" class="mt-4">Keperluan pelayanan</label><input id="service" name="service" class="form-control" maxlength="180" required value="{{ old('service') }}" placeholder="Contoh: Surat keterangan domisili">
@error('name')<div class="field-error">{{ $message }}</div>@enderror @error('service')<div class="field-error">{{ $message }}</div>@enderror
<button class="btn-primary-wide mt-4">Ambil nomor</button></form><p class="help-note">Nomor berlaku untuk hari ini. Silakan menunggu panggilan petugas.</p><footer>Kantor Kecamatan · Pelayanan yang tertib dan mudah</footer></section>
@endsection
