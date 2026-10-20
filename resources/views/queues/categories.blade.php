@extends('layouts.app')
@section('title', 'Kelola kategori')
@section('content')
<div class="dash-head"><div><div class="public-kicker">PENGATURAN LAYANAN</div><h1>Kategori pelayanan</h1></div></div>
@if(session('success'))<p class="success-message">{{ session('success') }}</p>@endif
<form class="form-panel compact-form" method="post" action="{{ route('queues.categories.store') }}">@csrf<label for="new-category-name">Nama kategori baru</label><input id="new-category-name" class="form-control" name="name" maxlength="120" required><button class="btn-primary-wide mt-3">Tambah kategori</button>@error('name')<p class="field-error">{{ $message }}</p>@enderror</form>
<div class="table-responsive table-shell mt-4"><table class="table align-middle"><thead><tr><th>Kategori</th><th>Jumlah antrean</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@foreach($categories as $category)<tr><td><form id="category-form-{{ $category->id }}" method="post" action="{{ route('queues.categories.update', $category) }}">@csrf @method('PUT')</form><input form="category-form-{{ $category->id }}" class="form-control" name="name" maxlength="120" value="{{ $category->name }}" required></td><td>{{ $category->queues_count }}</td><td><select form="category-form-{{ $category->id }}" class="form-select" name="is_active"><option value="1" @selected($category->is_active)>Aktif</option><option value="0" @selected(!$category->is_active)>Nonaktif</option></select></td><td><button form="category-form-{{ $category->id }}" class="btn-outline">Simpan</button></td></tr>@endforeach</tbody></table></div>
@endsection
