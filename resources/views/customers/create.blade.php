@extends('layouts.app')

@section('title', 'Tambah Pelanggan')

@section('content')
    <h1>Tambah Pelanggan</h1>
    <p class="subtitle">Lengkapi data pelanggan laundry.</p>

    <div class="card">
        <form action="{{ route('customers.store') }}" method="POST">
            @csrf

            <div class="form-grid">
                <div class="form-group full">
                    <label for="nama">Nama Pelanggan</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" placeholder="Contoh: Rina Wulandari" required>
                    @error('nama') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="nomor_telepon">Nomor Telepon</label>
                    <input type="text" id="nomor_telepon" name="nomor_telepon" value="{{ old('nomor_telepon') }}"
                           placeholder="081234567890" required>
                    <span class="form-hint">Diawali 0, tanpa spasi atau strip.</span>
                    @error('nomor_telepon') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group full">
                    <label for="alamat">Alamat <span class="muted">(opsional)</span></label>
                    <textarea id="alamat" name="alamat" placeholder="Contoh: Jl. Raya Kampus No. 12">{{ old('alamat') }}</textarea>
                    @error('alamat') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn">Simpan</button>
                <a href="{{ route('customers.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
