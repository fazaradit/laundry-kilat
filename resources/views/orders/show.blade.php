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
            <a href="{{ route('orders.struk', $order) }}" class="btn btn-secondary btn-sm">Struk</a>
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
                    // State machine hanya mengizinkan satu langkah maju, jadi
                    // dropdown satu opsi tidak berguna. Tujuan dikirim sebagai
                    // hidden input dan tetap divalidasi server.
                    $berikutnya = $order->status->berikutnya();
                    $bolehBatal = auth()->user()->isAdmin() && $order->status->bolehDibatalkan();
                @endphp

                <form action="{{ route('orders.status', $order) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="status" value="{{ $berikutnya?->value }}">

                    <p class="status-next">
                        Langkah berikutnya:
                        <strong>{{ $berikutnya?->label() ?? '-' }}</strong>
                    </p>
                    <p class="form-hint" style="margin-bottom: 16px;">
                        Status hanya bisa maju satu tahap. Tidak bisa mundur atau melompat.
                    </p>

                    @error('status') <span class="form-error">{{ $message }}</span> @enderror

                    <div class="form-group">
                        <label for="catatan_status">Catatan <span class="muted">(opsional)</span></label>
                        <input type="text" id="catatan_status" name="catatan" value="{{ old('catatan') }}"
                               maxlength="255" placeholder="Contoh: sudah selesai diproses">
                        @error('catatan') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn">
                            Proses ke {{ $berikutnya?->label() ?? 'tahap berikutnya' }}
                        </button>
                    </div>
                </form>

                @if ($bolehBatal)
                    <hr class="divider">

                    <form action="{{ route('orders.batalkan', $order) }}" method="POST"
                          onsubmit="return confirm('Batalkan pesanan {{ $order->kode_order }}? Tindakan ini tidak bisa dibatalkan.')">
                        @csrf
                        @method('PUT')
                        <p class="form-hint" style="margin-bottom: 10px;">
                            Pembatalan hanya bisa dilakukan dari status Diterima atau Diproses.
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
                        <th class="num" style="text-align: right;">Uang Diterima</th>
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
                            <td class="num" style="text-align: right;">
                                {{ $payment->uang_diterima === null ? '-' : 'Rp '.number_format($payment->uang_diterima, 0, ',', '.') }}
                            </td>
                            <td class="num" style="text-align: right;">
                                {{ $payment->kembalian > 0 ? 'Rp '.number_format($payment->kembalian, 0, ',', '.') : '-' }}
                            </td>
                            <td class="muted">{{ $payment->user->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($order->status->value === 'dibatalkan')
            <p class="form-hint" style="margin-top: 12px;">
                Pesanan dibatalkan, tidak ada pembayaran yang bisa dicatat.
            </p>
        @elseif ($order->sisa_bayar <= 0)
            <p class="form-hint" style="margin-top: 12px;">
                Tagihan pesanan ini sudah lunas.
            </p>
        @else
            <hr class="divider">

            <form action="{{ route('orders.payments.store', $order) }}" method="POST" id="form-pembayaran">
                @csrf

                <div class="form-grid">
                    <div class="form-group">
                        <label for="metode">Metode</label>
                        <select id="metode" name="metode" required>
                            <option value="tunai" @selected(old('metode', 'tunai') === 'tunai')>Tunai</option>
                            <option value="transfer" @selected(old('metode') === 'transfer')>Transfer</option>
                            <option value="qris" @selected(old('metode') === 'qris')>QRIS</option>
                        </select>
                        @error('metode') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="jumlah_bayar">Jumlah Bayar</label>
                        <input type="number" id="jumlah_bayar" name="jumlah_bayar"
                               value="{{ old('jumlah_bayar', $order->sisa_bayar) }}"
                               min="1" max="{{ $order->sisa_bayar }}" step="1" required>
                        <span class="form-hint">
                            Sisa tagihan Rp {{ number_format($order->sisa_bayar, 0, ',', '.') }}.
                        </span>
                        @error('jumlah_bayar') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group" id="grup-uang-diterima">
                        <label for="uang_diterima">Uang Diterima</label>
                        <input type="number" id="uang_diterima" name="uang_diterima"
                               value="{{ old('uang_diterima') }}" min="1" step="1">
                        <span class="form-hint" id="hint-kembalian">Kembalian dihitung otomatis.</span>
                        @error('uang_diterima') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group full">
                        <label for="catatan_bayar">Catatan <span class="muted">(opsional)</span></label>
                        <input type="text" id="catatan_bayar" name="catatan" value="{{ old('catatan') }}"
                               maxlength="255" placeholder="Contoh: uang muka">
                        @error('catatan') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn">Catat Pembayaran</button>
                </div>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('form-pembayaran');

            if (!form) {
                return;
            }

            var metode = document.getElementById('metode');
            var jumlah = document.getElementById('jumlah_bayar');
            var grupDiterima = document.getElementById('grup-uang-diterima');
            var diterima = document.getElementById('uang_diterima');
            var hint = document.getElementById('hint-kembalian');

            function rupiah(nilai) {
                return 'Rp ' + Math.round(nilai).toLocaleString('id-ID');
            }

            // Transfer dan QRIS tidak punya uang fisik, jadi kolomnya
            // disembunyikan dan tidak dikirim supaya server tidak menebak.
            function sesuaikan() {
                var tunai = metode.value === 'tunai';

                grupDiterima.style.display = tunai ? '' : 'none';
                diterima.required = tunai;
                diterima.disabled = !tunai;

                if (!tunai) {
                    diterima.value = '';
                }

                hitungKembalian();
            }

            function hitungKembalian() {
                if (!diterima || diterima.disabled) {
                    return;
                }

                var bayar = parseFloat(jumlah.value) || 0;
                var uang = parseFloat(diterima.value) || 0;

                if (uang <= 0) {
                    hint.textContent = 'Kembalian dihitung otomatis.';
                    hint.classList.remove('form-error');
                    return;
                }

                if (uang < bayar) {
                    hint.textContent = 'Kurang ' + rupiah(bayar - uang) + ' dari jumlah bayar.';
                    hint.classList.add('form-error');
                    return;
                }

                hint.textContent = 'Kembalian ' + rupiah(uang - bayar) + '.';
                hint.classList.remove('form-error');
            }

            metode.addEventListener('change', sesuaikan);
            jumlah.addEventListener('input', hitungKembalian);
            diterima.addEventListener('input', hitungKembalian);

            sesuaikan();
        })();
    </script>
@endpush
