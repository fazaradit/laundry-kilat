@extends('layouts.app')

@section('title', 'Edit Pelanggan')

@section('content')
    <h1>Edit Pelanggan</h1>
    <p class="subtitle">Perbarui data pelanggan {{ $customer->nama }}.</p>

    <div class="card">
        <form action="{{ route('customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group full">
                    <label for="nama">Nama Pelanggan</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama', $customer->nama) }}" required>
                    @error('nama') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="nomor_telepon">Nomor Telepon</label>
                    <input type="text" id="nomor_telepon" name="nomor_telepon"
                           value="{{ old('nomor_telepon', $customer->nomor_telepon) }}" required>
                    <span class="form-hint">Diawali 0, tanpa spasi atau strip.</span>
                    @error('nomor_telepon') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group full">
                    <label for="alamat">Alamat <span class="muted">(opsional)</span></label>
                    <textarea id="alamat" name="alamat">{{ old('alamat', $customer->alamat) }}</textarea>
                    @error('alamat') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn">Perbarui</button>
                <a href="{{ route('customers.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
@endsection
