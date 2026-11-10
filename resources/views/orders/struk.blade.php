@extends('layouts.print')

@section('title', 'Struk '.$order->kode_order)

@section('content')
    <div class="struk">
        <div class="struk-head">
            <p class="nama-laundry">Laundry Kilat</p>
            <p class="alamat">Laundry kiloan dan satuan</p>
        </div>

        <div class="struk-meta">
            <div class="baris">
                <span>Kode</span>
                <span class="num">{{ $order->kode_order }}</span>
            </div>
            <div class="baris">
                <span>Masuk</span>
                <span class="num">{{ $order->tanggal_masuk->format('d/m/Y H:i') }}</span>
            </div>
            <div class="baris">
                <span>Estimasi</span>
                <span class="num">{{ $order->estimasi_selesai->format('d/m/Y') }}</span>
            </div>
            <div class="baris">
                <span>Pelanggan</span>
                <span>{{ $order->customer->nama }}</span>
            </div>
            <div class="baris">
                <span>Kasir</span>
                <span>{{ $order->user->name }}</span>
            </div>
            <div class="baris">
                <span>Status</span>
                <span>{{ $order->status->label() }}</span>
            </div>
        </div>

        <table class="daftar-item">
            <tbody>
                {{-- Harga diambil dari snapshot order_items, bukan dari master
                     layanan. Mengubah harga di master tidak boleh mengubah
                     struk lama (PRD 6.4 no. 1 / AC-23). --}}
                @foreach ($order->items as $item)
                    <tr>
                        <td class="nama" colspan="2">{{ $item->service->nama_layanan }}</td>
                    </tr>
                    <tr>
                        <td class="rincian">
                            {{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}
                            {{ $item->service->satuan }} x Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                        </td>
                        <td class="nilai num">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ringkasan">
            <div class="baris">
                <span>Subtotal</span>
                <span class="num">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>
            @if ($order->diskon > 0)
                <div class="baris">
                    <span>Diskon</span>
                    <span class="num">- Rp {{ number_format($order->diskon, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="baris total">
                <span>Total</span>
                <span class="num">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
            </div>
            <div class="baris">
                <span>Dibayar</span>
                <span class="num">Rp {{ number_format($order->dibayar, 0, ',', '.') }}</span>
            </div>
            <div class="baris sisa">
                <span>Sisa</span>
                <span class="num">Rp {{ number_format($order->sisa_bayar, 0, ',', '.') }}</span>
            </div>

            @php $pembayaranTunai = $order->payments->where('metode', 'tunai')->where('kembalian', '>', 0)->last(); @endphp

            @if ($pembayaranTunai)
                <div class="baris">
                    <span>Uang diterima</span>
                    <span class="num">Rp {{ number_format($pembayaranTunai->uang_diterima, 0, ',', '.') }}</span>
                </div>
                <div class="baris">
                    <span>Kembalian</span>
                    <span class="num">Rp {{ number_format($pembayaranTunai->kembalian, 0, ',', '.') }}</span>
                </div>
            @endif
        </div>

        <div class="struk-foot">
            <p>{{ $order->status_pelunasan }}</p>
            <p>Terima kasih atas kepercayaan Anda.</p>
        </div>
    </div>
@endsection
