<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Antrean') · Kecamatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
@auth
<header class="topbar"><button class="menu-toggle" aria-label="Buka navigasi" id="menuToggle">☰</button><a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">K</span><span>KANTOR KECAMATAN<small>Sistem antrean pelayanan</small></span></a><span class="staff">{{ auth()->user()->name }}</span></header>
<div class="nav-shade" id="navShade"></div><aside class="slide-nav" id="slideNav"><div class="nav-heading">MENU PETUGAS</div><a href="{{ route('dashboard') }}">Ringkasan antrean</a><a href="{{ route('queues.history') }}">Riwayat pelayanan</a><a href="{{ route('queues.qr') }}">QR pengambilan</a><form method="post" action="{{ route('logout') }}">@csrf<button>Keluar</button></form></aside>
@endauth
<main class="page-shell">@yield('content')</main>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body></html>
