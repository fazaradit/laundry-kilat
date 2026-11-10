@extends('layouts.app')

@section('title', 'Pesanan Baru')

@section('content')
    <h1>Pesanan Baru</h1>
    <p class="subtitle">
        Total dan estimasi selesai dihitung ulang oleh server. Angka pada ringkasan di layar hanya perkiraan.
    </p>

    <form action="{{ route('orders.store') }}" method="POST" id="form-pesanan">
        @csrf

        <div class="card">
            <h2 class="card-title">Pelanggan</h2>

            <div class="form-grid">
                <div class="form-group full">
                    <label for="customer_id">Pelanggan</label>
                    <select id="customer_id" name="customer_id" required>
                        <option value="">Pilih pelanggan</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                @selected((int) old('customer_id', $customerTerpilih) === $customer->id)>
                                {{ $customer->nama }} - {{ $customer->nomor_telepon }}
                            </option>
                        @endforeach
                    </select>
                    <span class="form-hint">
                        Pelanggan belum terdaftar?
                        <a href="{{ route('customers.create') }}" class="link-strong">Tambahkan dulu</a>.
                    </span>
                    @error('customer_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">Item Layanan</h2>

            @error('items') <div class="form-error" style="margin-bottom: 12px;">{{ $message }}</div> @enderror

            @if ($services->isEmpty())
                <p class="muted">
                    Belum ada layanan aktif. Admin perlu menambahkan layanan terlebih dahulu di menu Layanan.
                </p>
            @else
                <div class="item-rows" id="item-rows">
                    {{-- Baris awal dirender server. Baris tambahan memakai <template>. --}}
                    @php
                        $barisAwal = old('items', [['service_id' => '', 'qty' => 1]]);
                    @endphp

                    @foreach ($barisAwal as $i => $baris)
                        <div class="item-row" data-index="{{ $i }}">
                            <div class="form-group item-service">
                                <label @if ($i === 0) for="items_{{ $i }}_service_id" @endif>Layanan</label>
                                <select name="items[{{ $i }}][service_id]" id="items_{{ $i }}_service_id" class="pilih-layanan" required>
                                    <option value="">Pilih layanan</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}"
                                                data-harga="{{ $service->harga }}"
                                                data-satuan="{{ $service->satuan }}"
                                                data-estimasi="{{ $service->estimasi_hari }}"
                                                @selected((string) ($baris['service_id'] ?? '') === (string) $service->id)>
                                            {{ $service->nama_layanan }} (Rp {{ number_format($service->harga, 0, ',', '.') }}/{{ $service->satuan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group item-qty">
                                <label @if ($i === 0) for="items_{{ $i }}_qty" @endif>Jumlah</label>
                                <input type="number" name="items[{{ $i }}][qty]" id="items_{{ $i }}_qty"
                                       class="input-qty" value="{{ $baris['qty'] ?? 1 }}"
                                       min="0.01" max="999.99" step="0.01" required>
                                <span class="form-hint satuan-hint">kg atau pcs</span>
                            </div>

                            <div class="item-subtotal">
                                <span class="label">Subtotal</span>
                                <span class="num subtotal-nilai">Rp 0</span>
                            </div>

                            <button type="button" class="btn btn-secondary btn-sm hapus-baris">Hapus</button>
                        </div>

                        @error("items.{$i}.service_id")
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                        @error("items.{$i}.qty")
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    @endforeach
                </div>

                <button type="button" class="btn btn-secondary btn-sm" id="tambah-baris">+ Tambah baris</button>
            @endif
        </div>

        <div class="card">
            <h2 class="card-title">Ringkasan</h2>

            <div class="summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="num" id="ringkasan-subtotal">Rp 0</span>
                </div>

                <div class="summary-row">
                    <label for="diskon">Diskon</label>
                    <input type="number" id="diskon" name="diskon" value="{{ old('diskon', 0) }}"
                           min="0" step="500" class="input-diskon">
                </div>

                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span class="num" id="ringkasan-total">Rp 0</span>
                </div>

                <div class="summary-row">
                    <span>Estimasi selesai</span>
                    <span id="ringkasan-estimasi" class="num">-</span>
                </div>
            </div>

            @error('diskon') <span class="form-error">{{ $message }}</span> @enderror

            <div class="form-group" style="margin-top: 18px;">
                <label for="catatan">Catatan <span class="muted">(opsional)</span></label>
                <textarea id="catatan" name="catatan" placeholder="Contoh: pisahkan pakaian putih">{{ old('catatan') }}</textarea>
                @error('catatan') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn" @disabled($services->isEmpty())>Simpan Pesanan</button>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>

    <template id="template-baris">
        <div class="item-row">
            <div class="form-group item-service">
                <label>Layanan</label>
                <select class="pilih-layanan" required>
                    <option value="">Pilih layanan</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}"
                                data-harga="{{ $service->harga }}"
                                data-satuan="{{ $service->satuan }}"
                                data-estimasi="{{ $service->estimasi_hari }}">
                            {{ $service->nama_layanan }} (Rp {{ number_format($service->harga, 0, ',', '.') }}/{{ $service->satuan }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group item-qty">
                <label>Jumlah</label>
                <input type="number" class="input-qty" value="1" min="0.01" max="999.99" step="0.01" required>
                <span class="form-hint satuan-hint">kg atau pcs</span>
            </div>

            <div class="item-subtotal">
                <span class="label">Subtotal</span>
                <span class="num subtotal-nilai">Rp 0</span>
            </div>

            <button type="button" class="btn btn-secondary btn-sm hapus-baris">Hapus</button>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        (function () {
            var rows = document.getElementById('item-rows');
            var template = document.getElementById('template-baris');
            var tombolTambah = document.getElementById('tambah-baris');
            var inputDiskon = document.getElementById('diskon');

            if (!rows || !template) {
                return;
            }

            function rupiah(nilai) {
                return 'Rp ' + Math.round(nilai).toLocaleString('id-ID');
            }

            // Nomor indeks harus rapat 0..n-1 supaya validasi items.* tetap rapi.
            function rapikanIndeks() {
                rows.querySelectorAll('.item-row').forEach(function (row, i) {
                    row.dataset.index = i;
                    row.querySelector('.pilih-layanan').name = 'items[' + i + '][service_id]';
                    row.querySelector('.input-qty').name = 'items[' + i + '][qty]';
                });
            }

            function hitungBaris(row) {
                var select = row.querySelector('.pilih-layanan');
                var qty = row.querySelector('.input-qty');
                var opsi = select.options[select.selectedIndex];
                var harga = opsi && opsi.dataset.harga ? parseFloat(opsi.dataset.harga) : 0;
                var satuan = opsi && opsi.dataset.satuan ? opsi.dataset.satuan : '';
                var jumlah = parseFloat(qty.value) || 0;

                // qty pcs tidak masuk akal kalau pecahan, jadi dibatasi ke bulat.
                if (satuan === 'pcs') {
                    qty.step = '1';
                } else if (satuan === 'kg') {
                    qty.step = '0.01';
                }

                var subtotal = harga * jumlah;
                row.querySelector('.subtotal-nilai').textContent = rupiah(subtotal);
                row.querySelector('.satuan-hint').textContent = satuan ? 'dalam ' + satuan : 'kg atau pcs';

                return subtotal;
            }

            function hitungSemua() {
                var subtotal = 0;
                var estimasi = 0;

                rows.querySelectorAll('.item-row').forEach(function (row) {
                    subtotal += hitungBaris(row);

                    var select = row.querySelector('.pilih-layanan');
                    var opsi = select.options[select.selectedIndex];

                    if (opsi && opsi.dataset.estimasi) {
                        estimasi = Math.max(estimasi, parseInt(opsi.dataset.estimasi, 10) || 0);
                    }
                });

                var diskon = parseFloat(inputDiskon.value) || 0;

                document.getElementById('ringkasan-subtotal').textContent = rupiah(subtotal);
                document.getElementById('ringkasan-total').textContent = rupiah(Math.max(0, subtotal - diskon));

                var target = document.getElementById('ringkasan-estimasi');

                if (estimasi > 0) {
                    var tanggal = new Date();
                    tanggal.setDate(tanggal.getDate() + estimasi);
                    target.textContent = tanggal.toLocaleDateString('id-ID', {
                        day: '2-digit', month: 'short', year: 'numeric'
                    }) + ' (' + estimasi + ' hari)';
                } else {
                    target.textContent = '-';
                }
            }

            function pasangBaris(row) {
                row.querySelector('.pilih-layanan').addEventListener('change', hitungSemua);
                row.querySelector('.input-qty').addEventListener('input', hitungSemua);

                row.querySelector('.hapus-baris').addEventListener('click', function () {
                    // Sisakan minimal satu baris.
                    if (rows.querySelectorAll('.item-row').length <= 1) {
                        row.querySelector('.pilih-layanan').value = '';
                        row.querySelector('.input-qty').value = 1;
                    } else {
                        row.remove();
                    }

                    rapikanIndeks();
                    hitungSemua();
                });
            }

            rows.querySelectorAll('.item-row').forEach(pasangBaris);

            if (tombolTambah) {
                tombolTambah.addEventListener('click', function () {
                    var baru = template.content.firstElementChild.cloneNode(true);
                    rows.appendChild(baru);
                    pasangBaris(baru);
                    rapikanIndeks();
                    hitungSemua();
                    baru.querySelector('.pilih-layanan').focus();
                });
            }

            inputDiskon.addEventListener('input', hitungSemua);

            rapikanIndeks();
            hitungSemua();
        })();
    </script>
@endpush
