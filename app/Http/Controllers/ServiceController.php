<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $services = Service::query()
            ->when(in_array($status, ['aktif', 'nonaktif'], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('nama_layanan', 'like', "%{$search}%"))
            ->withCount('orderItems')
            ->orderBy('nama_layanan')
            ->paginate(10)
            ->withQueryString();

        return view('services.index', compact('services', 'status', 'search'));
    }

    public function create(): View
    {
        return view('services.create');
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $service = Service::create($request->validated());

        return redirect()->route('services.index')
            ->with('success', "Layanan \"{$service->nama_layanan}\" berhasil ditambahkan.");
    }

    public function edit(Service $service): View
    {
        return view('services.edit', compact('service'));
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('services.index')
            ->with('success', "Layanan \"{$service->nama_layanan}\" berhasil diperbarui.");
    }

    public function destroy(Service $service): RedirectResponse
    {
        // Riwayat pesanan harus tetap utuh (snapshot harga), jadi layanan yang
        // sudah pernah dipakai tidak boleh dihapus - nonaktifkan saja.
        if ($service->orderItems()->exists()) {
            return redirect()->route('services.index')
                ->with('error', "Layanan \"{$service->nama_layanan}\" sudah dipakai pada pesanan, jadi tidak bisa dihapus. Ubah statusnya menjadi nonaktif.");
        }

        $nama = $service->nama_layanan;
        $service->delete();

        return redirect()->route('services.index')
            ->with('success', "Layanan \"{$nama}\" berhasil dihapus.");
    }
}
