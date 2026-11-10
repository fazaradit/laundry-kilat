<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}
