@extends('layouts.app')

@section('title', 'Layanan')

@section('content')
    <div class="toolbar">
        <div>
            <h1>Layanan</h1>
            <p class="subtitle">Daftar layanan laundry beserta harga per satuan.</p>
        </div>

        <div class="filter-form">
            <form action="{{ route('services.index') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="q">Cari</label>
                    <input type="search" id="q" name="q" value="{{ $search }}" placeholder="Nama layanan">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Semua</option>
                        <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('services.index') }}" class="btn btn-outline btn-sm">Reset</a>
                @endif
            </form>
            <a href="{{ route('services.create') }}" class="btn">+ Layanan</a>
        </div>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Nama Layanan</th>
                    <th>Satuan</th>
                    <th>Harga</th>
                    <th>Estimasi</th>
                    <th>Status</th>
                    <th>Dipakai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($services as $service)
                    <tr>
                        <td>{{ $service->nama_layanan }}</td>
                        <td>{{ $service->satuan }}</td>
                        <td>Rp {{ number_format($service->harga, 0, ',', '.') }} / {{ $service->satuan }}</td>
                        <td>{{ $service->estimasi_hari }} hari</td>
                        <td>
                            <span class="badge {{ $service->isAktif() ? 'badge-green' : 'badge-gray' }}">
                                {{ ucfirst($service->status) }}
                            </span>
                        </td>
                        <td>{{ $service->order_items_count }}×</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('services.edit', $service) }}" class="btn btn-outline btn-sm">Edit</a>
                                <form action="{{ route('services.destroy', $service) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus layanan {{ $service->nama_layanan }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">Belum ada layanan. Klik "+ Layanan" untuk menambahkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $services->links() }}
    </div>
@endsection
