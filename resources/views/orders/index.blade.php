@extends('layouts.app')

@section('title', 'Pesanan')

@section('content')
    <div class="toolbar">
        <div>
            <h1>Pesanan</h1>
            <p class="subtitle">Pesanan yang tercatat beserta status pengerjaan dan pelunasannya.</p>
        </div>

        <div class="filter-form">
            <form action="{{ route('orders.index') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="q">Cari</label>
                    <input type="search" id="q" name="q" value="{{ $search }}" placeholder="Kode atau nama pelanggan">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Semua</option>
                        @foreach ($statusOptions as $opsi)
                            <option value="{{ $opsi->value }}" @selected($status === $opsi->value)>{{ $opsi->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('orders.index') }}" class="btn btn-outline btn-sm">Reset</a>
                @endif
            </form>
            <a href="{{ route('orders.create') }}" class="btn">+ Pesanan</a>
        </div>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Pelanggan</th>
                    <th>Kasir</th>
                    <th class="num" style="text-align: right;">Total</th>
                    <th>Status</th>
                    <th>Pelunasan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('orders.show', $order) }}" class="link-strong">{{ $order->kode_order }}</a>
                            <div class="muted">{{ $order->tanggal_masuk->format('d M Y H:i') }}</div>
                        </td>
                        <td>{{ $order->customer->nama }}</td>
                        <td class="muted">{{ $order->user->name }}</td>
                        <td class="num" style="text-align: right;">
                            Rp {{ number_format($order->total, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $order->status_pelunasan_badge }}">{{ $order->status_pelunasan }}</span>
                            @if ($order->sisa_bayar > 0)
                                <div class="muted num">sisa Rp {{ number_format($order->sisa_bayar, 0, ',', '.') }}</div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="btn btn-outline btn-sm">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">
                            @if ($search !== '' || $status !== '')
                                Tidak ada pesanan yang cocok dengan filter tersebut.
                            @else
                                Belum ada pesanan. Klik "+ Pesanan" untuk membuat yang pertama.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $orders->links() }}
    </div>
@endsection
