<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    /**
     * Semua layanan. `?status=aktif|nonaktif` menyaring opsional.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ]);

        $services = Service::query()
            ->when(isset($data['status']), fn ($query) => $query->where('status', $data['status']))
            ->orderBy('nama_layanan')
            ->get();

        return ServiceResource::collection($services);
    }

    /**
     * Hapus layanan yang belum pernah dipakai pesanan.
     *
     * Aturannya sama dengan versi web (BR-04): layanan yang sudah dipakai harus
     * dinonaktifkan, bukan dihapus, supaya riwayat pesanan dan snapshot harga
     * tetap utuh. Foreign key `restrictOnDelete` menjaga ini juga di level
     * database, jadi ada dua lapis.
     */
    public function destroy(Service $service): JsonResponse
    {
        if ($service->orderItems()->exists()) {
            throw ValidationException::withMessages([
                'service' => 'Layanan "'.$service->nama_layanan.'" sudah dipakai pada pesanan, jadi tidak bisa dihapus. Ubah statusnya menjadi nonaktif.',
            ]);
        }

        $nama = $service->nama_layanan;
        $service->delete();

        return response()->json([
            'message' => 'Layanan "'.$nama.'" berhasil dihapus.',
        ]);
    }
}
