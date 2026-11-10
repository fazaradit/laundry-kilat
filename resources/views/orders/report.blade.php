@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    <div class="toolbar">
        <div>
            <h1>Laporan Pesanan</h1>
            <p class="subtitle">
                Data di halaman ini diambil dari <code>GET /api/orders</code>, bukan query langsung ke database.
            </p>
        </div>

        <div class="filter-form">
            <form action="{{ route('orders.report') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="dari">Dari</label>
                    <input type="date" id="dari" name="dari" value="{{ $dari }}">
                </div>
                <div class="form-group">
                    <label for="sampai">Sampai</label>
                    <input type="date" id="sampai" name="sampai" value="{{ $sampai }}">
                </div>
                <div class="form-group">
                    <label for="status_laporan">Status</label>
                    <select id="status_laporan" name="status">
                        <option value="">Semua</option>
                        @foreach ($statusOptions as $opsi)
                            <option value="{{ $opsi->value }}" @selected($status === $opsi->value)>{{ $opsi->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
            </form>
        </div>
    </div>

    <div class="overview">
        <div class="overview-primary">
            <div>
                <div class="label">Nilai pesanan periode ini</div>
                <div class="stat-value">Rp {{ number_format($totalNilai, 0, ',', '.') }}</div>
            </div>
            <div class="note">
                Jumlah total seluruh pesanan yang masuk pada {{ \Illuminate\Support\Carbon::parse($dari)->format('d M Y') }}
                sampai {{ \Illuminate\Support\Carbon::parse($sampai)->format('d M Y') }}.
            </div>
        </div>

        <div class="metric-list">
            <div class="metric-row">
                <span class="label">Uang diterima</span>
                <span class="stat-value">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</span>
            </div>
            <div class="metric-row">
                <span class="label">Jumlah pesanan</span>
                <span class="stat-value">{{ number_format($pesanan->total(), 0, ',', '.') }}</span>
            </div>
            <div class="metric-row">
                <span class="label">Belum tertagih</span>
                <span class="stat-value">Rp {{ number_format(max(0, $totalNilai - $totalDibayar), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Pelanggan</th>
                    <th>Tanggal Masuk</th>
                    <th class="num" style="text-align: right;">Total</th>
                    <th class="num" style="text-align: right;">Dibayar</th>
                    <th>Status</th>
                    <th>Pelunasan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pesanan as $baris)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('orders.show', $baris['id']) }}" class="link-strong">{{ $baris['kode_order'] }}</a>
                        </td>
                        <td>{{ $baris['customer']['nama'] ?? '-' }}</td>
                        <td class="nowrap num">{{ $baris['tanggal_masuk'] }}</td>
                        <td class="num" style="text-align: right;">Rp {{ number_format($baris['total'], 0, ',', '.') }}</td>
                        <td class="num" style="text-align: right;">Rp {{ number_format($baris['dibayar'], 0, ',', '.') }}</td>
                        <td>{{ \App\Enums\OrderStatus::from($baris['status'])->label() }}</td>
                        <td>
                            <span class="badge {{ $baris['status_pelunasan'] === 'Lunas' ? 'badge-success' : ($baris['status_pelunasan'] === 'DP' ? 'badge-warning' : 'badge-neutral') }}">
                                {{ $baris['status_pelunasan'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">
                            Tidak ada pesanan pada periode ini, atau server API internal sedang tidak aktif.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $pesanan->links() }}
    </div>
@endsection
