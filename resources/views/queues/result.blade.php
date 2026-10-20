@extends('layouts.app')
@section('title', 'Nomor '.$queue->code)
@section('content')
<section class="public-wrap result-wrap"><div class="public-kicker">NOMOR ANTREAN ANDA</div><div class="ticket-number">{{ $queue->code }}</div><div class="ticket-service">{{ $queue->category?->name ?? 'Kategori pelayanan' }}</div><p class="result-note">Keperluan: {{ $queue->service }}</p><div class="status-pill">{{ ['waiting'=>'Menunggu','called'=>'Dipanggil','serving'=>'Sedang dilayani','skipped'=>'Dilewati','completed'=>'Selesai'][$queue->status] ?? 'Menunggu' }}</div><p class="result-note">Silakan menunggu panggilan dari petugas. Perhatikan pengeras suara.</p></section>
@endsection

