@extends('layouts.app')

@section('title', 'Tambah Layanan')

@section('content')
    <h1>Tambah Layanan</h1>
    <p class="subtitle">Layanan baru akan langsung tersedia di form pesanan.</p>

    <div class="card">
        <form action="{{ route('services.store') }}" method="POST">
            @csrf

            <div class="form-grid">
                <div class="form-group full">
                    <label for="nama_layanan">Nama Layanan</label>
                    <input type="text" id="nama_layanan" name="nama_layanan" value="{{ old('nama_layanan') }}"
                           placeholder="Contoh: Cuci Kering" required>
                    @error('nama_layanan') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="satuan">Satuan</label>
                    <select id="satuan" name="satuan" required>
                        <option value="kg" @selected(old('satuan') === 'kg')>kg (kiloan)</option>
                        <option value="pcs" @selected(old('satuan') === 'pcs')>pcs (satuan)</option>
                    </select>
                    @error('satuan') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="harga">Harga per Satuan (Rp)</label>
                    <input type="number" id="harga" name="harga" value="{{ old('harga') }}" min="500" step="500"
                           placeholder="7000" required>
                    <span class="form-hint">Angka bulat tanpa titik, contoh: 7000.</span>
                    @error('harga') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="estimasi_hari">Estimasi Selesai (hari)</label>
                    <input type="number" id="estimasi_hari" name="estimasi_hari" value="{{ old('estimasi_hari', 2) }}"
                           min="1" max="14" required>
                    @error('estimasi_hari') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="aktif" @selected(old('status', 'aktif') === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(old('status') === 'nonaktif')>Nonaktif</option>
                    </select>
                    <span class="form-hint">Layanan nonaktif tidak muncul di form pesanan.</span>
                    @error('status') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn">Simpan</button>
                <a href="{{ route('services.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
