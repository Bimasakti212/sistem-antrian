@extends('layouts.app')
@section('title', 'Nomor '.$queue->code)
@section('content')
<section class="public-wrap result-wrap"><div class="public-kicker">NOMOR ANTREAN ANDA</div><div class="ticket-number">{{ $queue->code }}</div><div class="ticket-service">{{ $queue->service }}</div><div class="status-pill">{{ $queue->status === 'waiting' ? 'Menunggu' : ucfirst($queue->status) }}</div><p class="result-note">Silakan menunggu panggilan dari petugas. Perhatikan layar dan pengeras suara.</p><a class="text-link" href="{{ route('queues.public') }}">Kembali ke halaman antrean</a></section>
@endsection
