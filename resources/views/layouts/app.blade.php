<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Antrean') · Kecamatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/sitrian-ui.css') }}" rel="stylesheet">
</head>
<body>
@auth
<header class="topbar"><button class="menu-toggle" aria-label="Buka navigasi" aria-expanded="false" aria-controls="slideNav" id="menuToggle">☰</button><a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">S</span><span>SITRIAN<small>Sistem antrean pelayanan kecamatan</small></span></a><span class="staff">{{ auth()->user()->name }} · {{ auth()->user()->role === 'admin' ? 'Admin' : 'Petugas' }}</span></header>
<button type="button" class="nav-shade" id="navShade" aria-label="Tutup navigasi" tabindex="-1"></button><aside class="slide-nav" id="slideNav"><div class="nav-heading">MENU PETUGAS</div><a href="{{ route('profile.edit') }}">Edit profil</a><a href="{{ route('dashboard') }}">Dashboard antrean</a><a href="{{ route('queues.history') }}">Riwayat pelayanan</a><a href="{{ route('queues.qr') }}">QR pengambilan</a>@can('admin')<a href="{{ route('queues.categories') }}">Kategori</a><a href="{{ route('queues.counters') }}">Loket</a><a href="{{ route('queues.staff') }}">Petugas</a>@endcan<form method="post" action="{{ route('logout') }}">@csrf<button>Keluar</button></form></aside>
@endauth
<main class="page-shell">@yield('content')</main>
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/sitrian-ui.js') }}"></script>
@stack('scripts')
</body></html>
