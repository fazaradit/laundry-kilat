@extends('layouts.app')

@section('title', 'Detail Pesanan')

@section('content')
    <div class="toolbar">
        <div>
            <h1 class="kode-judul">
                {{ $order->kode_order }}
                <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                <span class="badge {{ $order->status_pelunasan_badge }}">{{ $order->status_pelunasan }}</span>
            </h1>
            <p class="subtitle">
                Masuk {{ $order->tanggal_masuk->format('d M Y H:i') }} oleh {{ $order->user->name }}
                | Estimasi selesai {{ $order->estimasi_selesai->format('d M Y') }}
            </p>
        </div>

        <div class="actions">
            <a href="{{ route('orders.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </div>

    <div class="detail-grid">
        <div class="card">
            <h2 class="card-title">Pelanggan</h2>
            <div class="detail-list">
                <div class="detail-row">
                    <span class="label">Nama</span>
                    <span>{{ $order->customer->nama }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Telepon</span>
                    <span class="num">{{ $order->customer->nomor_telepon }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Alamat</span>
                    <span>{{ $order->customer->alamat ?: '-' }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">Ringkasan</h2>
            <div class="summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="num">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row">
                    <span>Diskon</span>
                    <span class="num">- Rp {{ number_format($order->diskon, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span class="num">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row">
                    <span>Dibayar</span>
                    <span class="num">Rp {{ number_format($order->dibayar, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row">
                    <span>Sisa</span>
                    <span class="num">Rp {{ number_format($order->sisa_bayar, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title">Item Layanan</h2>
        <table>
            <thead>
                <tr>
                    <th>Layanan</th>
                    <th class="num" style="text-align: right;">Qty</th>
                    <th class="num" style="text-align: right;">Harga Satuan</th>
                    <th class="num" style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->service->nama_layanan }}</td>
                        <td class="num" style="text-align: right;">
                            {{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}
                            {{ $item->service->satuan }}
                        </td>
                        <td class="num" style="text-align: right;">
                            Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                        </td>
                        <td class="num" style="text-align: right;">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="form-hint" style="margin-top: 10px;">
            Harga di atas adalah snapshot saat pesanan dibuat. Perubahan harga di master layanan tidak mengubah baris ini.
        </p>
    </div>

    <div class="detail-grid">
        <div class="card">
            <h2 class="card-title">Riwayat Status</h2>
            <ol class="timeline">
                @foreach ($order->statusLogs->sortBy('id') as $log)
                    <li>
                        <div class="timeline-title">
                            {{ \App\Enums\OrderStatus::tryFrom($log->status_baru)?->label() ?? $log->status_baru }}
                        </div>
                        <div class="muted">
                            {{ $log->created_at->format('d M Y H:i') }} oleh {{ $log->user->name }}
                            @if ($log->status_lama !== '-')
                                <span class="nowrap">(dari {{ \App\Enums\OrderStatus::tryFrom($log->status_lama)?->label() ?? $log->status_lama }})</span>
                            @endif
                        </div>
                        @if ($log->catatan)
                            <div class="muted">{{ $log->catatan }}</div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="card">
            <h2 class="card-title">Ubah Status</h2>

            @if ($order->status->sudahFinal())
                <p class="muted">
                    Pesanan berstatus {{ $order->status->label() }} dan sudah final. Statusnya tidak dapat diubah lagi.
                </p>
            @else
                @php
                    $transisi = $order->status->transisiYangBoleh();
                    $bolehBatal = auth()->user()->isAdmin() && $order->status->bolehDibatalkan();
                @endphp

                <form action="{{ route('orders.status', $order) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="status">Status berikutnya</label>
                        <select id="status" name="status" required>
                            @foreach ($transisi as $opsi)
                                @if ($opsi === \App\Enums\OrderStatus::Dibatalkan)
                                    @continue
                                @endif
                                <option value="{{ $opsi->value }}">{{ $opsi->label() }}</option>
                            @endforeach
                        </select>
                        <span class="form-hint">Hanya langkah berikutnya yang sah yang ditampilkan.</span>
                        @error('status') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="catatan_status">Catatan <span class="muted">(opsional)</span></label>
                        <input type="text" id="catatan_status" name="catatan" value="{{ old('catatan') }}"
                               maxlength="255" placeholder="Contoh: sudah selesai dicuci">
                        @error('catatan') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn">Simpan Status</button>
                    </div>
                </form>

                @if ($bolehBatal)
                    <hr class="divider">

                    <form action="{{ route('orders.batalkan', $order) }}" method="POST"
                          onsubmit="return confirm('Batalkan pesanan {{ $order->kode_order }}? Tindakan ini tidak bisa dibatalkan.')">
                        @csrf
                        @method('PUT')
                        <p class="form-hint" style="margin-bottom: 10px;">
                            Pembatalan hanya bisa dilakukan dari status Diterima atau Dicuci.
                        </p>
                        <button type="submit" class="btn btn-danger btn-sm">Batalkan Pesanan</button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    @if ($order->catatan)
        <div class="card">
            <h2 class="card-title">Catatan Pesanan</h2>
            <p>{{ $order->catatan }}</p>
        </div>
    @endif

    <div class="card">
        <h2 class="card-title">Pembayaran</h2>

        @if ($order->payments->isEmpty())
            <p class="muted">Belum ada pembayaran tercatat untuk pesanan ini.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Metode</th>
                        <th class="num" style="text-align: right;">Jumlah</th>
                        <th class="num" style="text-align: right;">Kembalian</th>
                        <th>Dicatat Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->payments as $payment)
                        <tr>
                            <td class="nowrap">{{ $payment->tanggal_bayar->format('d M Y H:i') }}</td>
                            <td>{{ ucfirst($payment->metode) }}</td>
                            <td class="num" style="text-align: right;">Rp {{ number_format($payment->jumlah_bayar, 0, ',', '.') }}</td>
                            <td class="num" style="text-align: right;">Rp {{ number_format($payment->kembalian, 0, ',', '.') }}</td>
                            <td class="muted">{{ $payment->user->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="form-hint" style="margin-top: 12px;">
            Pencatatan pembayaran baru tersedia pada tahap berikutnya.
        </p>
    </div>
@endsection
