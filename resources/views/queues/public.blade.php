@extends('layouts.app')
@section('title', 'Ambil nomor antrean')
@section('content')
<section class="public-wrap"><div class="office-mark">S</div><div class="public-kicker">SISTEM ANTREAN PELAYANAN KECAMATAN</div><h1>Ambil nomor antrean</h1><p class="muted">Isi data berikut untuk mendapatkan nomor pelayanan hari ini.</p>
<form method="post" action="{{ route('queues.public.store') }}" class="form-panel">@csrf
@if(session('registration_error'))<div class="field-error" role="alert">{{ session('registration_error') }}</div>@endif
<label for="name">Nama lengkap</label><input id="name" name="name" class="form-control" maxlength="120" required value="{{ old('name') }}" placeholder="Masukkan nama Anda" autocomplete="name">
<label for="category_id" class="mt-4">Kategori pelayanan</label><select id="category_id" name="category_id" class="form-select" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select>
<label for="service" class="mt-4">Keperluan pelayanan</label><textarea id="service" name="service" class="form-control" maxlength="500" required placeholder="Tuliskan keperluan Anda">{{ old('service') }}</textarea>
@foreach(['name', 'category_id', 'service'] as $field) @error($field)<div class="field-error">{{ $message }}</div>@enderror @endforeach
@if($categories->isEmpty())<div class="field-error">Pendaftaran belum tersedia. Silakan minta bantuan petugas.</div>@endif
<button class="btn-primary-wide mt-4" @disabled($categories->isEmpty())>Ambil nomor antrean</button></form><p class="help-note">Nomor antrean berlaku untuk hari ini. Simpan halaman hasil pendaftaran dan tunggu panggilan petugas.</p><footer>Kantor Kecamatan · Pelayanan yang tertib dan mudah</footer></section>
@endsection
