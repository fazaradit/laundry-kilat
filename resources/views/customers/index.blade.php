@extends('layouts.app')

@section('title', 'Pelanggan')

@section('content')
    <div class="toolbar">
        <div>
            <h1>Pelanggan</h1>
            <p class="subtitle">Data pelanggan laundry yang terdaftar.</p>
        </div>

        <div class="filter-form">
            <form action="{{ route('customers.index') }}" method="GET" class="filter-form">
                <div class="form-group">
                    <label for="q">Cari</label>
                    <input type="search" id="q" name="q" value="{{ $search }}" placeholder="Nama atau nomor telepon">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
                @if ($search !== '')
                    <a href="{{ route('customers.index') }}" class="btn btn-outline btn-sm">Reset</a>
                @endif
            </form>
            <a href="{{ route('customers.create') }}" class="btn">+ Pelanggan</a>
        </div>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Nomor Telepon</th>
                    <th>Alamat</th>
                    <th class="num" style="text-align: right;">Pesanan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>{{ $customer->nama }}</td>
                        <td>{{ $customer->nomor_telepon }}</td>
                        <td>{{ $customer->alamat ?: '-' }}</td>
                        <td class="num" style="text-align: right;">{{ $customer->orders_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline btn-sm">Edit</a>
                                <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus pelanggan {{ $customer->nama }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty">
                            @if ($search !== '')
                                Tidak ada pelanggan yang cocok dengan "{{ $search }}".
                            @else
                                Belum ada data pelanggan. Klik "+ Pelanggan" untuk menambahkan.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $customers->links() }}
    </div>
@endsection
