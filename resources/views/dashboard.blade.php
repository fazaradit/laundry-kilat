@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1>Dashboard</h1>
    <p class="subtitle">Ringkasan operasional hari ini, diambil dari <code>GET /api/stats</code>.</p>

    <div class="overview">
        <div class="overview-primary">
            <div>
                <div class="label">Pendapatan hari ini</div>
                <div class="stat-value" style="margin-top: 10px;">
                    Rp {{ number_format($stats['pendapatan_hari_ini'], 0, ',', '.') }}
                </div>
            </div>
            <div class="note">
                Dihitung dari pembayaran yang tercatat sejak pukul 00.00 hari ini.
            </div>
        </div>

        <div class="metric-list">
            <div class="metric-row">
                <span class="label">Pesanan hari ini</span>
                <span class="stat-value">{{ number_format($stats['pesanan_hari_ini'], 0, ',', '.') }}</span>
            </div>
            <div class="metric-row">
                <span class="label">Pesanan aktif</span>
                <span class="stat-value">{{ number_format($stats['pesanan_aktif'], 0, ',', '.') }}</span>
            </div>
            <div class="metric-row">
                <span class="label">Total pelanggan</span>
                <span class="stat-value">{{ number_format($stats['total_pelanggan'] ?? 0, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="page-actions">
        <a href="{{ route('customers.index') }}" class="btn">Kelola pelanggan</a>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('services.index') }}" class="btn btn-secondary">Kelola layanan</a>
        @endif
    </div>
@endsection
