@extends('layouts.app')
@section('title', 'QR pengambilan antrean')
@section('content')
<section class="qr-sheet">
    <div class="public-kicker">INFORMASI PENGUNJUNG</div>
    <h1>Ambil nomor antrean</h1>
    <p>Arahkan kamera ponsel ke QR Code untuk membuka halaman pengambilan nomor.</p>
    @if($registrationUrl)
        <div class="qr-image"><img alt="QR code menuju halaman antrean warga" src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&amp;data={{ urlencode($registrationUrl) }}"></div>
        <strong>SCAN QR CODE UNTUK MENGAMBIL NOMOR ANTREAN</strong>
        <p class="qr-url">{{ $registrationUrl }}</p>
        <button class="btn-outline" type="button" onclick="window.print()">Cetak QR Code</button>
    @else
        <p class="qr-unavailable" role="alert">QR belum tersedia karena halaman ini dibuka melalui alamat lokal komputer. Buka halaman ini menggunakan alamat jaringan kantor atau domain yang dapat dijangkau ponsel, lalu muat ulang. Jangan gunakan localhost atau 127.0.0.1.</p>
    @endif
</section>
@endsection