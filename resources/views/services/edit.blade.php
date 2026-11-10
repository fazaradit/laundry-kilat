@extends('layouts.app')

@section('title', 'Edit Layanan')

@section('content')
    <h1>Edit Layanan</h1>
    <p class="subtitle">
        Mengubah harga tidak mengubah struk pesanan lama. Harga pada pesanan tersimpan sebagai snapshot.
    </p>

    <div class="card">
        <form action="{{ route('services.update', $service) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group full">
                    <label for="nama_layanan">Nama Layanan</label>
                    <input type="text" id="nama_layanan" name="nama_layanan"
                           value="{{ old('nama_layanan', $service->nama_layanan) }}" required>
                    @error('nama_layanan') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="satuan">Satuan</label>
                    <select id="satuan" name="satuan" required>
                        <option value="kg" @selected(old('satuan', $service->satuan) === 'kg')>kg (kiloan)</option>
                        <option value="pcs" @selected(old('satuan', $service->satuan) === 'pcs')>pcs (satuan)</option>
                    </select>
                    @error('satuan') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="harga">Harga per Satuan (Rp)</label>
                    <input type="number" id="harga" name="harga" value="{{ old('harga', $service->harga) }}"
                           min="500" step="500" required>
                    @error('harga') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="estimasi_hari">Estimasi Selesai (hari)</label>
                    <input type="number" id="estimasi_hari" name="estimasi_hari"
                           value="{{ old('estimasi_hari', $service->estimasi_hari) }}" min="1" max="14" required>
                    @error('estimasi_hari') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="aktif" @selected(old('status', $service->status) === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(old('status', $service->status) === 'nonaktif')>Nonaktif</option>
                    </select>
                    @error('status') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn">Perbarui</button>
                <a href="{{ route('services.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
