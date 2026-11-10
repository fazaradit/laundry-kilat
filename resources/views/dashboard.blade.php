@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1>Dashboard</h1>
    <p class="subtitle">Ringkasan operasional laundry, diambil dari <code>GET /api/stats</code>.</p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="value">{{ number_format($stats['pesanan_hari_ini'], 0, ',', '.') }}</div>
            <div class="label">Pesanan Hari Ini</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ number_format($stats['pesanan_aktif'], 0, ',', '.') }}</div>
            <div class="label">Pesanan Aktif</div>
        </div>
        <div class="stat-card">
            <div class="value" style="font-size: 26px;">
                Rp {{ number_format($stats['pendapatan_hari_ini'], 0, ',', '.') }}
            </div>
            <div class="label">Pendapatan Hari Ini</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ number_format($stats['total_pelanggan'] ?? 0, 0, ',', '.') }}</div>
            <div class="label">Total Pelanggan</div>
        </div>
    </div>

    <p style="margin-top: 24px;">
        <a href="{{ route('customers.index') }}" class="btn">Kelola Pelanggan</a>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('services.index') }}" class="btn btn-outline">Kelola Layanan</a>
        @endif
    </p>
@endsection
